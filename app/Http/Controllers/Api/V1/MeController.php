<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => (new UserResource($request->user()->load('profile')))->resolve($request),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated(),
        );

        $this->audit->record(AuditAction::ProfileUpdated, $user->profile()->first(), $user);

        return response()->json([
            'user' => (new UserResource($user->fresh()->load('profile')))->resolve($request),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword(
            $request->user(),
            $request->string('current_password')->toString(),
            $request->string('new_password')->toString(),
        );

        return response()->json(['message' => 'Пароль изменён']);
    }
}
