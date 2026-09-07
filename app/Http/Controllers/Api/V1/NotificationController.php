<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Http\Support\ApiPaginator;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $unread = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            NotificationResource::class,
            $request,
            ['unread_count' => $unread],
        );
    }

    public function read(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json((new NotificationResource($notification->fresh()))->resolve($request));
    }

    public function readAll(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Все уведомления прочитаны']);
    }
}
