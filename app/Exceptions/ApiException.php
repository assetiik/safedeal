<?php

namespace App\Exceptions;

use App\Enums\DealAction;
use App\Enums\DealStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $this->status);
    }

    public static function invalidTransition(DealStatus $status, DealAction|string $action): self
    {
        $actionCode = $action instanceof DealAction ? $action->value : $action;

        return new self(
            'DEAL_INVALID_TRANSITION',
            'Нельзя выполнить действие в текущем статусе',
            400,
            [
                'current_status' => $status->value,
                'action' => $actionCode,
            ],
        );
    }

    public static function forbidden(string $message = 'Недостаточно прав', array $details = []): self
    {
        return new self('FORBIDDEN', $message, 403, $details);
    }

    public static function notFound(string $message = 'Ресурс не найден'): self
    {
        return new self('NOT_FOUND', $message, 404);
    }

    public static function conflict(string $code, string $message, array $details = []): self
    {
        return new self($code, $message, 409, $details);
    }

    public static function unauthorized(string $message = 'Требуется авторизация'): self
    {
        return new self('UNAUTHENTICATED', $message, 401);
    }

    public static function blocked(): self
    {
        return new self('ACCOUNT_BLOCKED', 'Аккаунт заблокирован', 403);
    }

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'details' => empty($this->details) ? (object) [] : $this->details,
            ],
        ], $this->status);
    }
}
