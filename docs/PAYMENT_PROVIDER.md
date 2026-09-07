# Payment Provider Decision Record (MVP)

| Вопрос | Решение MVP |
|--------|-------------|
| Провайдер | `sandbox` (`App\Domain\Payments\Providers\SandboxPaymentProvider`) |
| Резерв | Instant succeed при `PAYMENT_SANDBOX_AUTO_COMPLETE=true` |
| Где деньги | Логический hold в БД (`payments` type=reserve succeeded). Реальные деньги — у будущего банка |
| Payout | Sandbox instant succeed на реквизиты `profile.bank_details` |
| Refund / partial | Sandbox instant succeed |
| Комиссия | `ESCROW_COMMISSION_RATE_BPS=0` (поле заложено) |
| KYC | Нет в MVP |
| Webhook | `POST /api/v1/payments/webhooks/sandbox` + header `X-Sandbox-Signature` |

## Переход на реальный банк (KZ)

1. Реализовать `PaymentProvider` (например `KaspiPaymentProvider` / `FreedomPayProvider`).
2. В `AppServiceProvider` биндить по `PAYMENT_PROVIDER`.
3. `initiateReserve` возвращает `redirect_url` / widget payload.
4. Статус резерва подтверждать **только** из webhook.
5. Не менять `DealService` / state machine.

## Безопасность

- Карточные данные не храним
- Идемпотентность reserve через `Idempotency-Key` / `payments.idempotency_key`
- Webhook signature обязателен
