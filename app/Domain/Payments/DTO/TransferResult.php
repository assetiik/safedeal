<?php

namespace App\Domain\Payments\DTO;

final readonly class TransferResult
{
    public function __construct(
        public string $status,
        public ?string $providerPaymentId = null,
        public array $payload = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
