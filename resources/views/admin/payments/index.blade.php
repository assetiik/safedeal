@extends('admin.layouts.app')

@section('title', 'Финансы')
@section('heading', 'Финансовые операции')

@section('content')
@php
    use App\Support\AdminUi;
    use App\Support\DealCode;
    use App\Support\Money;

    $filters = [
        'all' => ['label' => 'Все', 'icon' => null],
        'reserve' => ['label' => 'Резерв', 'icon' => 'lock'],
        'payout' => ['label' => 'Выплаты', 'icon' => 'payout'],
        'refund' => ['label' => 'Возвраты', 'icon' => 'refund'],
    ];
@endphp

<div class="mb-5 flex flex-wrap gap-2.5">
    @foreach ($filters as $key => $filter)
        <a href="{{ route('admin.payments.index', ['type' => $key]) }}"
           @class([
               'inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition',
               'bg-brand-500 text-white shadow-[0_8px_18px_rgba(59,110,245,0.25)]' => $type === $key,
               'border border-brand-200 bg-white text-brand-500 hover:bg-brand-50' => $type !== $key,
           ])>
            @if ($filter['icon'] === 'lock')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            @elseif ($filter['icon'] === 'payout')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                </svg>
            @elseif ($filter['icon'] === 'refund')
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                </svg>
            @endif
            {{ $filter['label'] }}
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($payments as $payment)
        @php
            $kind = AdminUi::paymentKind($payment->type);
            $href = $payment->deal ? route('admin.deals.show', $payment->deal) : null;
            $cardClass = 'panel flex items-center gap-3.5 p-4 transition hover:-translate-y-0.5 hover:shadow-md sm:gap-4 sm:p-5';
        @endphp

        @if ($href)
            <a href="{{ $href }}" class="{{ $cardClass }}">
                @include('admin.payments._row', compact('payment', 'kind'))
            </a>
        @else
            <div class="{{ $cardClass }}">
                @include('admin.payments._row', compact('payment', 'kind'))
            </div>
        @endif
    @empty
        <div class="panel px-5 py-12 text-center text-muted">Операций пока нет</div>
    @endforelse
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
