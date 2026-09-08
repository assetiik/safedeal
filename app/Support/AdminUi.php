<?php

namespace App\Support;

use App\Enums\DealStatus;
use App\Enums\DisputeStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;

final class AdminUi
{
    public static function dealBadge(DealStatus $status): string
    {
        return match ($status) {
            DealStatus::Completed, DealStatus::PayoutCompleted => 'badge-ok',
            DealStatus::Dispute => 'badge-danger',
            DealStatus::Refunded, DealStatus::Draft => 'badge-muted',
            DealStatus::AwaitingCustomer, DealStatus::AwaitingPayment, DealStatus::AwaitingExecutor => 'badge-info',
            DealStatus::InProgress, DealStatus::MoneyReserved, DealStatus::WorkCompleted, DealStatus::ContractConfirmed => 'badge-warn',
        };
    }

    public static function disputeBadge(DisputeStatus $status): string
    {
        return match ($status) {
            DisputeStatus::Open => 'badge-info',
            DisputeStatus::InReview => 'badge-warn',
            DisputeStatus::Resolved => 'badge-ok',
        };
    }

    public static function disputeLabel(DisputeStatus $status): string
    {
        return match ($status) {
            DisputeStatus::Open => 'Ожидает ответа',
            DisputeStatus::InReview => 'На рассмотрении',
            DisputeStatus::Resolved => 'Решён',
        };
    }

    public static function disputeIconTone(DisputeStatus $status): string
    {
        return match ($status) {
            DisputeStatus::Open => 'bg-sky-100 text-sky-600',
            DisputeStatus::InReview => 'bg-amber-100 text-amber-600',
            DisputeStatus::Resolved => 'bg-emerald-100 text-emerald-600',
        };
    }

    public static function userBadge(UserStatus $status): string
    {
        return $status === UserStatus::Active ? 'badge-ok' : 'badge-danger';
    }

    public static function userStatusLabel(UserStatus $status): string
    {
        return $status === UserStatus::Active ? 'Активен' : 'Заблокирован';
    }

    public static function roleLabel(UserRole $role): string
    {
        return match ($role) {
            UserRole::Customer => 'Заказчик',
            UserRole::Contractor => 'Исполнитель',
            UserRole::Admin => 'Админ',
        };
    }

    public static function paymentLabel(PaymentType $type): string
    {
        return match ($type) {
            PaymentType::Reserve => 'Резерв',
            PaymentType::Payout, PaymentType::PartialPayout => 'Выплата исполнителю',
            PaymentType::Refund, PaymentType::PartialRefund => 'Возврат заказчику',
        };
    }

    public static function documentLabel(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::Contract => 'Договор',
            DocumentType::Technical => 'Технический',
            DocumentType::Act => 'Акт',
            DocumentType::Other => 'Прочее',
        };
    }

    public static function paymentKind(PaymentType $type): string
    {
        return match ($type) {
            PaymentType::Reserve => 'reserve',
            PaymentType::Payout, PaymentType::PartialPayout => 'payout',
            PaymentType::Refund, PaymentType::PartialRefund => 'refund',
        };
    }

    public static function paymentIconTone(PaymentType $type): string
    {
        return match (self::paymentKind($type)) {
            'reserve' => 'bg-brand-50 text-brand-500',
            'payout' => 'bg-emerald-50 text-emerald-600',
            'refund' => 'bg-orange-50 text-orange-500',
        };
    }

    public static function paymentAmountClass(PaymentType $type): string
    {
        return match (self::paymentKind($type)) {
            'refund' => 'text-orange-500',
            default => 'text-ok',
        };
    }

    public static function paymentIsIncoming(PaymentType $type): bool
    {
        return match (self::paymentKind($type)) {
            'payout' => false,
            default => true,
        };
    }

    public static function fileExtension(?string $fileName): string
    {
        $ext = strtoupper((string) pathinfo((string) $fileName, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : 'FILE';
    }

    public static function fileIconTone(?string $fileName): string
    {
        return match (strtolower(self::fileExtension($fileName))) {
            'pdf' => 'bg-rose-50 text-rose-600',
            'xls', 'xlsx', 'csv' => 'bg-emerald-50 text-emerald-600',
            'doc', 'docx' => 'bg-sky-50 text-sky-600',
            'zip', 'rar', '7z' => 'bg-slate-100 text-slate-700',
            'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bg-violet-50 text-violet-600',
            'txt' => 'bg-orange-50 text-orange-600',
            default => 'bg-brand-50 text-brand-600',
        };
    }

    public static function fileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            $value = $bytes / 1_048_576;
            $formatted = rtrim(rtrim(number_format($value, 1, ',', ' '), '0'), ',');

            return $formatted.' МБ';
        }

        if ($bytes >= 1024) {
            return number_format((int) round($bytes / 1024), 0, ',', ' ').' КБ';
        }

        return $bytes.' Б';
    }

    public static function auditActionLabel(string $action): string
    {
        return match ($action) {
            'user.registered' => 'зарегистрировался в системе',
            'user.logged_in' => 'выполнил вход в систему',
            'user.blocked' => 'заблокировал пользователя',
            'user.unblocked' => 'разблокировал пользователя',
            'profile.updated' => 'обновил профиль',
            'deal.created' => 'создал сделку',
            'deal.accepted' => 'принял приглашение в сделку',
            'deal.declined' => 'отклонил приглашение в сделку',
            'deal.claimed' => 'откликнулся на открытый заказ',
            'deal.contract_confirmed' => 'подтвердил договор',
            'deal.status_forced' => 'изменил статус сделки',
            'deal.work_started' => 'начал работу по сделке',
            'deal.work_completed' => 'отметил работу выполненной',
            'deal.completion_confirmed' => 'подтвердил завершение сделки',
            'payment.reserve_initiated' => 'инициировал резерв средств',
            'payment.reserve_succeeded' => 'зарезервировал средства',
            'payment.payout_succeeded' => 'выполнил выплату исполнителю',
            'payment.refund_succeeded' => 'выполнил возврат заказчику',
            'dispute.opened' => 'открыл спор',
            'dispute.taken' => 'взял спор в работу',
            'dispute.resolved' => 'разрешил спор',
            'document.uploaded' => 'загрузил документ',
            'document.generated' => 'сгенерировал документ',
            default => $action,
        };
    }

    public static function auditStatusLabel(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return DealStatus::tryFrom($status)?->labelRu() ?? $status;
    }

    public static function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($first.$second);
    }

    public static function avatarTone(string|int $seed): string
    {
        $tones = [
            'bg-sky-100 text-sky-700',
            'bg-emerald-100 text-emerald-700',
            'bg-violet-100 text-violet-700',
            'bg-amber-100 text-amber-700',
            'bg-rose-100 text-rose-700',
            'bg-cyan-100 text-cyan-700',
            'bg-indigo-100 text-indigo-700',
            'bg-orange-100 text-orange-700',
        ];

        $hash = crc32((string) $seed);

        return $tones[$hash % count($tones)];
    }

    public static function adminWhen(?\Carbon\CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        $local = $date->timezone('Asia/Almaty');
        $time = $local->format('H:i');

        if ($local->isToday()) {
            return "Сегодня, {$time}";
        }

        if ($local->isYesterday()) {
            return "Вчера, {$time}";
        }

        return $local->format('d.m.Y, H:i');
    }

    /**
     * @return list<array{key: string, label: string, done: bool, current: bool}>
     */
    public static function dealSteps(DealStatus $status): array
    {
        $order = [
            'created' => [DealStatus::AwaitingExecutor],
            'contract' => [DealStatus::ContractConfirmed, DealStatus::AwaitingPayment],
            'reserved' => [DealStatus::MoneyReserved, DealStatus::InProgress],
            'work' => [DealStatus::WorkCompleted, DealStatus::AwaitingCustomer],
            'done' => [DealStatus::Completed, DealStatus::PayoutCompleted, DealStatus::Refunded],
        ];

        $labels = [
            'created' => 'Создана',
            'contract' => 'Договор',
            'reserved' => 'Депозит',
            'work' => 'Выполнение',
            'done' => 'Завершение',
        ];

        $currentIndex = 0;
        $keys = array_keys($order);
        foreach ($keys as $i => $key) {
            if (in_array($status, $order[$key], true) || ($status === DealStatus::Dispute && $i >= 2)) {
                $currentIndex = $i;
            }
            if (in_array($status, [DealStatus::Completed, DealStatus::PayoutCompleted, DealStatus::Refunded], true)) {
                $currentIndex = 4;
            }
        }

        // Progress by advancing through statuses
        $progress = match ($status) {
            DealStatus::Draft, DealStatus::AwaitingExecutor => 0,
            DealStatus::ContractConfirmed => 1,
            DealStatus::AwaitingPayment => 1,
            DealStatus::MoneyReserved, DealStatus::InProgress, DealStatus::Dispute => 2,
            DealStatus::WorkCompleted, DealStatus::AwaitingCustomer => 3,
            DealStatus::Completed, DealStatus::PayoutCompleted, DealStatus::Refunded => 4,
        };

        return collect($labels)->map(function (string $label, string $key) use ($progress, $keys) {
            $index = array_search($key, $keys, true);

            return [
                'key' => $key,
                'label' => $label,
                'done' => $index < $progress,
                'current' => $index === $progress,
            ];
        })->values()->all();
    }
}
