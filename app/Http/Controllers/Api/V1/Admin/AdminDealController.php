<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Deals\DealService;
use App\Enums\DealStatus;
use App\Enums\DisputeStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForceStatusRequest;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Http\Resources\Api\V1\DealListResource;
use App\Http\Resources\Api\V1\DealResource;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Http\Support\ApiPaginator;
use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\Dispute;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDealController extends Controller
{
    public function __construct(
        private readonly DealService $deals,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'users_count' => User::query()->count(),
            'active_deals' => Deal::query()->whereNotIn('status', [DealStatus::Completed, DealStatus::Refunded, DealStatus::Draft, DealStatus::PayoutCompleted])->count(),
            'open_disputes' => Dispute::query()->whereIn('status', [DisputeStatus::Open, DisputeStatus::InReview])->count(),
            'reserved_amount_tenge' => (int) Payment::query()->where('type', PaymentType::Reserve)->where('status', PaymentStatus::Succeeded)->sum('amount_tenge'),
            'payouts_amount_tenge' => (int) Payment::query()->whereIn('type', [PaymentType::Payout, PaymentType::PartialPayout])->where('status', PaymentStatus::Succeeded)->sum('amount_tenge'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Deal::query()->with(['customer.profile', 'contractor.profile'])->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%');
                if (ctype_digit($q)) {
                    $inner->orWhere('deal_number', (int) $q);
                }
            });
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DealListResource::class,
            $request,
        );
    }

    public function show(Request $request, Deal $deal): JsonResponse
    {
        return response()->json(
            (new DealResource($deal->load(['customer.profile', 'contractor.profile', 'payments'])))->resolve($request),
        );
    }

    public function forceStatus(ForceStatusRequest $request, Deal $deal): JsonResponse
    {
        $deal = $this->deals->forceStatus(
            $deal,
            $request->user(),
            DealStatus::from($request->string('status')->toString()),
            $request->string('reason')->toString(),
        );

        return response()->json(
            (new DealResource($deal->load(['customer.profile', 'contractor.profile', 'payments'])))->resolve($request),
        );
    }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::query()->with('deal')->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->string('deal_id'));
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            PaymentResource::class,
            $request,
        );
    }

    public function documents(Request $request): JsonResponse
    {
        $query = Document::query()->orderByDesc('created_at');
        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->string('deal_id'));
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DocumentResource::class,
            $request,
        );
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->string('deal_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('actor_user_id', $request->string('user_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            AuditLogResource::class,
            $request,
        );
    }
}
