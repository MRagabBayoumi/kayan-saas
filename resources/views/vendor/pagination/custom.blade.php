@if ($paginator->hasPages())
    <nav role="navigation" aria-label="تنقل الصفحات" class="erp-pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="erp-pagination-item erp-pagination-nav disabled" aria-disabled="true">
                <x-icon name="chevron-right" size="14" />
                <span>السابق</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="erp-pagination-item erp-pagination-nav" aria-label="الصفحة السابقة">
                <x-icon name="chevron-right" size="14" />
                <span>السابق</span>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="erp-pagination-dots" aria-disabled="true">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="erp-pagination-item active font-numeric" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="erp-pagination-item font-numeric" aria-label="صفحة {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="erp-pagination-item erp-pagination-nav" aria-label="الصفحة التالية">
                <span>التالي</span>
                <x-icon name="chevron-left" size="14" />
            </a>
        @else
            <span class="erp-pagination-item erp-pagination-nav disabled" aria-disabled="true">
                <span>التالي</span>
                <x-icon name="chevron-left" size="14" />
            </span>
        @endif
    </nav>
@endif
