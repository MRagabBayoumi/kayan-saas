@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل حركات المخزون" :breadcrumbs="['المخزون والمنتجات' => route('inventory.index'), 'سجل الحركات' => route('inventory.movements')]">
        <x-slot:actions>
            <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-primary">
                <x-icon name="arrow-left-right" size="16" />
                <span>نظرة عامة وتسجيل حركة</span>
            </a>
            <a href="{{ route('inventory.low-stock') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="alert-triangle" size="16" />
                <span>نواقص المخزون</span>
            </a>
            <a href="{{ route('warehouses.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="home" size="16" />
                <span>المستودعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الحركات:</span>
                <strong>{{ $movements->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <!-- Search & Filters Bar -->
    <div class="erp-card mb-4 p-3">
        <form action="{{ route('inventory.movements') }}" method="GET" class="erp-filter-form">
            <!-- Search -->
            <div class="flex-1 min-w-[220px]">
                <input 
                    type="search" 
                    name="search" 
                    value="{{ $searchTerm }}" 
                    class="erp-input font-numeric w-full" 
                    placeholder="بحث برقم الحركة، كود الصنف، أو اسم الصنف...">
            </div>

            <!-- Type Filter -->
            <div class="min-w-[160px]">
                <select name="type" class="erp-input" data-action="auto-submit">
                    <option value="">جميع أنواع الحركات</option>
                    <option value="in" {{ $selectedType === 'in' ? 'selected' : '' }}>إيداع / وارد</option>
                    <option value="out" {{ $selectedType === 'out' ? 'selected' : '' }}>صرف / منصرف</option>
                    <option value="transfer" {{ $selectedType === 'transfer' ? 'selected' : '' }}>تحويل مخزني</option>
                    <option value="adjustment" {{ $selectedType === 'adjustment' ? 'selected' : '' }}>تسوية جردية</option>
                </select>
            </div>

            <!-- Warehouse Filter -->
            <div class="min-w-[180px]">
                <select name="warehouse_id" class="erp-input" data-action="auto-submit">
                    <option value="">جميع المستودعات</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $selectedWarehouseId == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="erp-btn erp-btn-secondary">
                <x-icon name="search" size="14" />
                <span>تصفية</span>
            </button>

            @if($searchTerm || $selectedType || $selectedWarehouseId)
                <a href="{{ route('inventory.movements') }}" class="erp-btn erp-btn-secondary" title="إلغاء الفلاتر">
                    ✕ إعادة ضبط
                </a>
            @endif
        </form>
    </div>

    <!-- Movements Table -->
    <div class="erp-card overflow-x-auto mb-5">
        <table class="erp-table w-full">
            <thead>
                <tr>
                    <th class="w-[50px]">#</th>
                    <th>رقم الحركة</th>
                    <th>التاريخ والوقت</th>
                    <th class="text-center">نوع الحركة</th>
                    <th>الصنف</th>
                    <th>المستودع المصدر / الوجهة</th>
                    <th class="text-center">الكمية</th>
                    <th class="text-left">سعر الوحدة</th>
                    <th>المرجع / ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="font-numeric text-[var(--text-muted)]">{{ $loop->iteration + ($movements->currentPage() - 1) * $movements->perPage() }}</td>
                        <td>
                            <span class="font-numeric font-bold text-[var(--text-main)]">{{ $m->movement_number }}</span>
                        </td>
                        <td class="font-numeric text-xs text-[var(--text-secondary)]">
                            {{ $m->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="text-center">
                            <span class="erp-badge {{ $m->type_badge_class }}">
                                {{ $m->type_label }}
                            </span>
                        </td>
                        <td>
                            <div class="font-semibold text-[var(--text-main)]">{{ $m->product?->name }}</div>
                            <span class="font-numeric text-[11px] text-[var(--text-muted)]">[{{ $m->product?->sku }}]</span>
                        </td>
                        <td>
                            @if($m->type === 'transfer')
                                <div class="flex items-center gap-1.5 text-xs">
                                    <span>{{ $m->fromWarehouse?->name ?: '—' }}</span>
                                    <span class="text-[var(--text-muted)]">←</span>
                                    <strong>{{ $m->toWarehouse?->name ?: '—' }}</strong>
                                </div>
                            @elseif($m->type === 'in')
                                <span class="text-movement-in font-semibold">{{ $m->toWarehouse?->name ?: '—' }}</span>
                            @else
                                <span class="text-movement-out font-semibold">{{ $m->fromWarehouse?->name ?: '—' }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="font-numeric text-sm font-extrabold {{ $m->type === 'in' ? 'text-movement-in' : ($m->type === 'out' ? 'text-movement-out' : 'text-[var(--text-main)]') }}">
                                {{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '-' : '') }}{{ number_format($m->quantity) }}
                            </span>
                            <span class="text-[11px] text-[var(--text-muted)]">قطعة</span>
                            @if($m->product && ($m->product->unit_factor ?? 1) > 1 && $m->product->unit && $m->product->unit !== 'قطعة')
                                <div class="text-[10px] text-[var(--text-muted)] font-numeric">({{ $m->product->formatStockBreakdown((float)$m->quantity) }})</div>
                            @endif
                        </td>
                        <td class="text-left font-numeric text-xs">
                            {{ number_format($m->unit_cost, 2) }} ج.م
                        </td>
                        <td class="text-xs text-[var(--text-secondary)] max-w-[200px]">
                            {{ $m->notes ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center p-9 text-[var(--text-muted)]">
                            <x-icon name="arrow-left-right" size="36" class="mb-2 opacity-50 inline-block" />
                            <div>لا توجد حركات مخزنية مسجلة حتى الآن</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($movements->hasPages())
        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    @endif
@endsection
