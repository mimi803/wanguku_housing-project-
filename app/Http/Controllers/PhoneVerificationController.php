<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PhoneVerificationCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PhoneVerificationController extends Controller
{
    public function show(Request $request)
    {
        $pending = $request->session()->get('pending_phone_verification');
        abort_unless(is_array($pending) && isset($pending['phone'], $pending['purpose']), 403, 'Start sign-in or account creation to request a phone code.');
        abort_unless(in_array($pending['purpose'], ['register', 'login', 'reset'], true), 403);
        return view('auth.verify-phone', ['purpose' => $pending['purpose'], 'phone' => $pending['phone']]);
    }

    public function verify(Request $request, PhoneVerificationCodes $codes)
    {
        $pending = $request->session()->get('pending_phone_verification');
        abort_unless(is_array($pending) && isset($pending['phone'], $pending['purpose']), 403);
        abort_unless(in_array($pending['purpose'], ['register', 'login', 'reset'], true), 403);
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => [$pending['purpose'] === 'reset' ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($pending['purpose'] === 'register') {
            $registration = $request->session()->get('pending_account');
            abort_unless(is_array($registration) && ($registration['phone'] ?? null) === $pending['phone'], 403);
        } else {
            $existingUser = User::query()->findOrFail($pending['user_id'] ?? 0);
            abort_unless($existingUser->phone && app(\App\Services\KenyanPhoneNumber::class)->normalize($existingUser->phone) === $pending['phone'], 403);
        }

        if (! $codes->verify($pending['phone'], $pending['purpose'], $data['code'])) {
            return back()->withErrors(['code' => 'That code is invalid or expired. Request a new code and try again.'])->withInput();
        }

        if ($pending['purpose'] === 'register') {
            $registration = $request->session()->get('pending_account');
            $user = User::create([...$registration, 'phone_verified_at' => now()]);
            $request->session()->forget('pending_account');
            $request->session()->forget('pending_phone_verification');
            Auth::login($user);
            $request->session()->regenerate();

            if ($user->role === 'farmer') {
                return redirect()->route('farmer.pending')->with('status', 'Your phone is verified. Farmers Connect will review the farm profile before publishing it.');
            }

            return redirect()->route('account')->with('status', 'Your phone is verified. Welcome to Farmers Connect.');
        }

        $user = User::query()->findOrFail($pending['user_id'] ?? 0);
        abort_unless($user->phone && app(\App\Services\KenyanPhoneNumber::class)->normalize($user->phone) === $pending['phone'], 403);
        if ($pending['purpose'] === 'reset') {
            $user->forceFill(['password' => Hash::make($data['password']), 'phone_verified_at' => now()])->save();
            $request->session()->forget('pending_phone_verification');
            $request->session()->forget('pending_account');
            return redirect()->route('login')->with('status', 'Your password has been changed. Sign in with your phone number.');
        }

        $user->forceFill(['phone_verified_at' => now()])->save();
        $request->session()->forget('pending_phone_verification');
        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended($user->role === 'buyer' ? route('account') : ($user->role === 'farmer' && ! $user->farmer_verified_at ? route('farmer.pending') : route('farm.dashboard')));
    }

    public function resend(Request $request, PhoneVerificationCodes $codes)
    {
        $pending = $request->session()->get('pending_phone_verification');
        abort_unless(is_array($pending) && isset($pending['phone'], $pending['purpose']), 403);
        abort_unless(in_array($pending['purpose'], ['register', 'login', 'reset'], true), 403);
        if ($pending['purpose'] === 'register') {
            $registration = $request->session()->get('pending_account');
            abort_unless(is_array($registration) && ($registration['phone'] ?? null) === $pending['phone'], 403);
        } else {
            $existingUser = User::query()->findOrFail($pending['user_id'] ?? 0);
            abort_unless($existingUser->phone && app(\App\Services\KenyanPhoneNumber::class)->normalize($existingUser->phone) === $pending['phone'], 403);
        }

        try {
            $codes->send($pending['phone'], $pending['purpose']);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['code' => str_contains($exception->getMessage(), 'Too many') ? $exception->getMessage() : 'We could not send a new code. Check SMS service settings and try again.']);
        }

        return back()->with('status', 'A new code has been sent.');
    }
}
