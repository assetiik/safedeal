<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw ApiException::unauthorized();
        }

        if ($roles !== [] && ! in_array($user->role->value, $roles, true)) {
            throw ApiException::forbidden('Недостаточно прав для этого раздела');
        }

        return $next($request);
    }
}
