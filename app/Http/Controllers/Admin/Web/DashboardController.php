<?php

namespace App\Http\Controllers\Admin\Web;

use App\Enums\DealStatus;
use App\Enums\DisputeStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $usersToday = User::query()->whereDate('created_at', today())->count();
        $dealsToday = Deal::query()->whereDate('created_at', today())->count();
        $disputesToday = Dispute::query()->whereDate('created_at', today())->count();

        $openDisputes = Dispute::query()->whereIn('status', [DisputeStatus::Open, DisputeStatus::InReview])->count();

        $reserved = (int) Payment::query()
            ->where('type', PaymentType::Reserve)
            ->where('status', PaymentStatus::Succeeded)
            ->sum('amount_tenge');

        $payouts = (int) Payment::query()
            ->whereIn('type', [PaymentType::Payout, PaymentType::PartialPayout])
            ->where('status', PaymentStatus::Succeeded)
            ->sum('amount_tenge');

        $refunds = (int) Payment::query()
            ->whereIn('type', [PaymentType::Refund, PaymentType::PartialRefund])
            ->where('status', PaymentStatus::Succeeded)
            ->sum('amount_tenge');

        $dealVolume = (int) Deal::query()->sum('amount_tenge');
        $commissions = (int) Deal::query()->sum('commission_amount_tenge');

        $recentDisputes = Dispute::query()
            ->with(['deal.customer.profile', 'deal.contractor.profile'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'users_today' => $usersToday,
                'deals' => Deal::query()->count(),
                'deals_today' => $dealsToday,
                'open_disputes' => $openDisputes,
                'disputes_today' => $disputesToday,
                'reserved' => $reserved,
            ],
            'finance' => [
                'deal_volume' => $dealVolume,
                'reserves' => $reserved,
                'payouts' => $payouts,
                'refunds' => $refunds,
                'commissions' => $commissions,
            ],
            'recentDisputes' => $recentDisputes,
            'activeDeals' => Deal::query()->whereNotIn('status', [
                DealStatus::Completed, DealStatus::Refunded, DealStatus::Draft, DealStatus::PayoutCompleted,
            ])->count(),
        ]);
    }
}
