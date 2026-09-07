<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasUuids;

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'category',
        'type',
        'title',
        'body',
        'payload',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'type' => NotificationType::class,
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $notification = $this->where($field ?? $this->getRouteKeyName(), $value)->first();

        if ($notification === null) {
            throw ApiException::notFound('Уведомление не найдено');
        }

        $user = auth()->user();

        if ($user instanceof User && $notification->user_id !== $user->id && ! $user->isAdmin()) {
            throw ApiException::notFound('Уведомление не найдено');
        }

        return $notification;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
