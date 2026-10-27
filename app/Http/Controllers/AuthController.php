<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\KenyanPhoneNumber;
use App\Services\PhoneVerificationCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Throwable;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:30'], 'password' => ['required', 'string']]);
        $phoneService = app(KenyanPhoneNumber::class);
        $phone = $phoneService->normalize($data['phone']);
        $limiterKey = 'login:'.hash('sha256', $request->ip().'|'.($phone ?? $data['phone']));

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return back()->withErrors(['phone' => 'Sign-in is temporarily locked. Wait 15 minutes, then try again.'])->onlyInput('phone');
        }

        $matches = $phone ? User::query()->whereIn('phone', $phoneService->lookupVariants($phone))->limit(2)->get() : collect();
        $user = $matches->count() === 1 ? $matches->first() : null;
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($limiterKey, 900);
            if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
                Log::notice('Farmers Connect sign-in locked after repeated failures.', ['ip' => $request->ip(), 'account_fingerprint' => hash('sha256', (string) ($phone ?? 'invalid'))]);
            }

            return back()->withErrors(['phone' => 'That phone number and password did not match an account.'])->onlyInput('phone');
        }

        RateLimiter::clear($limiterKey);
        if (! $user->phone_verified_at) {
            if (! $phone) {
                return back()->withErrors(['phone' => 'The phone number on this account is not valid. Contact Farmers Connect support to update it.'])->onlyInput('phone');
            }
            $request->session()->put('pending_phone_verification', ['phone' => $phone, 'purpose' => 'login', 'user_id' => $user->id, 'remember' => $request->boolean('remember')]);
            try {
                app(PhoneVerificationCodes::class)->send($phone, 'login');
            } catch (\RuntimeException) {
                return back()->withErrors(['phone' => 'Phone verification could not send a code. Check that Farmers Connect SMS is configured.'])->onlyInput('phone');
            }

            return redirect()->route('phone.verify')->with('status', 'We sent a sign-in code to your phone.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        return redirect()->intended($user->role === 'buyer' ? route('account') : ($user->role === 'farmer' && ! $user->farmer_verified_at ? route('farmer.pending') : route('farm.dashboard')));
    }

    public function showRegister() { return view('auth.register', ['accountRole' => 'buyer']); }

    public function showRegisterRole(string $role)
    {
        abort_unless(in_array($role, ['buyer', 'farmer'], true), 404);
        return view('auth.register', ['accountRole' => $role]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:buyer,farmer'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:150'],
        ]);
        $phoneService = app(KenyanPhoneNumber::class);
        $phone = $phoneService->normalize($data['phone']);
        if (! $phone) return back()->withErrors(['phone' => 'Enter a valid phone number, such as 0712 345 678.'])->withInput($request->except('password', 'password_confirmation'));
        if (User::query()->whereIn('phone', $phoneService->lookupVariants($phone))->exists()) {
            return back()->withErrors(['phone' => 'This phone number already has an account. Sign in instead.'])->withInput($request->except('password', 'password_confirmation'));
        }
        $data['email'] = mb_strtolower($data['email']);
        if (User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
            return back()->withErrors(['email' => 'That email address is already associated with an account.'])->withInput($request->except('password', 'password_confirmation'));
        }

        $request->session()->put('pending_account', [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'location_name' => $data['location_name'] ?? null,
        ]);
        $request->session()->put('pending_phone_verification', ['phone' => $phone, 'purpose' => 'register']);

        try {
            app(PhoneVerificationCodes::class)->send($phone, 'register');
        } catch (\RuntimeException $exception) {
            $message = str_contains($exception->getMessage(), 'Too many') ? $exception->getMessage() : 'Phone verification is not ready. The Farmers Connect administrator must configure SMS delivery before accounts can be created.';
            return back()->withErrors(['phone' => $message])->withInput($request->except('password', 'password_confirmation'));
        }

        return redirect()->route('phone.verify')->with('status', 'We sent a six-digit verification code to your phone.');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:100']]);

        try {
            $status = Password::sendResetLink(['email' => mb_strtolower($data['email'])]);
        } catch (Throwable $exception) {
            Log::warning('Farmers Connect password reset email could not be sent.', ['type' => $exception::class]);

            return back()->withErrors(['email' => 'Password reset email is temporarily unavailable. Please try again later.'])->withInput();
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Please wait a minute before requesting another reset link.'])->withInput();
        }

        return back()->with('status', 'If an account matches that email address, a password reset link will be sent.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [...$data, 'email' => mb_strtolower($data['email'])],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your phone number and new password.');
        }

        return back()->withErrors(['email' => 'That reset link is invalid or has expired. Request a new one and try again.'])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
