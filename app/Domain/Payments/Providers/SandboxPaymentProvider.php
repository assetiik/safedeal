<?php

namespace App\Domain\Payments\Providers;

use App\Domain\Payments\Contracts\PaymentProvider;
use App\Domain\Payments\DTO\ReserveResult;
use App\Domain\Payments\DTO\TransferResult;
use App\Domain\Payments\DTO\WebhookPayload;
use App\Exceptions\ApiException;
use App\Models\Deal;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SandboxPaymentProvider implements PaymentProvider
{
    public function code(): string
    {
        return 'sandbox';
    }

    public function initiateReserve(Deal $deal, Payment $payment): ReserveResult
    {
        $providerId = 'sandbox_reserve_'.$payment->id;

        if (config('escrow.payment.sandbox.auto_complete')) {
            return new ReserveResult(
                status: 'succeeded',
                providerPaymentId: $providerId,
                redirectUrl: null,
                payload: ['mode' => 'auto_complete'],
            );
        }

        return new ReserveResult(
            status: 'pending',
            providerPaymentId: $providerId,
            redirectUrl: url('/sandbox/pay/'.$payment->id),
            payload: ['mode' => 'redirect'],
        );
    }

    public function initiatePayout(Deal $deal, Payment $payment): TransferResult
    {
        return new TransferResult(
            status: 'succeeded',
            providerPaymentId: 'sandbox_payout_'.$payment->id,
            payload: ['iban' => $deal->contractor?->profile?->bank_details],
        );
    }

    public function initiateRefund(Deal $deal, Payment $payment): TransferResult
    {
        return new TransferResult(
            status: 'succeeded',
            providerPaymentId: 'sandbox_refund_'.$payment->id,
        );
    }

    public function verifyWebhook(Request $request): WebhookPayload
    {
        $secret = (string) config('escrow.payment.sandbox.webhook_secret');
        $header = (string) $request->header('X-Sandbox-Signature', '');

        if ($secret === '' || ! hash_equals($secret, $header)) {
            throw new ApiException('WEBHOOK_INVALID_SIGNATURE', 'Неверная подпись webhook', 401);
        }

        $providerPaymentId = (string) $request->input('provider_payment_id', '');
        $status = (string) $request->input('status', '');
        $type = (string) $request->input('type', 'reserve');

        if ($providerPaymentId === '' || $status === '') {
            throw new ApiException('WEBHOOK_INVALID_PAYLOAD', 'Некорректное тело webhook', 400);
        }

        return new WebhookPayload(
            providerPaymentId: $providerPaymentId,
            type: $type,
            status: $status,
            raw: $request->all(),
        );
    }

    public static function fakeIdempotencyKey(): string
    {
        return (string) Str::uuid();
    }
}
