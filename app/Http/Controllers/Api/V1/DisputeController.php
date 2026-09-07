<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Deals\DisputeService;
use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OpenDisputeRequest;
use App\Http\Resources\Api\V1\DisputeResource;
use App\Http\Support\ApiPaginator;
use App\Models\Deal;
use App\Models\Dispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService $disputes,
    ) {}

    public function store(OpenDisputeRequest $request, Deal $deal): JsonResponse
    {
        $dispute = $this->disputes->open($deal, $request->user(), trim($request->string('reason')->toString()));

        return response()->json(
            (new DisputeResource($dispute->load(['deal', 'events'])))->resolve($request),
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $query = Dispute::query()
            ->with('deal')
            ->whereHas('deal', fn ($q) => $q->forUser($request->user()))
            ->orderByDesc('updated_at');

        $filter = $request->string('filter')->toString();
        match ($filter) {
            'open' => $query->whereIn('status', [DisputeStatus::Open, DisputeStatus::InReview]),
            'resolved' => $query->where('status', DisputeStatus::Resolved),
            default => null,
        };

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DisputeResource::class,
            $request,
        );
    }

    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        $dispute->load(['deal.documents', 'events', 'openedBy.profile']);

        return response()->json((new DisputeResource($dispute))->resolve($request));
    }
}
