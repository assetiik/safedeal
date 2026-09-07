<?php

namespace App\Http\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiPaginator
{
    /**
     * @param  class-string<JsonResource>  $resource
     */
    public static function make(LengthAwarePaginator $paginator, string $resource, Request $request, array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($paginator->items())->resolve($request),
            'meta' => array_merge([
                'page' => $paginator->currentPage(),
                'page_size' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $extraMeta),
        ]);
    }

    public static function pageSize(Request $request): int
    {
        $default = (int) config('escrow.pagination.default', 15);
        $max = (int) config('escrow.pagination.max', 50);

        return min(max($request->integer('page_size', $default), 1), $max);
    }
}
