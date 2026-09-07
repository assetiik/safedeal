@php
    use App\Support\AdminUi;
    use App\Support\DealCode;
    use App\Support\Money;
@endphp

<div class="icon-bubble {{ AdminUi::paymentIconTone($payment->type) }}">
    @if ($kind === 'reserve')
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
    @elseif ($kind === 'payout')
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
    @endif
</div>

<div class="min-w-0 flex-1">
    <div class="font-semibold text-ink">{{ AdminUi::paymentLabel($payment->type) }}</div>
    @if ($payment->deal)
        <div class="mt-0.5 text-sm text-muted">Сделка {{ DealCode::format($payment->deal) }}</div>
    @endif
    <div class="mt-0.5 text-sm text-muted">
        {{ $payment->created_at?->timezone('Asia/Almaty')->format('d.m.Y H:i') }}
    </div>
</div>

<div class="flex shrink-0 items-center gap-2 sm:gap-3">
    <div class="font-display text-base font-semibold sm:text-lg {{ AdminUi::paymentAmountClass($payment->type) }}">
        {{ Money::signed($payment->amount_tenge, AdminUi::paymentIsIncoming($payment->type)) }}
    </div>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5 text-slate-300">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
    </svg>
</div>
