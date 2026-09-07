@extends('admin.layouts.app')

@section('title', 'Аудит-лог')
@section('heading', 'Аудит-лог')

@section('content')
@php
    use App\Support\AdminUi;
    use App\Support\DealCode;
    use App\Support\Money;
    use App\Enums\DealStatus;
@endphp

<form method="GET" action="{{ route('admin.audit.index') }}" class="mb-5 space-y-3">
    <div class="relative max-w-2xl">
        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-muted">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>
        <input
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="Поиск по действиям, пользователям, объектам..."
            class="input pl-12"
        >
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <label class="inline-flex items-center gap-2.5 rounded-2xl border border-line bg-white px-3.5 py-2 shadow-[0_1px_2px_rgba(17,24,39,0.03)]">
            <span class="shrink-0 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </span>
            <select name="days" class="min-w-[150px] border-0 bg-transparent py-0.5 pr-6 text-sm font-medium text-ink outline-none" onchange="this.form.submit()">
                @foreach ([1 => '1 день', 7 => '7 дней', 30 => '30 дней', 90 => '90 дней', 0 => 'Всё время'] as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>Период: {{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="inline-flex items-center gap-2.5 rounded-2xl border border-line bg-white px-3.5 py-2 shadow-[0_1px_2px_rgba(17,24,39,0.03)]">
            <span class="shrink-0 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </span>
            <select name="actor" class="min-w-[130px] border-0 bg-transparent py-0.5 pr-6 text-sm font-medium text-ink outline-none" onchange="this.form.submit()">
                <option value="all" @selected($actor === 'all')>Все акторы</option>
                <option value="admin" @selected($actor === 'admin')>Админы</option>
                <option value="customer" @selected($actor === 'customer')>Заказчики</option>
                <option value="contractor" @selected($actor === 'contractor')>Исполнители</option>
                <option value="system" @selected($actor === 'system')>Система</option>
            </select>
        </label>

        <label class="inline-flex items-center gap-2.5 rounded-2xl border border-line bg-white px-3.5 py-2 shadow-[0_1px_2px_rgba(17,24,39,0.03)]">
            <span class="shrink-0 text-brand-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
            </span>
            <select name="action" class="min-w-[220px] max-w-[340px] border-0 bg-transparent py-0.5 pr-6 text-sm font-medium text-ink outline-none" onchange="this.form.submit()">
                <option value="">Все действия</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" @selected($action === $act)>{{ AdminUi::auditActionLabel($act) }}</option>
                @endforeach
            </select>
        </label>

        <a href="{{ route('admin.audit.index') }}" class="px-2 text-sm font-semibold text-brand-500 hover:text-brand-600">Сбросить</a>
    </div>
</form>

<div class="panel overflow-hidden p-4 sm:p-6">
    @forelse ($logs as $log)
        @php
            $isSystem = $log->actor === null;
            $actorName = $log->actor?->displayName() ?? 'Система';
            $payload = is_array($log->payload) ? $log->payload : [];
            $fromStatus = AdminUi::auditStatusLabel($payload['from'] ?? null);
            $toStatus = AdminUi::auditStatusLabel($payload['to'] ?? null);
            $amountFrom = isset($payload['from_amount']) ? (int) $payload['from_amount'] : (isset($payload['old_amount']) ? (int) $payload['old_amount'] : null);
            $amountTo = isset($payload['to_amount']) ? (int) $payload['to_amount'] : (isset($payload['amount_tenge']) ? (int) $payload['amount_tenge'] : (isset($payload['amount']) ? (int) $payload['amount'] : null));
            $fileName = $payload['file_name'] ?? $payload['filename'] ?? null;
            $roleFrom = $payload['from_role'] ?? $payload['old_role'] ?? null;
            $roleTo = $payload['to_role'] ?? $payload['new_role'] ?? null;

            $objectHref = null;
            $objectLabel = null;
            if ($log->deal) {
                $objectHref = route('admin.deals.show', $log->deal);
                $objectLabel = 'Сделка '.DealCode::format($log->deal);
            } elseif (str_contains((string) $log->entity_type, 'User') && $log->entity_id) {
                $objectHref = route('admin.users.show', $log->entity_id);
                $objectLabel = 'Пользователь';
            } elseif (str_contains((string) $log->entity_type, 'Dispute') && $log->entity_id) {
                $objectHref = route('admin.disputes.show', $log->entity_id);
                $objectLabel = 'Спор';
            } elseif (str_contains((string) $log->entity_type, 'Document')) {
                $objectLabel = $fileName ? (string) $fileName : 'Документ';
            }
        @endphp

        <div class="grid grid-cols-[1fr] gap-3 py-4 sm:grid-cols-[148px_28px_minmax(0,1fr)] sm:gap-x-4 {{ ! $loop->last ? 'border-b border-line/70 sm:border-0' : '' }}">
            <div class="text-xs text-muted sm:pt-2.5 sm:text-right">
                {{ $log->created_at?->timezone('Asia/Almaty')->format('d.m.Y H:i:s') }}
            </div>

            <div class="relative hidden justify-center sm:flex">
                @if (! $loop->last)
                    <div class="absolute left-1/2 top-3 bottom-[-1rem] w-px -translate-x-1/2 bg-brand-200"></div>
                @endif
                <div class="relative z-10 mt-2.5 h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white"></div>
            </div>

            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <div @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl',
                        'bg-violet-50 text-violet-600' => $isSystem,
                        'bg-brand-50 text-brand-500' => ! $isSystem,
                    ])>
                        @if ($isSystem)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.992l-1.003-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <div class="font-semibold text-ink">{{ $actorName }}</div>
                        <div class="mt-0.5 text-sm text-muted">{{ AdminUi::auditActionLabel($log->action) }}</div>

                        @if ($objectLabel)
                            <div class="mt-2">
                                @if ($objectHref)
                                    <a href="{{ $objectHref }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-500 hover:text-brand-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        {{ $objectLabel }}
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        {{ $objectLabel }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:max-w-[280px] sm:justify-end">
                    @if ($toStatus && ! $fromStatus)
                        <span @class([
                            'badge',
                            'badge-ok' => in_array(($payload['to'] ?? ''), [DealStatus::Completed->value, DealStatus::PayoutCompleted->value], true),
                            'badge-danger' => ($payload['to'] ?? '') === DealStatus::Dispute->value,
                            'badge-warn' => ! in_array(($payload['to'] ?? ''), [DealStatus::Completed->value, DealStatus::PayoutCompleted->value, DealStatus::Dispute->value], true),
                        ])>{{ $toStatus }}</span>
                    @elseif ($fromStatus && $toStatus)
                        <span class="text-sm font-medium text-muted">{{ $fromStatus }} → <span class="text-ink">{{ $toStatus }}</span></span>
                    @endif

                    @if ($fileName)
                        <span class="inline-flex items-center rounded-xl border border-line bg-white px-2.5 py-1 text-xs font-medium text-muted">
                            {{ $fileName }}
                        </span>
                    @endif

                    @if ($amountFrom !== null && $amountTo !== null && $amountFrom !== $amountTo)
                        <span class="text-sm font-semibold text-ink">{{ Money::tenge($amountFrom) }} → {{ Money::tenge($amountTo) }}</span>
                    @elseif ($amountTo !== null && str_starts_with($log->action, 'payment.'))
                        <span class="text-sm font-semibold text-ink">{{ Money::tenge($amountTo) }}</span>
                    @endif

                    @if ($roleFrom && $roleTo)
                        <span class="text-sm font-medium text-muted">{{ $roleFrom }} → <span class="text-ink">{{ $roleTo }}</span></span>
                    @endif

                    @if ($log->ip && ($isSystem || empty($payload) || (! $toStatus && ! $fileName && $amountTo === null)))
                        <span class="text-xs text-muted">IP {{ $log->ip }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="py-12 text-center text-muted">Записей нет</div>
    @endforelse
</div>

<div class="mt-6">
    {{ $logs->links() }}
</div>
@endsection
