<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null || ! $user->isAdmin() || ! $user->isActive()) {
            Auth::logout();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Доступ только для администратора']);
        }

        return $next($request);
    }
}
