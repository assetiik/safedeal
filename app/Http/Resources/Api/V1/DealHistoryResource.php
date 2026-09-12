<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AuditAction;
use App\Http\Support\ApiDate;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class DealHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $action = is_string($this->action) ? $this->action : (string) $this->action;

        return [
            'id' => $this->id,
            'action' => $action,
            'title' => $this->titleFor($action),
            'actor_name' => $this->actor?->displayName(),
            'actor_user_id' => $this->actor_user_id,
            'payload' => $this->payload ?? (object) [],
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }

    private function titleFor(string $action): string
    {
        return match ($action) {
            AuditAction::DealCreated->value => 'Сделка создана',
            AuditAction::DealAccepted->value => 'Исполнитель принял приглашение',
            AuditAction::DealClaimed->value => 'Исполнитель откликнулся на заказ',
            AuditAction::DealDeclined->value => 'Исполнитель отклонил приглашение',
            AuditAction::DealContractConfirmed->value => 'Договор подтверждён',
            AuditAction::DealWorkStarted->value => 'Работа начата',
            AuditAction::DealWorkCompleted->value => 'Работа отмечена выполненной',
            AuditAction::DealCompletionConfirmed->value => 'Заказчик подтвердил выполнение',
            AuditAction::PaymentReserveInitiated->value => 'Инициирован резерв средств',
            AuditAction::PaymentReserveSucceeded->value => 'Деньги зарезервированы',
            AuditAction::PaymentPayoutSucceeded->value => 'Выплата исполнителю',
            AuditAction::PaymentRefundSucceeded->value => 'Возврат заказчику',
            AuditAction::DisputeOpened->value => 'Открыт спор',
            AuditAction::DisputeTaken->value => 'Спор взят в работу',
            AuditAction::DisputeResolved->value => 'Спор решён',
            AuditAction::DocumentUploaded->value => 'Загружен документ',
            AuditAction::DealStatusForced->value => 'Статус изменён администратором',
            default => $action,
        };
    }
}
