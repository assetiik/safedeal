@extends('admin.layouts.app')

@section('title', 'Профиль')
@section('heading', 'Профиль пользователя')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Назад
    </a>
@endsection

@section('content')
@php
    use App\Support\AdminUi;
    use App\Support\Money;
    use App\Support\DealCode;
    use App\Enums\DealStatus;
    use App\Enums\UserRole;

    $verified = $user->email_verified_at !== null;
    $verifiedAt = $user->email_verified_at?->timezone('Asia/Almaty')->format('d.m.Y');
@endphp

<div class="mx-auto max-w-5xl space-y-5">
    {{-- Profile card --}}
    <section class="panel p-5 sm:p-6">
        <div class="flex items-start" style="gap: 16px;">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500">
                @if ($user->role === UserRole::Customer)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                @else
                    <span class="text-base font-bold">{{ AdminUi::initials($user->displayName()) }}</span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center" style="gap: 8px;">
                    <h2 class="font-display text-xl font-bold text-ink">{{ $user->displayName() }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $user->isActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $user->isActive() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        {{ AdminUi::userStatusLabel($user->status) }}
                    </span>
                </div>
                <div class="mt-1.5 flex items-center gap-1.5 text-sm text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    {{ AdminUi::roleLabel($user->role) }}
                </div>
            </div>
        </div>

        <div class="mt-5 border-t border-line">
            @foreach ([
                ['Email', $user->email, 'mail'],
                ['Телефон', $user->profile?->phone ?: '—', 'phone'],
                ['БИН / ИИН', $user->profile?->tax_id ?: '—', 'id'],
            ] as [$label, $value, $icon])
                <div class="flex items-center border-b border-line py-3.5" style="gap: 14px;">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                        @if ($icon === 'mail')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        @elseif ($icon === 'phone')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" /></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-muted">{{ $label }}</div>
                        <div class="mt-0.5 truncate text-sm font-semibold text-ink">{{ $value }}</div>
                    </div>
                </div>
            @endforeach

            <div x-data="{ open: false }" class="border-b border-line py-3.5">
                <button type="button" @click="open = !open" class="flex w-full items-center text-left" style="gap: 14px;">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-muted">Реквизиты</div>
                        <div class="mt-0.5 truncate text-sm font-semibold text-ink">{{ $user->profile?->bank_details ?: '—' }}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0 text-muted transition" :class="open && 'rotate-90'">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
                <div x-cloak x-show="open" x-transition class="mt-3 ml-[50px] space-y-1.5 rounded-xl bg-surface px-3 py-3 text-sm">
                    <div><span class="text-muted">Счёт / IBAN:</span> {{ $user->profile?->bank_details ?: '—' }}</div>
                    <div><span class="text-muted">Адрес:</span> {{ $user->profile?->legal_address ?: '—' }}</div>
                    @if ($user->profile?->contact_person)
                        <div><span class="text-muted">Контакт:</span> {{ $user->profile->contact_person }}</div>
                    @endif
                </div>
            </div>

            <div class="flex items-center py-3.5" style="gap: 14px;">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-muted">Верификация</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @if ($verified)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                                Пройдена
                            </span>
                            <span class="text-sm text-muted">{{ $verifiedAt }}</span>
                        @else
                            <span class="badge badge-warn">Не пройдена</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="panel p-5 sm:p-6">
        <h2 class="font-display text-lg font-bold">Связанные сделки</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                [$stats['total'], 'Всего', 'doc', 'bg-brand-50 text-brand-500'],
                [$stats['pending'], 'В ожидании', 'dot', 'bg-brand-50 text-brand-500'],
                [$stats['in_progress'], 'В работе', 'lock', 'bg-brand-50 text-brand-500'],
                [$stats['completed'], 'Завершено', 'check', 'bg-emerald-50 text-emerald-600'],
            ] as [$value, $label, $icon, $tone])
                <div class="rounded-2xl {{ $tone }} px-3 py-4 text-center">
                    <div class="mx-auto mb-2 flex h-7 w-7 items-center justify-center">
                        @if ($icon === 'check')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @elseif ($icon === 'lock')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        @elseif ($icon === 'dot')
                            <span class="h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        @endif
                    </div>
                    <div class="font-display text-2xl font-bold">{{ $value }}</div>
                    <div class="mt-1 text-xs text-muted">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Deals list --}}
    <section class="panel overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="font-display text-lg font-bold">Последние сделки</h2>
        </div>
        <div class="divide-y divide-line">
            @forelse ($deals as $deal)
                @php
                    $isPending = in_array($deal->status, [
                        DealStatus::AwaitingExecutor, DealStatus::ContractConfirmed, DealStatus::AwaitingPayment, DealStatus::AwaitingCustomer,
                    ], true);
                    $isDone = in_array($deal->status, [DealStatus::Completed, DealStatus::PayoutCompleted], true);
                    $counterparty = $user->id === $deal->customer_user_id
                        ? ($deal->contractor?->displayName() ? 'Исполнитель: '.$deal->contractor->displayName() : 'Исполнитель: —')
                        : 'Заказчик: '.($deal->customer?->displayName() ?? '—');
                    $iconTone = $isDone ? 'bg-emerald-50 text-emerald-600' : 'bg-brand-50 text-brand-500';
                @endphp
                <a href="{{ route('admin.deals.show', $deal) }}" class="flex items-center px-5 py-4 transition hover:bg-brand-50/40" style="gap: 14px;">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconTone }}">
                        @if ($isDone)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @elseif ($isPending)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="text-sm font-semibold text-ink">Сделка {{ DealCode::format($deal) }}</div>
                            <span class="badge {{ AdminUi::dealBadge($deal->status) }}">{{ $deal->status->labelRu() }}</span>
                        </div>
                        <div class="mt-0.5 truncate text-sm text-muted">{{ $counterparty }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 text-sm">
                            <span class="font-semibold">{{ Money::tenge($deal->amount_tenge) }}</span>
                            <span class="text-muted">{{ $deal->updated_at?->timezone('Asia/Almaty')->format('d.m.Y') }}</span>
                        </div>
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0 text-muted">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-sm text-muted">Сделок пока нет</div>
            @endforelse
        </div>

        @if ($stats['total'] > 0)
            <div class="border-t border-line px-5 py-4">
                <a href="{{ route('admin.deals.index', ['q' => $user->displayName()]) }}" class="link-more inline-flex items-center gap-1">
                    Показать все сделки ({{ $stats['total'] }})
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        @endif
    </section>

    <div class="grid gap-3 sm:grid-cols-2">
        <form method="POST" action="{{ route('admin.users.block', $user) }}" onsubmit="return confirm('Заблокировать пользователя?')">
            @csrf
            <button
                type="submit"
                @disabled(! $user->isActive())
                class="flex w-full items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition {{ $user->isActive() ? 'border-rose-300 bg-white text-rose-600 hover:bg-rose-50' : 'cursor-not-allowed border-line bg-surface text-muted opacity-50' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                Заблокировать
            </button>
        </form>

        <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
            @csrf
            <button
                type="submit"
                @disabled($user->isActive())
                class="flex w-full items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition {{ ! $user->isActive() ? 'border-brand-300 bg-white text-brand-600 hover:bg-brand-50' : 'cursor-not-allowed border-line bg-surface text-muted opacity-50' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                Разблокировать
            </button>
        </form>
    </div>
</div>
@endsection
