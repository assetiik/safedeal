<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DealStatus;
use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DealListResource;
use App\Http\Resources\Api\V1\DisputeResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Deal;
use App\Models\Dispute;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $deals = Deal::query()
            ->with(['customer.profile', 'contractor.profile'])
            ->forUser($user)
            ->whereNotIn('status', [DealStatus::Completed, DealStatus::Refunded, DealStatus::Draft, DealStatus::PayoutCompleted])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $disputes = Dispute::query()
            ->with('deal')
            ->whereHas('deal', fn ($q) => $q->forUser($user))
            ->whereIn('status', [DisputeStatus::Open, DisputeStatus::InReview])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $payments = Payment::query()
            ->with('deal')
            ->whereHas('deal', fn ($q) => $q->forUser($user))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'active_deals' => DealListResource::collection($deals)->resolve($request),
            'disputes' => DisputeResource::collection($disputes)->resolve($request),
            'payments' => PaymentResource::collection($payments)->resolve($request),
        ]);
    }
}
