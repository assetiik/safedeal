<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureAdminWeb;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserRole::class,
            'admin.web' => EnsureAdminWeb::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ApiException $e, Request $request): mixed {
            if ($request->is('admin/*') && ! $request->expectsJson()) {
                if ($e->status === 404) {
                    abort(404, $e->getMessage());
                }

                return back()->withErrors(['error' => $e->getMessage()]);
            }

            return $e->toResponse();
        });

        $exceptions->render(function (ValidationException $e, Request $request): mixed {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->validator->errors()->first(),
                    'details' => $e->errors(),
                ],
            ], 400);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request): mixed {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Требуется авторизация',
                    'details' => (object) [],
                ],
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request): mixed {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Ресурс не найден',
                    'details' => (object) [],
                ],
            ], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request): mixed {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Слишком много запросов',
                    'details' => (object) [],
                ],
            ], 429);
        });
    })->create();
