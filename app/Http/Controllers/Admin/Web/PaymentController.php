<?php

namespace App\Http\Controllers\Admin\Web;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()->with('deal')->latest();

        $type = $request->string('type', 'all')->toString();
        match ($type) {
            'reserve' => $query->where('type', PaymentType::Reserve),
            'payout' => $query->whereIn('type', [PaymentType::Payout, PaymentType::PartialPayout]),
            'refund' => $query->whereIn('type', [PaymentType::Refund, PaymentType::PartialRefund]),
            default => null,
        };

        return view('admin.payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'type' => $type,
        ]);
    }
}
