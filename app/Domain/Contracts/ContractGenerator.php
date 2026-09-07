<?php

namespace App\Domain\Contracts;

use App\Models\Deal;
use Illuminate\Support\Facades\View;

final class ContractGenerator
{
    public const VERSION = 'v1';

    public function render(Deal $deal): string
    {
        $deal->loadMissing(['customer.profile', 'contractor.profile']);

        return trim(View::make('contracts.escrow', [
            'deal' => $deal,
            'customerName' => $deal->customer->displayName(),
            'contractorName' => $deal->contractor?->displayName() ?? $deal->contractor_invite_email,
            'customerTaxId' => $deal->customer->profile?->tax_id,
            'contractorTaxId' => $deal->contractor?->profile?->tax_id,
        ])->render());
    }
}
