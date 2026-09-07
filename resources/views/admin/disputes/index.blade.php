@extends('admin.layouts.app')

@section('title', 'Споры')
@section('heading', 'Очередь споров')
@section('subheading', 'Открытые споры, требующие рассмотрения')

@section('content')
@php use App\Support\AdminUi; use App\Support\DealCode; use App\Support\Money; @endphp

<form method="GET" class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
    <input type="search" name="q" value="{{ $q }}" placeholder="Поиск по номеру сделки или участнику" class="input lg:max-w-md">
    <div class="flex flex-wrap gap-2">
        @foreach ([
            'open' => ['Открытые', $counts['open'], true],
            'in_review' => ['В работе', $counts['in_review'], false],
            'resolved' => ['Решённые', $counts['resolved'], false],
        ] as $key => [$label, $count, $alert])
            <a href="{{ route('admin.disputes.index', ['status' => $key, 'q' => $q]) }}"
               @class([
                   'rounded-xl px-3.5 py-2 text-sm font-semibold transition',
                   'bg-red-50 text-danger ring-1 ring-red-200' => $status === $key && $alert,
                   'bg-brand-500 text-white' => $status === $key && ! $alert,
                   'border border-line bg-white text-muted hover:bg-brand-50' => $status !== $key,
               ])>
                {{ $label }} · {{ $count }}
            </a>
        @endforeach
    </div>
</form>

<div class="grid gap-3">
    @forelse ($disputes as $dispute)
        @php $deal = $dispute->deal; @endphp
        <a href="{{ route('admin.disputes.show', $dispute) }}" class="panel block p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-danger">СПОР</span>
                        <span class="font-semibold">{{ DealCode::format($deal) }}</span>
                        <span class="badge {{ AdminUi::disputeBadge($dispute->status) }}">{{ AdminUi::disputeLabel($dispute->status) }}</span>
                    </div>
                    @if ($deal?->title)
                        <div class="mt-1 text-sm text-ink">{{ $deal->title }}</div>
                    @endif
                    <p class="mt-2 max-w-2xl text-sm text-muted">{{ \Illuminate\Support\Str::limit($dispute->reason, 140) }}</p>
                </div>
                <div class="text-right">
                    <div class="font-display text-lg font-semibold">{{ Money::tenge($deal->amount_tenge) }}</div>
                    <div class="text-xs text-muted">{{ $dispute->created_at?->timezone('Asia/Almaty')->format('d.m.Y') }}</div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ AdminUi::avatarTone($deal->customer_user_id ?? 'c') }}">
                        {{ AdminUi::initials($deal->customer?->displayName()) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ $deal->customer?->displayName() ?? '—' }}</div>
                        <div class="text-xs text-muted">Заказчик</div>
                    </div>
                </div>
                <div class="text-muted">↔</div>
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ AdminUi::avatarTone($deal->contractor_user_id ?? ($deal->contractor_invite_email ?? 'x')) }}">
                        {{ AdminUi::initials($deal->contractor?->displayName() ?? $deal->contractor_invite_email) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ $deal->contractor?->displayName() ?? $deal->contractor_invite_email ?? '—' }}</div>
                        <div class="text-xs text-muted">Исполнитель</div>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="panel px-5 py-12 text-center text-muted">Споров в этой очереди нет</div>
    @endforelse
</div>

<div class="mt-6">{{ $disputes->links() }}</div>
@endsection
