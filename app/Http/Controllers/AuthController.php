<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those details did not match an account.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended($request->user()->role === 'buyer' ? route('account') : route('farm.dashboard'));
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
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route($user->role === 'farmer' ? 'farm.dashboard' : 'account')->with('status', 'Welcome to Wanguku Housing. Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
