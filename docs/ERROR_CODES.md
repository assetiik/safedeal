# Error codes

| code | HTTP | Когда |
|------|------|-------|
| VALIDATION_ERROR | 400 | Ошибка валидации |
| DEAL_INVALID_TRANSITION | 400 | Нельзя сделать action в статусе |
| INVALID_CREDENTIALS | 401 | Логин |
| UNAUTHENTICATED | 401 | Нет токена |
| INVALID_REFRESH_TOKEN | 401 | Refresh |
| WEBHOOK_INVALID_SIGNATURE | 401 | Webhook |
| FORBIDDEN | 403 | Роль / ACL |
| ACCOUNT_BLOCKED | 403 | Пользователь заблокирован |
| NOT_FOUND | 404 | Нет сущности / чужая (намеренно) |
| PAYMENT_ALREADY_RESERVED | 409 | Повторный reserve |
| CONTRACT_ALREADY_CONFIRMED | 409 | Повтор confirm |
| DISPUTE_ALREADY_OPEN | 409 | Активный спор |
| PARTIAL_SUM_MISMATCH | 400 | partial суммы |
| RATE_LIMITED | 429 | Throttle |
| USE_DISPUTE_ENDPOINT | 422 | open_dispute через actions |

Формат:

```json
{
  "error": {
    "code": "DEAL_INVALID_TRANSITION",
    "message": "Нельзя выполнить действие в текущем статусе",
    "details": { "current_status": "in_progress", "action": "confirm_completion" }
  }
}
```
