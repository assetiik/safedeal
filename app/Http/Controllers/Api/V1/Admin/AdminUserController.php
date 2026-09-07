<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\UserStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Support\ApiPaginator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('profile')->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($inner) use ($q) {
                $inner->where('email', 'like', '%'.$q.'%')
                    ->orWhereHas('profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%'));
            });
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            UserResource::class,
            $request,
        );
    }

    public function block(Request $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            throw ApiException::forbidden('Нельзя заблокировать администратора');
        }

        $user->update(['status' => UserStatus::Blocked]);
        $user->tokens()->delete();
        $this->audit->record(AuditAction::UserBlocked, $user, $request->user());

        return response()->json((new UserResource($user->fresh()->load('profile')))->resolve($request));
    }

    public function unblock(Request $request, User $user): JsonResponse
    {
        $user->update(['status' => UserStatus::Active]);
        $this->audit->record(AuditAction::UserUnblocked, $user, $request->user());

        return response()->json((new UserResource($user->fresh()->load('profile')))->resolve($request));
    }
}
