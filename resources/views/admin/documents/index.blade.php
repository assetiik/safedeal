@extends('admin.layouts.app')

@section('title', 'Документы')
@section('heading', 'Документы')

@section('actions')
<button type="button" onclick="document.getElementById('upload-modal').showModal()" class="btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
    </svg>
    Загрузить документ
</button>
@endsection

@section('content')
@php
    use App\Support\AdminUi;
    use App\Support\DealCode;

    $filters = [
        'all' => 'Все',
        'contract' => 'Договоры',
        'act' => 'Акты',
        'other' => 'Прочее',
    ];
@endphp

<form method="GET" action="{{ route('admin.documents.index') }}" class="mb-4">
    <input type="hidden" name="type" value="{{ $type }}">
    @if (! empty($dealId))
        <input type="hidden" name="deal_id" value="{{ $dealId }}">
    @endif
    <div class="flex gap-2.5">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                type="search"
                name="q"
                value="{{ $q }}"
                placeholder="Поиск документов..."
                class="input pl-12"
            >
        </div>
        <button type="submit" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-line bg-white text-brand-500 transition hover:bg-brand-50" title="Фильтр / поиск" aria-label="Применить поиск">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
        </button>
    </div>
</form>

@if (! empty($dealId))
    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm">
        <span class="text-brand-700">Фильтр: документы сделки
            <strong>{{ $filteredDeal ? DealCode::format($filteredDeal) : $dealId }}</strong>
        </span>
        <a href="{{ route('admin.documents.index', ['type' => $type, 'q' => $q]) }}" class="font-semibold text-brand-600 hover:underline">Сбросить</a>
    </div>
@endif

<div class="mb-5 flex flex-wrap gap-2.5">
    @foreach ($filters as $key => $label)
        <a href="{{ route('admin.documents.index', array_filter(['type' => $key, 'q' => $q, 'deal_id' => $dealId ?: null])) }}"
           @class([
               'rounded-2xl px-4 py-2 text-sm font-semibold transition',
               'bg-brand-500 text-white shadow-[0_8px_18px_rgba(59,110,245,0.25)]' => $type === $key,
               'border border-line bg-white text-ink hover:bg-brand-50' => $type !== $key,
           ])>
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($documents as $document)
        <article
            class="panel relative p-4 sm:p-5"
            x-data="{ open: false }"
            @keydown.escape.window="open = false"
            :style="open ? 'z-index: 50;' : 'z-index: 1;'"
        >
            <div class="flex items-start gap-3.5">
                <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl {{ AdminUi::fileIconTone($document->file_name) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 opacity-90">
                        <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                        <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                    </svg>
                    <span class="mt-0.5 text-[10px] font-bold leading-none">{{ AdminUi::fileExtension($document->file_name) }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="truncate font-semibold text-ink">{{ $document->file_name ?: $document->title }}</h3>
                    @if ($document->deal)
                        <a href="{{ route('admin.deals.show', $document->deal) }}" class="mt-1 block truncate text-sm text-muted hover:text-brand-600">
                            Сделка № {{ ltrim(DealCode::format($document->deal), '#') }}
                        </a>
                    @else
                        <div class="mt-1 truncate text-sm text-muted">{{ $document->title }}</div>
                    @endif
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-muted">
                        <span class="badge badge-info">{{ AdminUi::documentLabel($document->type) }}</span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            {{ $document->created_at?->timezone('Asia/Almaty')->format('d.m.Y') }}
                        </span>
                        <span>{{ AdminUi::fileSize((int) $document->size_bytes) }}</span>
                    </div>
                </div>

                <div class="relative ml-auto flex shrink-0 items-center gap-2 self-start">
                    <a href="{{ route('admin.documents.download', $document) }}" class="btn-ghost px-3 py-2 text-xs">
                        Скачать
                    </a>
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
                        @click.outside="open = false"
                        x-transition.opacity
                        class="absolute right-0 top-10 z-50 min-w-[200px] overflow-hidden rounded-2xl border border-line bg-white py-1 shadow-lg whitespace-nowrap"
                    >
                        <a href="{{ route('admin.documents.download', $document) }}" class="block px-4 py-2.5 text-sm font-medium text-ink hover:bg-brand-50">
                            Скачать
                        </a>
                        @if ($document->deal)
                            <a href="{{ route('admin.deals.show', $document->deal) }}" class="block px-4 py-2.5 text-sm font-medium text-ink hover:bg-brand-50">
                                Открыть сделку
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="panel px-5 py-12 text-center text-muted">Документы не найдены</div>
    @endforelse
</div>

<div class="mt-6">{{ $documents->links() }}</div>

<dialog id="upload-modal" class="w-full max-w-lg rounded-2xl border border-line p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="p-6">
        @csrf
        <h3 class="font-display text-xl font-semibold">Загрузить документ</h3>
        <div class="mt-4 space-y-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Сделка</label>
                <select name="deal_id" class="input" required>
                    <option value="">Выберите сделку</option>
                    @foreach ($deals as $deal)
                        <option value="{{ $deal->id }}">#{{ $deal->deal_number }} — {{ $deal->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Тип</label>
                <select name="type" class="input" required>
                    <option value="contract">Договор</option>
                    <option value="act">Акт</option>
                    <option value="technical">Технический</option>
                    <option value="other">Прочее</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Название</label>
                <input type="text" name="title" class="input" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Файл</label>
                <input type="file" name="file" class="input" required>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn-ghost" onclick="this.closest('dialog').close()">Отмена</button>
            <button class="btn-primary">Загрузить</button>
        </div>
    </form>
</dialog>
@endsection
