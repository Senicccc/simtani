<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'password' => $request->password,
        ];

        $loginValue = $request->login;

        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $loginValue;
        } else {
            $credentials['nomor_anggota'] = $loginValue;
        }

        $user = User::where('status_aktif', true)->where(function ($query) use ($loginValue) {
            $query->where('email', $loginValue)
                ->orWhere('nomor_anggota', $loginValue);
        })->first();

        if (! $user || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Kredensial tidak valid.'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended($user->role === 'admin' ? route('admin.dashboard') : route('anggota.dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    }
}
