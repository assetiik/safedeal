<?php

namespace App\Domain\Payments\DTO;

final readonly class WebhookPayload
{
    public function __construct(
        public string $providerPaymentId,
        public string $type,
        public string $status,
        public array $raw = [],
    ) {}
}
