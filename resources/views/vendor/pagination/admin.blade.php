@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Пагинация" class="mt-2 flex flex-col items-center gap-3">
        <p class="text-sm text-muted">
            @if ($paginator->firstItem())
                Показано
                <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
                из
                <span class="font-semibold text-ink">{{ number_format($paginator->total(), 0, ',', ' ') }}</span>
            @else
                Записей нет
            @endif
        </p>

        @if ($paginator->hasPages())
            <div class="inline-flex items-center gap-1 rounded-2xl border border-line bg-white p-1 shadow-[0_1px_2px_rgba(17,24,39,0.03)]">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-300" aria-disabled="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-muted transition hover:bg-brand-50 hover:text-brand-600" aria-label="Назад">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-muted">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-brand-500 px-3 text-sm font-semibold text-white shadow-[0_6px_14px_rgba(59,110,245,0.28)]">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-3 text-sm font-medium text-muted transition hover:bg-brand-50 hover:text-brand-600" aria-label="Страница {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-muted transition hover:bg-brand-50 hover:text-brand-600" aria-label="Вперёд">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-300" aria-disabled="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
