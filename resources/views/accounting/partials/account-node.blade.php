@props(['account', 'level' => 1])

@php
    $hasChildren = $account->children && $account->children->isNotEmpty();
    $balance = (float) $account->current_balance;
    $isPositive = $balance > 0;
    $isNegative = $balance < 0;
    $isZero = abs($balance) < 0.001;
    
    // Type styling
    $typeBorderClass = match ($account->type) {
        'asset' => 'coa-type-asset',
        'liability' => 'coa-type-liability',
        'equity' => 'coa-type-equity',
        'revenue' => 'coa-type-revenue',
        'expense' => 'coa-type-expense',
        default => '',
    };

    $levelClasses = match ($account->level) {
        1 => 'level-1 py-3 bg-[var(--bg-surface-alt)] font-bold',
        2 => 'level-2 py-2.5 font-semibold',
        3 => 'level-3 py-2',
        default => 'level-4 py-2 font-normal',
    };
@endphp

<div class="coa-node-row {{ $levelClasses }} {{ $account->level === 1 ? $typeBorderClass : '' }} account-row-wrapper border-b border-[var(--border-light)]" 
     id="account-node-{{ $account->id }}"
     data-account-id="{{ $account->id }}" 
     data-account-code="{{ $account->code }}" 
     data-account-name="{{ $account->name }}" 
     data-account-type="{{ $account->type }}"
     data-level="{{ $account->level }}">
    
    <div class="coa-node-content" style="padding-right: {{ max(16, ($account->level - 1) * 20 + 16) }}px;">
        
        <!-- Left Column: Expand Button, Code, Name, System Tag, Subcount -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            @if($hasChildren)
                <button type="button" 
                        class="coa-toggle-btn node-toggle-btn" 
                        aria-expanded="true"
                        title="طي / توسيع الفرع"
                        onclick="toggleAccountNode(this, 'children-{{ $account->id }}')">
                    <x-icon name="chevron-down" size="13" class="transform transition-transform duration-150" />
                </button>
            @else
                <span class="w-6 h-6 flex items-center justify-center text-[var(--text-light)] flex-shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-[var(--border-color)]"></span>
                </span>
            @endif

            <!-- Code Badge with Quick-Copy -->
            <button type="button" 
                    class="coa-code-pill font-numeric" 
                    title="انقر لنسخ كود الحساب ({{ $account->code }})"
                    onclick="copyAccountCode('{{ $account->code }}', event)">
                <span>{{ $account->code }}</span>
                <x-icon name="copy" size="10" class="opacity-50 hover:opacity-100" />
            </button>

            <!-- Node Icon -->
            @if($account->level === 1)
                <span class="text-[var(--primary)] hidden sm:inline-flex flex-shrink-0">
                    <x-icon name="folder-open" size="17" />
                </span>
            @elseif($hasChildren)
                <span class="text-amber-500/80 hidden sm:inline-flex flex-shrink-0">
                    <x-icon name="folder" size="15" />
                </span>
            @else
                <span class="text-emerald-500/70 hidden sm:inline-flex flex-shrink-0">
                    <x-icon name="tag" size="13" />
                </span>
            @endif

            <!-- Account Name Arabic + English -->
            <div class="flex items-center gap-2 truncate min-w-0">
                <span class="text-[var(--text-main)] truncate {{ $account->level === 1 ? 'text-sm font-bold' : ($account->level === 2 ? 'text-xs font-semibold' : 'text-xs') }}">
                    {{ $account->name }}
                </span>
                
                @if($account->name_en)
                    <span class="text-[11px] text-[var(--text-muted)] font-sans hidden md:inline truncate">
                        {{ $account->name_en }}
                    </span>
                @endif

                @if($hasChildren)
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)] font-numeric">
                        {{ $account->children->count() }}
                    </span>
                @endif

                @if($account->is_system)
                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-[var(--bg-hover)] text-[var(--text-muted)] font-medium hidden lg:inline">
                        نظامي
                    </span>
                @endif
            </div>
        </div>

        <!-- Right Column: Nature, Type, Balance, Actions -->
        <div class="flex items-center gap-3 md:gap-5 flex-shrink-0">
            <!-- Account Nature (Dr/Cr) -->
            <span class="text-[11px] font-mono px-2 py-0.5 rounded border border-[var(--border-color)] bg-[var(--bg-surface-alt)] text-[var(--text-muted)] hidden xl:inline-block">
                {{ $account->nature === 'debit' ? 'مدين (Dr)' : 'دائن (Cr)' }}
            </span>

            <!-- Account Type Badge -->
            <span class="erp-badge {{ $account->type_badge_class }} text-[10px] py-0.5 px-2 hidden sm:inline-flex">
                {{ $account->type_label }}
            </span>

            <!-- Balance Value -->
            <div class="text-left font-numeric font-bold text-xs sm:text-sm min-w-[110px] md:min-w-[130px] flex items-center justify-end gap-1">
                @if($isZero)
                    <span class="text-[var(--text-light)] font-normal">0.00</span>
                    <span class="text-[10px] text-[var(--text-light)] font-normal">ج.م</span>
                @elseif($isPositive)
                    <span class="text-[var(--success-text)] font-black">
                        {{ number_format($balance, 2) }}
                    </span>
                    <span class="text-[10px] text-[var(--text-muted)] font-normal">ج.م</span>
                @else
                    <span class="text-[var(--danger)] font-black">
                        ({{ number_format(abs($balance), 2) }})
                    </span>
                    <span class="text-[10px] text-[var(--danger)] font-bold">عجز</span>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-1">
                <!-- View Account General Ledger -->
                <a href="{{ route('accounting.accounts.ledger', $account->id) }}" 
                   class="p-1.5 rounded text-[var(--text-muted)] hover:text-[var(--success)] hover:bg-[var(--success-soft)] transition-colors" 
                   title="كشف حساب الأستاذ العام [{{ $account->name }}]">
                    <x-icon name="eye" size="14" />
                </a>

                <!-- View Account Entries in Journal -->
                <a href="{{ route('accounting.journal-entries', ['search' => $account->code]) }}" 
                   class="p-1.5 rounded text-[var(--text-muted)] hover:text-[var(--primary)] hover:bg-[var(--primary-soft)] transition-colors" 
                   title="عرض دفتر قيود وسجل حركات الحساب [{{ $account->code }}]">
                    <x-icon name="file-text" size="14" />
                </a>

                <!-- Add Sub-Account Button -->
                @if($account->level < 4)
                    <button type="button" 
                            class="p-1.5 rounded text-[var(--primary)] hover:bg-[var(--primary-soft)] transition-colors" 
                            title="إضافة حساب فرعي متفرع من [{{ $account->name }}]"
                            onclick="openAddSubAccountModal('{{ $account->id }}', '{{ $account->code }} - {{ $account->name }}')">
                        <x-icon name="plus" size="14" />
                    </button>
                @else
                    <span class="w-[26px]"></span>
                @endif
            </div>
        </div>
    </div>
</div>

@if($hasChildren)
    <div id="children-{{ $account->id }}" class="coa-children account-children">
        @foreach($account->children as $child)
            @include('accounting.partials.account-node', ['account' => $child, 'level' => $level + 1])
        @endforeach
    </div>
@endif
