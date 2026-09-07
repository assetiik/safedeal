<?php

namespace App\Domain\Payments\DTO;

final readonly class ReserveResult
{
    public function __construct(
        public string $status,
        public ?string $providerPaymentId = null,
        public ?string $redirectUrl = null,
        public array $payload = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
