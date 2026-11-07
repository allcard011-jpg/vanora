<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS_PER_EMAIL = 5;
    private const MAX_ATTEMPTS_PER_IP = 20;
    private const DECAY_SECONDS = 300;

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $emailKey = 'admin-login:'.Str::lower($credentials['email']).'|'.$request->ip();
        $ipKey = 'admin-login-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($emailKey, self::MAX_ATTEMPTS_PER_EMAIL)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_ATTEMPTS_PER_IP)) {
            $seconds = max(RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey));

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($emailKey, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($emailKey);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
