<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View { return view('auth.login'); }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        }

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true])) {
            RateLimiter::hit($key, 900);
            return back()->withErrors(['email' => 'These credentials could not be verified.'])->onlyInput('email');
        }

        $user = Auth::user();
        if (! in_array($user?->role, ['admin', 'enforcer'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::clear($key);

            return redirect()->route('login')->withErrors([
                'email' => 'This account needs an Admin or Enforcer role. Ask an admin to update it.',
            ])->withInput($request->only('email'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Audit::record('login');

        return redirect()->route($user->role === 'admin' ? 'admin.dashboard' : 'enforcer.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Audit::record('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
