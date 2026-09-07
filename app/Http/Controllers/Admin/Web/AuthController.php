<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        if ($login === 'admin') {
            $login = 'admin@safedeal.test';
        }

        if (! Auth::attempt([
            'email' => $login,
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Неверный логин или пароль'])->onlyInput('login');
        }

        $request->session()->regenerate();

        if (! Auth::user()?->isAdmin() || ! Auth::user()->isActive()) {
            Auth::logout();

            return back()->withErrors(['login' => 'Доступ только для активного администратора']);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
