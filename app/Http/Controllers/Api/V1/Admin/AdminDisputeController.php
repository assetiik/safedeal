<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Deals\DisputeService;
use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResolveDisputeRequest;
use App\Http\Resources\Api\V1\DisputeResource;
use App\Http\Support\ApiPaginator;
use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService $disputes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Dispute::query()->with('deal')->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'open') {
                $query->whereIn('status', [DisputeStatus::Open, DisputeStatus::InReview]);
            } else {
                $query->where('status', $status);
            }
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DisputeResource::class,
            $request,
        );
    }

    public function take(Request $request, Dispute $dispute): JsonResponse
    {
        $dispute = $this->disputes->take($dispute, $request->user());

        return response()->json((new DisputeResource($dispute))->resolve($request));
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        $dispute = $this->disputes->resolve($dispute, $request->user(), $request->validated());

        return response()->json((new DisputeResource($dispute))->resolve($request));
    }
}
