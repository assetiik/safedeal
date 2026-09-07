@extends('admin.layouts.app')

@section('title', 'Спор')
@section('heading', 'Спор по сделке '.\App\Support\DealCode::short($dispute->deal))
@section('subheading', $dispute->deal->title)

@section('actions')
    <a href="{{ route('admin.disputes.index') }}" class="btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Назад
    </a>
@endsection

@section('content')
@php
    use App\Support\AdminUi;
    use App\Support\DealCode;
    use App\Support\Money;
    use App\Enums\DisputeStatus;

    $deal = $dispute->deal;
    $code = DealCode::format($deal);
    $reserved = method_exists($deal, 'reservedAmount') ? $deal->reservedAmount() : $deal->amount_tenge;
@endphp

<div class="mx-auto max-w-5xl space-y-5" x-data="{ tab: 'details', type: 'payout_contractor' }">
    <div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ AdminUi::disputeBadge($dispute->status) }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
            </svg>
            Статус спора: {{ AdminUi::disputeLabel($dispute->status) }}
        </span>
    </div>

    {{-- Deal info --}}
    <section class="panel p-5 sm:p-6">
        <div class="mb-4 flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-bold">Информация о сделке</h2>
        </div>

        <div class="divide-y divide-line">
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">ID сделки</span>
                <a href="{{ route('admin.deals.show', $deal) }}" class="text-sm font-semibold text-brand-600 hover:underline">{{ $code }}</a>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
                <span class="text-sm text-muted">Название</span>
                <span class="max-w-[65%] text-right text-sm font-semibold text-ink">{{ $deal->title }}</span>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Заказчик</span>
                <a href="{{ route('admin.users.show', $deal->customer) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink hover:text-brand-600">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold {{ AdminUi::avatarTone($deal->customer_user_id) }}">
                        {{ AdminUi::initials($deal->customer?->displayName()) }}
                    </span>
                    {{ $deal->customer?->displayName() ?? '—' }}
                </a>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Исполнитель</span>
                @if ($deal->contractor)
                    <a href="{{ route('admin.users.show', $deal->contractor) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink hover:text-brand-600">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold {{ AdminUi::avatarTone($deal->contractor_user_id) }}">
                            {{ AdminUi::initials($deal->contractor->displayName()) }}
                        </span>
                        {{ $deal->contractor->displayName() }}
                    </a>
                @else
                    <span class="text-sm font-semibold">{{ $deal->contractor_invite_email ?: '—' }}</span>
                @endif
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Сумма сделки</span>
                <span class="text-sm font-semibold">{{ Money::tenge($deal->amount_tenge) }}</span>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Сумма в депозите</span>
                <span class="text-sm font-semibold">{{ Money::tenge($reserved) }}</span>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Дата создания</span>
                <span class="text-sm font-semibold">{{ $deal->created_at?->timezone('Asia/Almaty')->format('d.m.Y, H:i') }}</span>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm text-muted">Срок выполнения</span>
                <span class="text-sm font-semibold">{{ $deal->deadline?->format('d.m.Y') ?? '—' }}</span>
            </div>
        </div>
    </section>

    {{-- Reason --}}
    <section class="panel p-5 sm:p-6">
        <div class="mb-3 flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                </svg>
            </div>
            <h2 class="font-display text-lg font-bold">Причина спора</h2>
        </div>
        <p class="whitespace-pre-wrap text-sm leading-relaxed text-ink">{{ $dispute->reason }}</p>
        <div class="mt-3 text-xs text-muted">
            Открыл: {{ $dispute->openedBy?->displayName() ?? '—' }} · {{ $dispute->created_at?->timezone('Asia/Almaty')->format('d.m.Y, H:i') }}
        </div>
    </section>

    {{-- Tabs: details / documents / history --}}
    <section class="panel overflow-hidden">
        <div class="flex gap-1 border-b border-line px-2 pt-2 sm:px-4">
            @foreach (['details' => 'Детали', 'documents' => 'Документы', 'history' => 'История'] as $key => $label)
                <button
                    type="button"
                    @click="tab = '{{ $key }}'"
                    class="relative px-4 py-3 text-sm font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'text-brand-600' : 'text-muted hover:text-ink'"
                >
                    {{ $label }}
                    <span
                        class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-brand-500"
                        x-show="tab === '{{ $key }}'"
                        x-cloak
                    ></span>
                </button>
            @endforeach
        </div>

        <div class="p-5 sm:p-6" x-show="tab === 'details'" x-cloak>
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.491Z" />
                    </svg>
                </div>
                <h3 class="font-display text-base font-bold">Стороны спора</h3>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('admin.users.show', $deal->customer) }}" class="flex items-center rounded-2xl bg-surface px-4 py-3.5 transition hover:bg-brand-50" style="gap: 12px;">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ AdminUi::avatarTone($deal->customer_user_id) }}">
                        {{ AdminUi::initials($deal->customer?->displayName()) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-muted">Заказчик</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->customer?->displayName() ?? '—' }}</div>
                    </div>
                </a>

                @if ($deal->contractor)
                    <a href="{{ route('admin.users.show', $deal->contractor) }}" class="flex items-center rounded-2xl bg-surface px-4 py-3.5 transition hover:bg-brand-50" style="gap: 12px;">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ AdminUi::avatarTone($deal->contractor_user_id) }}">
                            {{ AdminUi::initials($deal->contractor->displayName()) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted">Исполнитель</div>
                            <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->contractor->displayName() }}</div>
                        </div>
                    </a>
                @else
                    <div class="flex items-center rounded-2xl bg-surface px-4 py-3.5" style="gap: 12px;">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-sm font-bold">?</div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted">Исполнитель</div>
                            <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->contractor_invite_email ?: 'Не назначен' }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <p class="mt-4 text-sm text-muted">
                Доказательства сторон в MVP отображаются через документы сделки во вкладке «Документы».
            </p>
        </div>

        <div class="p-5 sm:p-6" x-show="tab === 'documents'" x-cloak>
            <div class="space-y-2">
                @forelse ($deal->documents as $doc)
                    <a href="{{ route('admin.documents.download', $doc) }}" class="flex items-center justify-between rounded-2xl bg-surface px-4 py-3.5 text-sm transition hover:bg-brand-50">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                            </div>
                            <span class="truncate font-medium">{{ $doc->title }}</span>
                        </div>
                        <span class="shrink-0 text-muted">{{ number_format($doc->size_bytes / 1024, 0) }} КБ</span>
                    </a>
                @empty
                    <div class="py-8 text-center text-sm text-muted">Документов нет</div>
                @endforelse
            </div>
        </div>

        <div class="p-5 sm:p-6" x-show="tab === 'history'" x-cloak>
            <div class="space-y-3">
                @forelse ($dispute->events as $event)
                    <div class="rounded-2xl bg-surface px-4 py-3 text-sm">
                        <div class="font-semibold text-ink">{{ $event->type }}</div>
                        <div class="mt-1 text-xs text-muted">
                            {{ $event->actor?->displayName() ?? 'Система' }} · {{ $event->created_at?->timezone('Asia/Almaty')->format('d.m.Y, H:i') }}
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-muted">История пуста</div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($dispute->resolution_note)
        <section class="panel border-emerald-200 bg-emerald-50/60 p-5 sm:p-6">
            <h2 class="font-display text-lg font-bold text-emerald-800">Решение</h2>
            <p class="mt-2 text-sm text-emerald-900">{{ $dispute->resolution_note }}</p>
            <div class="mt-3 text-xs text-emerald-700">
                {{ $dispute->resolution_type?->value }} · заказчику {{ Money::tenge($dispute->customer_amount_tenge) }} · исполнителю {{ Money::tenge($dispute->contractor_amount_tenge) }}
            </div>
        </section>
    @endif

    {{-- Admin actions --}}
    @if ($dispute->status->isActive())
        @if ($dispute->status === DisputeStatus::Open)
            <form method="POST" action="{{ route('admin.disputes.take', $dispute) }}">
                @csrf
                <button class="btn-ghost w-full py-3.5">Взять в работу</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="resolution_type" :value="type">

            <button type="button" @click="type = 'payout_contractor'"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-4 text-left transition"
                    :class="type === 'payout_contractor' ? 'bg-brand-500 text-white shadow-[0_8px_20px_rgba(59,110,245,0.28)]' : 'border border-line bg-white hover:bg-brand-50'">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                     :class="type === 'payout_contractor' ? 'bg-white/20' : 'bg-brand-50 text-brand-500'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold" :class="type === 'payout_contractor' ? 'text-white' : 'text-brand-700'">Выплатить исполнителю</div>
                    <div class="mt-0.5 text-xs" :class="type === 'payout_contractor' ? 'text-white/80' : 'text-muted'">Перевести всю сумму сделки исполнителю</div>
                </div>
            </button>

            <button type="button" @click="type = 'refund_customer'"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-4 text-left transition"
                    :class="type === 'refund_customer' ? 'border-2 border-rose-500 bg-rose-50' : 'border border-rose-200 bg-white hover:bg-rose-50'">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold text-rose-600">Вернуть заказчику</div>
                    <div class="mt-0.5 text-xs text-muted">Вернуть всю сумму сделки заказчику</div>
                </div>
            </button>

            <button type="button" @click="type = 'partial'"
                    class="flex w-full items-center gap-3 rounded-2xl px-4 py-4 text-left transition"
                    :class="type === 'partial' ? 'border-2 border-brand-500 bg-brand-50' : 'border border-brand-200 bg-white hover:bg-brand-50'">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold text-brand-700">Частичное решение</div>
                    <div class="mt-0.5 text-xs text-muted">Выплатить часть исполнителю и вернуть часть заказчику</div>
                </div>
            </button>

            <div x-show="type === 'partial'" class="grid grid-cols-2 gap-3" x-cloak>
                <div>
                    <label class="mb-1.5 block text-xs text-muted">Заказчику, ₸</label>
                    <input type="number" name="customer_amount_tenge" min="0" class="input" value="0">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs text-muted">Исполнителю, ₸</label>
                    <input type="number" name="contractor_amount_tenge" min="0" class="input" value="{{ $reserved }}">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium">Комментарий для сторон</label>
                <textarea name="resolution_note" rows="3" class="input" required placeholder="Текст решения"></textarea>
            </div>

            <button class="btn-primary w-full py-3.5">Применить решение</button>
        </form>
    @endif
</div>
@endsection
