<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DealResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function show(Request $request, Deal $deal): JsonResponse
    {
        return response()->json($this->payments->summary($deal, $request->user()));
    }

    public function reserve(Request $request, Deal $deal): JsonResponse
    {
        $key = $request->header('Idempotency-Key') ?: $request->header('Idempotency-Key'.'') ?: $request->input('idempotency_key');
        $key = is_string($key) && $key !== '' ? $key : 'reserve:'.$deal->id.':'.$request->user()->id;

        $payment = $this->payments->reserve($deal, $request->user(), $key);
        $deal = $deal->fresh(['customer.profile', 'contractor.profile', 'payments']);

        return response()->json([
            'payment' => (new PaymentResource($payment))->resolve($request),
            'flow_status' => $payment->status->value === 'succeeded' ? 'success' : 'processing',
            'redirect_url' => null,
            'deal' => (new DealResource($deal))->resolve($request),
        ]);
    }

    public function webhook(Request $request, string $provider): JsonResponse
    {
        $payment = $this->payments->handleWebhook($request, $provider);

        return response()->json([
            'ok' => true,
            'payment_id' => $payment->id,
            'status' => $payment->status->value,
        ]);
    }
}
