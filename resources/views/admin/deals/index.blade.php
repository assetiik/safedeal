@extends('admin.layouts.app')

@section('title', 'Сделки')
@section('heading', 'Все сделки')
@section('subheading', 'Управление безопасными сделками сервиса')

@section('content')
@php use App\Support\Money; use App\Support\DealCode; use App\Support\AdminUi; @endphp

<form method="GET" class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
    <input type="search" name="q" value="{{ $q }}" placeholder="Поиск по номеру, услуге или участнику..." class="input lg:max-w-md">
    <div class="flex flex-wrap gap-2">
        @foreach ([
            'all' => 'Все ('.$counts['all'].')',
            'active' => 'Активные ('.$counts['active'].')',
            'completed' => 'Завершённые ('.$counts['completed'].')',
            'dispute' => 'Спор ('.$counts['dispute'].')',
        ] as $key => $label)
            <a href="{{ route('admin.deals.index', ['filter' => $key, 'q' => $q]) }}"
               class="rounded-xl px-3.5 py-2 text-sm font-semibold transition {{ $filter === $key ? ($key === 'dispute' ? 'bg-red-50 text-danger ring-1 ring-red-200' : 'bg-brand-500 text-white') : 'border border-line bg-white text-muted hover:bg-brand-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</form>

<div class="grid gap-4">
    @forelse ($deals as $deal)
        <a href="{{ route('admin.deals.show', $deal) }}" class="panel block p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display text-lg font-semibold">{{ DealCode::format($deal) }}</span>
                        <span class="badge {{ AdminUi::dealBadge($deal->status) }}">{{ $deal->status->labelRu() }}</span>
                    </div>
                    <div class="mt-1 text-sm text-ink">{{ $deal->title }}</div>
                </div>
                <div class="font-display text-xl font-semibold">{{ Money::tenge($deal->amount_tenge) }}</div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                        {{ AdminUi::initials($deal->customer?->displayName()) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ $deal->customer?->displayName() }}</div>
                        <div class="text-xs text-muted">Заказчик</div>
                    </div>
                </div>
                <div class="text-muted">↔</div>
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                        {{ AdminUi::initials($deal->contractor?->displayName() ?? $deal->contractor_invite_email) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ $deal->contractor?->displayName() ?? $deal->contractor_invite_email }}</div>
                        <div class="text-xs text-muted">Исполнитель</div>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="panel px-5 py-12 text-center text-muted">Сделки не найдены</div>
    @endforelse
</div>

<div class="mt-6">{{ $deals->links() }}</div>
@endsection
