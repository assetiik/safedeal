<?php

namespace App\Http\Controllers\Admin\Web;

use App\Domain\Deals\DealService;
use App\Enums\DealStatus;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealController extends Controller
{
    public function __construct(
        private readonly DealService $deals,
    ) {}

    public function index(Request $request): View
    {
        $query = Deal::query()
            ->with(['customer.profile', 'contractor.profile'])
            ->latest('updated_at');

        $filter = $request->string('filter', 'all')->toString();
        match ($filter) {
            'active' => $query->whereNotIn('status', [
                DealStatus::Completed, DealStatus::Draft, DealStatus::Refunded, DealStatus::PayoutCompleted,
            ]),
            'completed' => $query->whereIn('status', [DealStatus::Completed, DealStatus::PayoutCompleted]),
            'dispute' => $query->where('status', DealStatus::Dispute),
            default => null,
        };

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%')
                    ->orWhere('contractor_invite_email', 'like', '%'.$q.'%')
                    ->orWhereHas('customer.profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%'))
                    ->orWhereHas('contractor.profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%'));
                if (ctype_digit(ltrim($q, '#'))) {
                    $inner->orWhere('deal_number', (int) ltrim($q, '#'));
                }
            });
        }

        return view('admin.deals.index', [
            'deals' => $query->paginate(12)->withQueryString(),
            'filter' => $filter,
            'q' => $q,
            'counts' => [
                'all' => Deal::query()->count(),
                'active' => Deal::query()->whereNotIn('status', [
                    DealStatus::Completed, DealStatus::Draft, DealStatus::Refunded, DealStatus::PayoutCompleted,
                ])->count(),
                'completed' => Deal::query()->whereIn('status', [DealStatus::Completed, DealStatus::PayoutCompleted])->count(),
                'dispute' => Deal::query()->where('status', DealStatus::Dispute)->count(),
            ],
        ]);
    }

    public function show(Deal $deal): View
    {
        $deal->load([
            'customer.profile',
            'contractor.profile',
            'contract',
            'payments',
            'documents' => fn ($q) => $q->latest('created_at'),
            'dispute.events',
            'auditLogs' => fn ($q) => $q->latest('created_at')->limit(20),
        ]);

        return view('admin.deals.show', [
            'deal' => $deal,
            'statuses' => DealStatus::cases(),
        ]);
    }

    public function forceStatus(Request $request, Deal $deal): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $this->deals->forceStatus(
            $deal,
            $request->user(),
            DealStatus::from($data['status']),
            $data['reason'],
        );

        return back()->with('success', 'Статус сделки обновлён');
    }
}
