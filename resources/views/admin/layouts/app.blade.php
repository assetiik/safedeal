<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Админ-панель') · SafeDeal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|sora:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell">
    <aside class="admin-sidebar">
        <div class="flex items-center gap-3 border-b border-line px-5 py-5">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-[0_8px_18px_rgba(59,110,245,0.35)]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                    <path fill-rule="evenodd" d="M12 1.5c-2.338 0-4.5 1.12-5.85 2.9A7.48 7.48 0 0 0 4.5 9v1.5A2.25 2.25 0 0 0 2.25 12.75v6A2.25 2.25 0 0 0 4.5 21h15a2.25 2.25 0 0 0 2.25-2.25v-6A2.25 2.25 0 0 0 19.5 10.5V9a7.48 7.48 0 0 0-1.65-4.6A7.48 7.48 0 0 0 12 1.5Zm-3.75 9V9a3.75 3.75 0 1 1 7.5 0v1.5h-7.5Z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <div class="font-display text-base font-bold tracking-tight text-ink">SafeDeal</div>
                <div class="text-xs text-muted">Безопасные сделки</div>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @php
                $items = [
                    ['route' => 'admin.dashboard', 'label' => 'Дашборд', 'match' => 'admin.dashboard', 'icon' => 'grid'],
                    ['route' => 'admin.users.index', 'label' => 'Пользователи', 'match' => 'admin.users.*', 'icon' => 'users'],
                    ['route' => 'admin.deals.index', 'label' => 'Сделки', 'match' => 'admin.deals.*', 'icon' => 'briefcase'],
                    ['route' => 'admin.disputes.index', 'label' => 'Споры', 'match' => 'admin.disputes.*', 'icon' => 'flag'],
                    ['route' => 'admin.payments.index', 'label' => 'Финансы', 'match' => 'admin.payments.*', 'icon' => 'wallet'],
                    ['route' => 'admin.documents.index', 'label' => 'Документы', 'match' => 'admin.documents.*', 'icon' => 'folder'],
                    ['route' => 'admin.audit.index', 'label' => 'Аудит-лог', 'match' => 'admin.audit.*', 'icon' => 'list'],
                ];
            @endphp

            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   class="nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}">
                    @include('admin.partials.icon', ['name' => $item['icon']])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-line p-4">
            <div class="mb-3 rounded-2xl bg-brand-50 px-3 py-3">
                <div class="text-xs text-muted">Вы вошли как</div>
                <div class="truncate text-sm font-semibold text-ink">{{ auth()->user()->displayName() }}</div>
                <div class="truncate text-xs text-muted">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn-ghost w-full">Выйти</button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="sticky top-0 z-20 border-b border-line bg-white/90 px-8 py-4 backdrop-blur-md">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="font-display text-[28px] font-bold tracking-tight text-ink">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-1 text-sm text-muted">@yield('subheading')</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @yield('actions')
                    <a href="{{ route('admin.disputes.index') }}" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-500 transition hover:bg-brand-100" title="Уведомления / споры">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <div class="px-8 py-6">
            @if (session('success'))
                <div class="fade-up mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="fade-up mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="fade-up">
                @yield('content')
            </div>
        </div>
    </main>
</body>
</html>
