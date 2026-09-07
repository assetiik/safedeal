<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\AuthService;
use App\Domain\Auth\TokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly TokenService $tokens,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return response()->json([
            'user' => (new UserResource($result['user']))->resolve($request),
            'tokens' => $result['tokens'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->string('email')->toString(), $request->string('password')->toString());

        return response()->json([
            'user' => (new UserResource($result['user']))->resolve($request),
            'tokens' => $result['tokens'],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => ['required', 'string']]);

        return response()->json([
            'tokens' => $this->tokens->rotate($request->string('refresh_token')->toString()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revokeCurrent($request->user(), $request->input('refresh_token'));

        return response()->json(['message' => 'Выход выполнен']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $this->auth->forgotPassword($request->string('email')->toString());

        return response()->json([
            'message' => 'Если аккаунт существует, мы отправили код на почту',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $min = (int) config('escrow.password_min', 8);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
            'token' => ['nullable', 'string'],
            'new_password' => ['required', 'string', 'min:'.$min],
        ]);

        $code = $data['code'] ?? $data['token'] ?? '';
        $this->auth->resetPassword($data['email'], $code, $data['new_password']);

        return response()->json(['message' => 'Пароль обновлён']);
    }
}
