@extends('admin.layouts.app')

@section('title', 'Пользователи')
@section('heading', 'Пользователи')

@section('content')
@php
    use App\Support\AdminUi;

    $sortLabels = [
        'newest' => 'По дате регистрации',
        'oldest' => 'Сначала старые',
        'name' => 'По имени',
    ];
@endphp

<form method="GET" action="{{ route('admin.users.index') }}" class="mb-4">
    <input type="hidden" name="role" value="{{ $role }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-muted">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </span>
        <input
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="Поиск по имени, компании или email"
            class="input pl-12"
        >
    </div>
</form>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach (['all' => 'Все', 'customer' => 'Заказчики', 'contractor' => 'Исполнители'] as $key => $label)
        <a href="{{ route('admin.users.index', ['role' => $key, 'q' => $q, 'sort' => $sort]) }}"
           class="rounded-2xl px-4 py-2 text-sm font-semibold transition {{ $role === $key ? 'bg-brand-500 text-white shadow-[0_8px_18px_rgba(59,110,245,0.25)]' : 'border border-brand-200 bg-white text-brand-500 hover:bg-brand-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="text-sm text-muted">
        Всего: <span class="font-semibold text-ink">{{ number_format($total, 0, ',', ' ') }}</span>
        {{ $total === 1 ? 'пользователь' : ($total > 1 && $total < 5 ? 'пользователя' : 'пользователей') }}
        @if ($role !== 'all' || $q !== '')
            <span class="text-muted">из {{ number_format($allTotal, 0, ',', ' ') }}</span>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2">
        <input type="hidden" name="role" value="{{ $role }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <label class="sr-only" for="users-sort">Сортировка</label>
        <select id="users-sort" name="sort" onchange="this.form.submit()" class="input w-auto min-w-[200px] py-2 text-sm font-medium text-ink">
            @foreach ($sortLabels as $key => $label)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="space-y-3">
    @forelse ($users as $user)
        <article
            class="panel relative flex items-start gap-3 p-4 sm:items-center sm:gap-4 sm:p-5"
            x-data="{ open: false }"
            @keydown.escape.window="open = false"
            :style="open ? 'z-index: 50;' : 'z-index: 1;'"
        >
            <a href="{{ route('admin.users.show', $user) }}" class="flex min-w-0 flex-1 items-start gap-3.5 sm:items-center">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ AdminUi::avatarTone($user->id) }}">
                    {{ AdminUi::initials($user->displayName()) }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="truncate font-semibold text-ink">{{ $user->displayName() }}</h3>
                        <span class="badge {{ AdminUi::userBadge($user->status) }}">{{ AdminUi::userStatusLabel($user->status) }}</span>
                    </div>
                    <div class="mt-0.5 truncate text-sm text-muted">{{ $user->email }}</div>
                    <div class="mt-1.5 flex items-center gap-1.5 text-sm text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0 text-brand-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.781-2.015-1.85-2.174a49.8 49.8 0 0 0-15.3 0C3.531 6.691 2.75 7.625 2.75 8.706v3.783c0 .655.269 1.25.75 1.661m16.5 0a2.18 2.18 0 0 1-.75 1.661m-15.75 0a2.18 2.18 0 0 0 .75 1.661" />
                        </svg>
                        <span>{{ AdminUi::roleLabel($user->role) }}</span>
                    </div>
                </div>
            </a>

            <div class="relative shrink-0">
                <button
                    type="button"
                    @click.stop="open = !open"
                    class="flex h-9 w-9 items-center justify-center rounded-xl text-muted transition hover:bg-brand-50 hover:text-brand-600"
                    aria-label="Действия"
                    :aria-expanded="open.toString()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition.origin.top.right
                    @click.outside="open = false"
                    class="absolute right-0 top-full mt-1 whitespace-nowrap rounded-2xl border border-line bg-white py-1 shadow-[0_16px_40px_rgba(17,24,39,0.14)]"
                    style="z-index: 60; min-width: 200px;"
                >
                    <a href="{{ route('admin.users.show', $user) }}" class="block px-4 py-3 text-sm font-medium text-ink transition hover:bg-brand-50">
                        Открыть профиль
                    </a>

                    @if ($user->isActive())
                        <form method="POST" action="{{ route('admin.users.block', $user) }}" onsubmit="return confirm('Заблокировать этого пользователя?')">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-semibold transition hover:bg-rose-50" style="color: #e11d48;">
                                Заблокировать
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                Разблокировать
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="panel px-5 py-14 text-center text-sm text-muted">Пользователи не найдены</div>
    @endforelse
</div>

<div class="mt-6">{{ $users->links() }}</div>
@endsection
