@props([
    'id' => null,
    'title' => 'طباعة',
    'code' => null,
    'urlA4' => null,
    'urlA5' => null,
    'urlThermal' => null,
    'directA4' => null,
    'directA5' => null,
    'directThermal' => null,
    'size' => 'md',
    'btnClass' => null,
])

@php
    $menuId = $id ?: 'printMenu_' . ($code ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $code) : uniqid());
    $isSmall = $size === 'sm';
    $defaultBtnClass = $isSmall 
        ? 'erp-btn erp-btn-ghost erp-btn-sm text-[var(--primary)]' 
        : 'erp-btn erp-btn-secondary';
    $finalBtnClass = $btnClass ?: $defaultBtnClass;
@endphp

<div class="erp-dropdown inline-block" data-component="print-dropdown">
    <button type="button" 
        data-dropdown-toggle="{{ $menuId }}" 
        class="{{ $finalBtnClass }}" 
        title="{{ $title }}{{ $code ? ' (' . $code . ')' : '' }}">
        <x-icon name="printer" size="{{ $isSmall ? 14 : 16 }}" />
        @if(!$isSmall)
            <span>{{ $title }}</span>
        @endif
        <x-icon name="chevron-down" size="{{ $isSmall ? 10 : 13 }}" />
    </button>

    <div id="{{ $menuId }}" class="erp-dropdown-menu {{ $isSmall ? 'min-w-[210px] left-0 right-auto' : 'min-w-[240px]' }}">
        <div class="erp-dropdown-header text-[11px] text-[var(--text-muted)] font-semibold flex items-center justify-between">
            <span>نماذج وخيارات الطباعة</span>
            @if($code)
                <span class="text-[10px] font-mono text-[var(--primary)] font-bold">{{ $code }}</span>
            @endif
        </div>

        @if($urlThermal)
            <a href="{{ $urlThermal }}" target="_blank" class="erp-dropdown-item font-semibold text-emerald-700 dark:text-emerald-400">
                <x-icon name="printer" size="15" class="text-emerald-600" />
                <div class="flex flex-col">
                    <span>إيصال حراري (80 مم)</span>
                    <span class="text-[10px] text-[var(--text-muted)] font-normal">طابعة الكاشير Xprinter XP-80C</span>
                </div>
            </a>
        @endif

        @if($directThermal)
            <button type="button" data-action="python-direct-print" data-url="{{ $directThermal }}" data-fallback-url="{{ $urlThermal }}" class="erp-dropdown-item font-bold text-emerald-800 dark:text-emerald-300">
                <x-icon name="zap" size="15" class="text-emerald-600" />
                <div class="flex flex-col">
                    <span>طباعة فورية صامتة ⚡</span>
                    <span class="text-[10px] text-[var(--text-muted)] font-normal">إرسال مباشر لطابعة XP-80C</span>
                </div>
            </button>
        @endif

        @if($urlA4 || $urlA5)
            <div class="erp-dropdown-divider"></div>
        @endif

        @if($urlA4)
            <a href="{{ $urlA4 }}" target="_blank" class="erp-dropdown-item">
                <x-icon name="file-text" size="15" class="text-sky-600" />
                <div class="flex flex-col">
                    <span>فاتورة قياسية (A4)</span>
                    <span class="text-[10px] text-[var(--text-muted)] font-normal">نموذج رسمي كامل للشركات</span>
                </div>
            </a>
        @endif

        @if($urlA5)
            <a href="{{ $urlA5 }}" target="_blank" class="erp-dropdown-item">
                <x-icon name="file" size="15" class="text-indigo-600" />
                <div class="flex flex-col">
                    <span>فاتورة مدمجة (A5)</span>
                    <span class="text-[10px] text-[var(--text-muted)] font-normal">توفير الورق والأحبار</span>
                </div>
            </a>
        @endif
    </div>
</div>
