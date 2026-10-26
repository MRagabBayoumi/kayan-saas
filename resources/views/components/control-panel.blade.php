@props([
    'title' => 'لوحة التحكم',
    'breadcrumbs' => [],
    'showSearch' => false,
    'searchPlaceholder' => 'بحث وتصفية...',
    'showViewSwitchers' => false,
])

<section class="erp-control-panel">
    <div class="erp-cp-top">
        <!-- Breadcrumb & Title -->
        <nav class="erp-breadcrumb" aria-label="شريط المسار">
            <span class="erp-breadcrumb-item">
                <a href="{{ url('/') }}">{{ config('app.name', 'BayoSoft ERP') }}</a>
            </span>
            @foreach($breadcrumbs as $name => $link)
                <span class="erp-breadcrumb-sep">/</span>
                <span class="erp-breadcrumb-item">
                    @if($link)
                        <a href="{{ $link }}">{{ $name }}</a>
                    @else
                        <span>{{ $name }}</span>
                    @endif
                </span>
            @endforeach
            <span class="erp-breadcrumb-sep">/</span>
            <span class="erp-breadcrumb-item">
                <span>{{ $title }}</span>
            </span>
        </nav>

        @if($showViewSwitchers)
            <!-- View Switchers (Odoo Style) -->
            <div class="erp-view-switchers">
                <button type="button" class="erp-view-btn active" title="عرض القائمة (List View)"><x-icon name="list" size="14" /></button>
                <button type="button" class="erp-view-btn" title="عرض البطاقات (Kanban View)"><x-icon name="layout-grid" size="14" /></button>
                <button type="button" class="erp-view-btn" title="عرض الرسم البياني (Graph View)"><x-icon name="trending-up" size="14" /></button>
            </div>
        @endif
    </div>

    <div class="erp-cp-bottom">
        <!-- Actions slot -->
        <div class="erp-actions-bar">
            {{ $actions ?? '' }}
        </div>

        <!-- Search / Filter slot -->
        @if($showSearch)
            <div class="erp-cp-search">
                <div class="erp-search-box">
                    <input type="search" class="erp-search-input" placeholder="{{ $searchPlaceholder }}">
                    <span class="erp-search-icon"><x-icon name="search" size="14" /></span>
                </div>
                {{ $filters ?? '' }}
            </div>
        @else
            <div>
                {{ $extra ?? '' }}
            </div>
        @endif
    </div>
</section>
