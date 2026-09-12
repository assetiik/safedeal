@extends('admin.layouts.app')

@section('title', 'Сделка')
@section('heading', 'Сделка '.\App\Support\DealCode::short($deal))
@section('subheading', $deal->title)

@section('actions')
    <a href="{{ route('admin.deals.index') }}" class="btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Назад
    </a>
@endsection

@section('content')
@php
    use App\Support\Money;
    use App\Support\AdminUi;
    use App\Support\DealCode;
    use App\Enums\DealStatus;

    $code = DealCode::format($deal);
    $steps = AdminUi::dealSteps($deal->status);
    $guaranteeActive = in_array($deal->status, [
        DealStatus::MoneyReserved,
        DealStatus::InProgress,
        DealStatus::WorkCompleted,
        DealStatus::AwaitingCustomer,
        DealStatus::Dispute,
    ], true) || $deal->funds_frozen;

    $deadlineLabel = $deal->deadline
        ? $deal->deadline->timezone('Asia/Almaty')->locale('ru')->translatedFormat('d F Y')
        : null;
    $daysLeft = $deal->deadline ? (int) now()->startOfDay()->diffInDays($deal->deadline->startOfDay(), false) : null;
@endphp

<div class="mx-auto max-w-5xl space-y-5">
    {{-- Summary card --}}
    <section class="panel p-5 sm:p-6" x-data="{ copied: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($guaranteeActive)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                    Гарантия активна
                </span>
            @else
                <span class="badge {{ AdminUi::dealBadge($deal->status) }}">{{ $deal->status->labelRu() }}</span>
            @endif

            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-brand-600"
                @click="navigator.clipboard.writeText(@js($code)); copied = true; setTimeout(() => copied = false, 1500)"
            >
                <span x-text="copied ? 'Скопировано' : 'ID сделки {{ $code }}'"></span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-brand-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
            </button>
        </div>

        <div class="mt-5">
            <div class="text-sm text-muted">Сумма сделки</div>
            <div class="mt-1 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ Money::tenge($deal->amount_tenge) }}</div>
            <div class="mt-1 text-sm italic text-muted">{{ Money::tengeWords($deal->amount_tenge) }}</div>
        </div>

        <div class="mt-5 grid gap-3 border-t border-line pt-5 sm:grid-cols-2">
            <a href="{{ route('admin.users.show', $deal->customer) }}" class="flex items-center rounded-2xl bg-surface px-4 py-3.5 transition hover:bg-brand-50" style="gap: 12px;">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ AdminUi::avatarTone($deal->customer_user_id) }} text-sm font-bold">
                    {{ AdminUi::initials($deal->customer?->displayName()) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-muted">Заказчик</div>
                    <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->customer?->displayName() ?? '—' }}</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0 text-muted">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>

            @if ($deal->contractor)
                <a href="{{ route('admin.users.show', $deal->contractor) }}" class="flex items-center rounded-2xl bg-surface px-4 py-3.5 transition hover:bg-brand-50" style="gap: 12px;">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ AdminUi::avatarTone($deal->contractor_user_id) }} text-sm font-bold">
                        {{ AdminUi::initials($deal->contractor->displayName()) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-muted">Исполнитель</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->contractor->displayName() }}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0 text-muted">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <div class="flex items-center rounded-2xl bg-surface px-4 py-3.5" style="gap: 12px;">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-muted">Исполнитель</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">{{ $deal->contractor_invite_email ?: 'Не назначен' }}</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
            <div class="flex items-center text-sm text-ink" style="gap: 10px;">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs text-muted">Дедлайн сделки</div>
                    <div class="mt-0.5 font-semibold">{{ $deadlineLabel ?? 'Не указан' }}</div>
                </div>
            </div>

            @if ($daysLeft !== null)
                <span @class([
                    'rounded-full px-3 py-1.5 text-xs font-semibold',
                    'bg-brand-50 text-brand-600' => $daysLeft > 0,
                    'bg-amber-50 text-amber-700' => $daysLeft === 0,
                    'bg-rose-50 text-rose-700' => $daysLeft < 0,
                ])>
                    @if ($daysLeft > 0)
                        Осталось {{ $daysLeft }} {{ $daysLeft === 1 ? 'день' : ($daysLeft < 5 ? 'дня' : 'дней') }}
                    @elseif ($daysLeft === 0)
                        Сегодня
                    @else
                        Просрочено на {{ abs($daysLeft) }} {{ abs($daysLeft) === 1 ? 'день' : (abs($daysLeft) < 5 ? 'дня' : 'дней') }}
                    @endif
                </span>
            @endif
        </div>

        @if ($deal->funds_frozen)
            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                Средства заморожены
            </div>
        @endif
    </section>

    {{-- Status stepper --}}
    <section class="panel p-5 sm:p-6">
        <h2 class="font-display text-lg font-bold">Статус сделки</h2>
        <div class="mt-5 overflow-x-auto pb-1">
            <div class="flex min-w-[520px] items-start justify-between gap-1">
                @foreach ($steps as $i => $step)
                    <div class="relative flex flex-1 flex-col items-center text-center">
                        @if ($i > 0)
                            <div @class([
                                'absolute top-4 right-1/2 h-0.5 w-full -translate-y-1/2',
                                'bg-emerald-500' => $step['done'] || $step['current'],
                                'bg-slate-200' => ! $step['done'] && ! $step['current'],
                            ]) style="z-index: 0;"></div>
                        @endif
                        <div @class([
                            'relative z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold',
                            'bg-emerald-500 text-white' => $step['done'],
                            'bg-brand-500 text-white ring-4 ring-brand-100' => $step['current'],
                            'bg-slate-100 text-slate-400' => ! $step['done'] && ! $step['current'],
                        ])>
                            @if ($step['done'])
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>
                        <div @class([
                            'mt-2 text-xs font-semibold',
                            'text-emerald-700' => $step['done'],
                            'text-brand-600' => $step['current'],
                            'text-muted' => ! $step['done'] && ! $step['current'],
                        ])>{{ $step['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="badge {{ AdminUi::dealBadge($deal->status) }}">{{ $deal->status->labelRu() }}</span>
            <span class="text-sm text-muted">Обновлено {{ AdminUi::adminWhen($deal->updated_at) }}</span>
        </div>
    </section>

    {{-- Menu links --}}
    <section class="panel overflow-hidden" x-data="{ termsOpen: false }">
        @if ($deal->contract)
            <a href="{{ route('admin.documents.index', ['deal_id' => $deal->id, 'type' => 'contract']) }}" class="flex items-center border-b border-line px-5 py-4 transition hover:bg-brand-50/40" style="gap: 14px;">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                </div>
                <div class="min-w-0 flex-1 text-sm font-semibold">Договор</div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-muted"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @endif

        <a href="{{ route('admin.payments.index') }}" class="flex items-center border-b border-line px-5 py-4 transition hover:bg-brand-50/40" style="gap: 14px;">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
            </div>
            <div class="min-w-0 flex-1 text-sm font-semibold">Оплата</div>
            <span class="text-sm text-muted">{{ $deal->payments->count() }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-muted"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        </a>

        <button type="button" @click="termsOpen = !termsOpen" class="flex w-full items-center border-b border-line px-5 py-4 text-left transition hover:bg-brand-50/40" style="gap: 14px;">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
            </div>
            <div class="min-w-0 flex-1 text-sm font-semibold">Условия сделки</div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-muted transition" :class="termsOpen && 'rotate-90'"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        </button>
        <div x-cloak x-show="termsOpen" x-transition class="border-b border-line bg-surface px-5 py-4 text-sm text-ink">
            <p class="whitespace-pre-wrap">{{ $deal->terms ?: 'Условия не указаны' }}</p>
            @if ($deal->additional_terms)
                <p class="mt-3 whitespace-pre-wrap text-muted">{{ $deal->additional_terms }}</p>
            @endif
        </div>

        <div x-data="{ docsOpen: true }">
            <button type="button" @click="docsOpen = !docsOpen" class="flex w-full items-center border-b border-line px-5 py-4 text-left transition hover:bg-brand-50/40" style="gap: 14px;">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                </div>
                <div class="min-w-0 flex-1 text-sm font-semibold">Документы</div>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $deal->documents->count() }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-muted transition" :class="docsOpen && 'rotate-90'"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
            <div x-show="docsOpen" class="space-y-2 border-b border-line bg-surface px-5 py-4">
                @forelse ($deal->documents as $doc)
                    <div class="flex items-center gap-3 rounded-2xl bg-white px-3.5 py-3">
                        <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl {{ AdminUi::fileIconTone($doc->file_name) }}">
                            <span class="text-[10px] font-bold leading-none">{{ AdminUi::fileExtension($doc->file_name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-ink">{{ $doc->file_name ?: $doc->title }}</div>
                            <div class="mt-0.5 text-xs text-muted">
                                {{ AdminUi::documentLabel($doc->type) }}
                                · {{ AdminUi::fileSize((int) $doc->size_bytes) }}
                                · {{ $doc->created_at?->timezone('Asia/Almaty')->format('d.m.Y H:i') }}
                            </div>
                            @if ($doc->title && $doc->title !== $doc->file_name)
                                <div class="mt-0.5 truncate text-xs text-muted">{{ $doc->title }}</div>
                            @endif
                        </div>
                        <a href="{{ route('admin.documents.download', $doc) }}" class="btn-ghost shrink-0 px-3 py-2 text-xs">
                            Скачать
                        </a>
                    </div>
                @empty
                    <div class="py-6 text-center text-sm text-muted">Документов по этой сделке пока нет</div>
                @endforelse
                <a href="{{ route('admin.documents.index', ['deal_id' => $deal->id]) }}" class="inline-flex items-center gap-1 pt-1 text-sm font-semibold text-brand-600">
                    Все документы сделки
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </a>
            </div>
        </div>

        <div x-data="{ historyOpen: false }">
            <button type="button" @click="historyOpen = !historyOpen" class="flex w-full items-center px-5 py-4 text-left transition hover:bg-brand-50/40" style="gap: 14px;">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div class="min-w-0 flex-1 text-sm font-semibold">История</div>
                <span class="text-sm text-muted">{{ $deal->auditLogs->count() }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-muted transition" :class="historyOpen && 'rotate-90'"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
            <div x-cloak x-show="historyOpen" x-transition class="space-y-2 border-t border-line bg-surface px-5 py-4">
                @forelse ($deal->auditLogs as $log)
                    <div class="rounded-xl bg-white px-3 py-2.5 text-sm">
                        <div class="font-medium">{{ AdminUi::auditActionLabel(is_string($log->action) ? $log->action : (string) ($log->action->value ?? $log->action)) }}</div>
                        <div class="mt-0.5 text-xs text-muted">
                            {{ $log->actor?->displayName() ?? 'Система' }}
                            · {{ AdminUi::adminWhen($log->created_at) }}
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-muted">Записей пока нет</div>
                @endforelse
                @if ($deal->dispute)
                    <a href="{{ route('admin.disputes.show', $deal->dispute) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600">
                        Открыть спор
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Admin controls --}}
    <section class="panel p-5 sm:p-6">
        <div class="mb-4 flex items-center gap-2 text-brand-600">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
            </svg>
            <h2 class="font-display text-base font-bold">Только для администраторов</h2>
        </div>

        @if ($canPayout ?? false)
            <form method="POST" action="{{ route('admin.deals.payout', $deal) }}" class="mb-6 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                @csrf
                <div class="text-sm font-semibold text-emerald-800">Выплатить исполнителю</div>
                <p class="text-xs text-emerald-700">Переведёт зарезервированные средства и поставит статус «Выплата исполнителю».</p>
                <input type="hidden" name="reason" value="Выплата через админку (демо)">
                <button class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700">Выплатить</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.deals.force-status', $deal) }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium">Изменить статус сделки</label>
                <select name="status" class="input">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($deal->status === $status)>{{ $status->labelRu() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Причина</label>
                <textarea name="reason" rows="3" class="input" required placeholder="Обязательный комментарий для аудита"></textarea>
            </div>
            <div class="flex items-start gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-sm text-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-4 w-4 shrink-0">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                </svg>
                <span>Смена статуса влияет на права сторон. Все изменения пишутся в аудит-лог.</span>
            </div>
            <button class="btn-primary w-full">
                Изменить статус
            </button>
        </form>
    </section>
</div>
@endsection
