<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\DTO\ReserveResult;
use App\Domain\Payments\DTO\TransferResult;
use App\Domain\Payments\DTO\WebhookPayload;
use App\Models\Deal;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentProvider
{
    public function code(): string;

    public function initiateReserve(Deal $deal, Payment $payment): ReserveResult;

    public function initiatePayout(Deal $deal, Payment $payment): TransferResult;

    public function initiateRefund(Deal $deal, Payment $payment): TransferResult;

    public function verifyWebhook(Request $request): WebhookPayload;
}
