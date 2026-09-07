<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Deals\DealStateMachine;
use App\Domain\Payments\PaymentService;
use App\Enums\DealStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Http\Support\ApiDate;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deal */
class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $machine = app(DealStateMachine::class);
        $can = $user ? $machine->permissions($this->resource, $user) : [];

        $counterpartyName = null;
        if ($user?->isCustomer()) {
            $counterpartyName = $this->contractor?->displayName() ?? $this->contractor_invite_email;
        } elseif ($user?->isContractor()) {
            $counterpartyName = $this->customer?->displayName();
        }

        $reserve = $this->relationLoaded('payments')
            ? $this->payments->first(fn ($p) => $p->type === PaymentType::Reserve && $p->status === PaymentStatus::Succeeded)
            : app(PaymentService::class)->successfulReserve($this->resource);

        return [
            'id' => $this->id,
            'deal_number' => $this->deal_number,
            'status' => $this->status->value,
            'status_label' => $this->status->labelRu(),
            'title' => $this->title,
            'description' => $this->description,
            'amount_tenge' => $this->amount_tenge,
            'currency' => $this->currency,
            'deadline' => ApiDate::date($this->deadline),
            'terms' => $this->terms,
            'additional_terms' => $this->additional_terms,
            'required_documents' => $this->required_documents ?? [],
            'customer_user_id' => $this->customer_user_id,
            'contractor_user_id' => $this->contractor_user_id,
            'contractor_invite_email' => $this->contractor_invite_email,
            'customer_name' => $this->customer?->displayName(),
            'contractor_name' => $this->contractor?->displayName() ?? $this->contractor_invite_email,
            'counterparty_name' => $counterpartyName,
            'customer_confirmed_contract' => $this->customer_confirmed_contract,
            'contractor_confirmed_contract' => $this->contractor_confirmed_contract,
            'funds_frozen' => $this->funds_frozen,
            'payment_status_label' => $this->paymentStatusLabel($reserve !== null),
            'can' => $can,
            'created_at' => ApiDate::iso($this->created_at),
            'updated_at' => ApiDate::iso($this->updated_at),
        ];
    }

    private function paymentStatusLabel(bool $reserved): string
    {
        if ($this->status === DealStatus::Refunded) {
            return 'refunded';
        }

        if (in_array($this->status, [DealStatus::Completed, DealStatus::PayoutCompleted], true)) {
            return 'paid_out';
        }

        if ($this->funds_frozen || $this->status === DealStatus::Dispute) {
            return 'frozen';
        }

        if ($reserved || in_array($this->status, [DealStatus::MoneyReserved, DealStatus::InProgress, DealStatus::WorkCompleted, DealStatus::AwaitingCustomer], true)) {
            return 'reserved';
        }

        if ($this->status === DealStatus::AwaitingPayment) {
            return 'awaiting';
        }

        return 'none';
    }
}
