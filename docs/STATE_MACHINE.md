# State machine (сделки)

## Happy path

```
awaiting_executor
  --accept_invitation--> contract_confirmed
  --confirm_contract (оба)--> awaiting_payment
  --reserve_payment--> money_reserved
  --(auto, если ESCROW_AUTO_START_WORK)--> in_progress
  --mark_work_completed--> work_completed (audit) → awaiting_customer
  --confirm_completion + payout--> completed
```

## Decline

```
awaiting_executor --decline_invitation--> draft
```

## Dispute

```
money_reserved | in_progress | work_completed | awaiting_customer
  --open_dispute--> dispute (funds_frozen=true)
  --admin resolve-->
      payout_contractor → completed
      refund_customer → refunded
      partial → completed (если contractor > 0) иначе refunded
```

## Права

| Action | Роль |
|--------|------|
| create | customer |
| accept / decline / mark_work_completed | contractor |
| reserve / confirm_completion | customer |
| confirm_contract / open_dispute | стороны сделки |
| admin resolve / force-status | admin |

Все переходы идут только через Domain-сервисы + `DealStateMachine`. Прямой `update status` из контроллера запрещён (кроме admin force с audit).
