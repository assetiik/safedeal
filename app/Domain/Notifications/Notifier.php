<?php

namespace App\Domain\Notifications;

use App\Enums\NotificationType;
use App\Mail\AppNotificationMail;
use App\Models\AppNotification;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class Notifier
{
    public function send(
        User $user,
        NotificationType $type,
        string $title,
        string $body,
        array $payload = [],
        bool $email = true,
    ): AppNotification {
        $notification = AppNotification::query()->create([
            'user_id' => $user->id,
            'category' => $type->category(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
        ]);

        if ($email) {
            Mail::to($user->email)->send(new AppNotificationMail($title, $body));
        }

        return $notification;
    }

    public function dealParties(Deal $deal, NotificationType $type, string $title, string $body, array $payload = []): void
    {
        $payload = array_merge(['deal_id' => $deal->id, 'deal_number' => $deal->deal_number], $payload);

        $this->send($deal->customer, $type, $title, $body, $payload);

        if ($deal->contractor) {
            $this->send($deal->contractor, $type, $title, $body, $payload);
        }
    }
}
