@extends('admin.layouts.app')

@section('title', 'Дашборд')
@section('heading', 'Админ-панель')

@section('content')
@php
    use App\Support\Money;
    use App\Support\DealCode;
    use App\Support\AdminUi;
    use App\Enums\DisputeStatus;
@endphp

{{-- KPI 2×2 / 4-col like mobile mock --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Пользователи', number_format($stats['users'], 0, ',', ' '), '+'.$stats['users_today'].' за сегодня', 'users'],
        ['Сделки', number_format($stats['deals'], 0, ',', ' '), '+'.$stats['deals_today'].' за сегодня', 'briefcase'],
        ['Открытые споры', number_format($stats['open_disputes'], 0, ',', ' '), '+'.$stats['disputes_today'].' за сегодня', 'flag'],
        ['Сумма в резерве', Money::tenge($stats['reserved']), 'Тенге (KZT)', 'wallet'],
    ] as $i => [$label, $value, $sub, $icon])
        <div class="stat-card" style="animation-delay: {{ $i * 40 }}ms">
            <div class="flex items-start gap-4">
                <div class="icon-bubble bg-brand-50 text-brand-500">
                    @include('admin.partials.icon', ['name' => $icon])
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-medium text-muted">{{ $label }}</div>
                    <div class="mt-1.5 font-display text-[28px] font-bold leading-none tracking-tight">{{ $value }}</div>
                    <div class="mt-2 text-sm font-semibold {{ $i < 3 ? 'text-brand-500' : 'text-muted font-medium' }}">{{ $sub }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Recent disputes --}}
<section class="panel mt-6">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 class="font-display text-lg font-bold">Последние споры</h2>
        <a href="{{ route('admin.disputes.index') }}" class="link-more">Все споры ›</a>
    </div>

    <div class="divide-y divide-line">
        @forelse ($recentDisputes as $dispute)
            @php
                $tone = AdminUi::disputeIconTone($dispute->status);
                $buyer = $dispute->deal->customer?->displayName() ?? '—';
            @endphp
            <a href="{{ route('admin.disputes.show', $dispute) }}" class="flex items-start gap-3.5 px-5 py-4 transition hover:bg-brand-50/50">
                <div class="icon-bubble {{ $tone }} mt-0.5">
                    @if ($dispute->status === DisputeStatus::Resolved)
                        @include('admin.partials.icon', ['name' => 'users'])
                    @elseif ($dispute->status === DisputeStatus::InReview)
                        @include('admin.partials.icon', ['name' => 'briefcase'])
                    @else
                        @include('admin.partials.icon', ['name' => 'flag'])
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                        <div class="min-w-0">
                            <div class="font-semibold text-ink">Спор {{ DealCode::format($dispute->deal) }}</div>
                            <div class="mt-1 text-sm text-muted">Покупатель: {{ $buyer }}</div>
                            <div class="mt-0.5 truncate text-sm text-ink/75">Сделка: {{ $dispute->deal->title }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="font-semibold text-ink">{{ Money::tenge($dispute->deal->amount_tenge) }}</div>
                            <div class="mt-1 text-xs text-muted">{{ AdminUi::adminWhen($dispute->created_at) }}</div>
                            <div class="mt-2">
                                <span class="badge {{ AdminUi::disputeBadge($dispute->status) }}">{{ AdminUi::disputeLabel($dispute->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="px-5 py-12 text-center text-sm text-muted">Споров пока нет</div>
        @endforelse
    </div>
</section>

{{-- Financial activity — horizontal cards --}}
<section class="mt-6">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-display text-lg font-bold">Финансовая активность</h2>
        <a href="{{ route('admin.payments.index') }}" class="link-more">Смотреть отчёт ›</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Объём сделок', $finance['deal_volume'], 'bg-sky-100 text-sky-600', 'grid'],
            ['Пополнения', $finance['reserves'], 'bg-emerald-100 text-emerald-600', 'wallet'],
            ['Выплаты', $finance['payouts'], 'bg-violet-100 text-violet-600', 'briefcase'],
            ['Комиссии', $finance['commissions'], 'bg-orange-100 text-orange-600', 'folder'],
        ] as [$label, $value, $tone, $icon])
            <div class="panel p-5 transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(17,24,39,0.06)]">
                <div class="icon-bubble {{ $tone }} mb-4">
                    @include('admin.partials.icon', ['name' => $icon])
                </div>
                <div class="text-sm font-medium text-muted">{{ $label }}</div>
                <div class="mt-2 font-display text-2xl font-bold tracking-tight">{{ Money::tenge($value) }}</div>
                <div class="mt-2 text-sm font-semibold text-emerald-600">за 7 дней</div>
            </div>
        @endforeach
    </div>
</section>
@endsection
