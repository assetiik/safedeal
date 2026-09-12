<?php

namespace App\Domain\Contracts;

use App\Models\Deal;
use Illuminate\Support\Facades\View;

final class ContractGenerator
{
    public const VERSION = 'v2';

    public function render(Deal $deal): string
    {
        $deal->loadMissing(['customer.profile', 'contractor.profile']);

        $customer = $deal->customer;
        $contractor = $deal->contractor;
        $customerProfile = $customer?->profile;
        $contractorProfile = $contractor?->profile;

        $contractorName = $contractor?->displayName();
        if (! filled($contractorName)) {
            $contractorName = filled($deal->contractor_invite_email)
                ? $deal->contractor_invite_email
                : 'определяется после отклика';
        }

        return trim(View::make('contracts.escrow', [
            'deal' => $deal,
            'contractDate' => $deal->created_at?->timezone('Asia/Almaty')->format('d.m.Y') ?? now('Asia/Almaty')->format('d.m.Y'),
            'customerName' => $customer?->displayName() ?? '—',
            'contractorName' => $contractorName,
            'customerTaxId' => $customerProfile?->tax_id,
            'contractorTaxId' => $contractorProfile?->tax_id,
            'customerPhone' => $customerProfile?->phone,
            'contractorPhone' => $contractorProfile?->phone,
            'customerIban' => $customerProfile?->bank_details,
            'contractorIban' => $contractorProfile?->bank_details,
            'customerAddress' => $customerProfile?->legal_address,
            'contractorAddress' => $contractorProfile?->legal_address,
            'customerEmail' => $customer?->email,
            'contractorEmail' => $contractor?->email ?? (filled($deal->contractor_invite_email) ? $deal->contractor_invite_email : null),
        ])->render());
    }
}
