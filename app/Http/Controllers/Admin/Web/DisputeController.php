<?php

namespace App\Http\Controllers\Admin\Web;

use App\Domain\Deals\DisputeService;
use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService $disputes,
    ) {}

    public function index(Request $request): View
    {
        $query = Dispute::query()
            ->with(['deal.customer.profile', 'deal.contractor.profile', 'openedBy.profile'])
            ->latest();

        $status = $request->string('status', 'open')->toString();
        match ($status) {
            'open' => $query->where('status', DisputeStatus::Open),
            'in_review' => $query->where('status', DisputeStatus::InReview),
            'resolved' => $query->where('status', DisputeStatus::Resolved),
            default => null,
        };

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('reason', 'like', '%'.$q.'%')
                    ->orWhereHas('deal', function ($deal) use ($q) {
                        $deal->where('title', 'like', '%'.$q.'%');
                        if (ctype_digit(ltrim($q, '#'))) {
                            $deal->orWhere('deal_number', (int) ltrim($q, '#'));
                        }
                    });
            });
        }

        return view('admin.disputes.index', [
            'disputes' => $query->paginate(12)->withQueryString(),
            'status' => $status,
            'q' => $q,
            'counts' => [
                'open' => Dispute::query()->where('status', DisputeStatus::Open)->count(),
                'in_review' => Dispute::query()->where('status', DisputeStatus::InReview)->count(),
                'resolved' => Dispute::query()->where('status', DisputeStatus::Resolved)->count(),
            ],
        ]);
    }

    public function show(Dispute $dispute): View
    {
        $dispute->load([
            'deal.customer.profile',
            'deal.contractor.profile',
            'deal.documents',
            'openedBy.profile',
            'events.actor.profile',
            'resolvedBy.profile',
        ]);

        return view('admin.disputes.show', compact('dispute'));
    }

    public function take(Request $request, Dispute $dispute): RedirectResponse
    {
        $this->disputes->take($dispute, $request->user());

        return back()->with('success', 'Спор взят в работу');
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'resolution_type' => ['required', 'in:payout_contractor,refund_customer,partial'],
            'resolution_note' => ['required', 'string', 'min:3'],
            'customer_amount_tenge' => ['nullable', 'integer', 'min:0'],
            'contractor_amount_tenge' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->disputes->resolve($dispute, $request->user(), $data);

        return redirect()
            ->route('admin.disputes.show', $dispute)
            ->with('success', 'Спор решён');
    }
}
