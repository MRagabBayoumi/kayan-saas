@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل قوائم التعبئة والتجهيز (Packing Lists Archive)" :breadcrumbs="['المخزون والمنتجات' => route('products.index'), 'صانع قائمة التعبئة' => route('packing-list.index'), 'سجل القوائم المحفوظة' => '#']">
        <x-slot:actions>
            <a href="{{ route('packing-list.index') }}" class="erp-btn erp-btn-primary font-bold">
                <x-icon name="plus" size="16" />
                <span>إنشاء قائمة تعبئة جديدة</span>
            </a>
            <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للأصناف</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                إجمالي القوائم: {{ $packingLists->total() }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div data-page="packing-list-archive" class="space-y-4">

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="erp-alert erp-alert-success mb-3" role="alert">
            <x-icon name="check-circle" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- 1. KPI Summary Cards -->
    <div class="inventory-kpi-grid">
        <div class="erp-card inventory-kpi-card mb-0">
            <div class="inventory-kpi-icon primary">
                <x-icon name="clipboard" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي القوائم المحفوظة</div>
                <div class="font-numeric text-xl font-extrabold text-[var(--text-main)] mt-0.5">{{ number_format($metrics['total_lists']) }}</div>
            </div>
        </div>

        <div class="erp-card inventory-kpi-card mb-0">
            <div class="inventory-kpi-icon info">
                <x-icon name="package" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي الوحدات المجهزة</div>
                <div class="font-numeric text-xl font-extrabold text-[#0284c7] mt-0.5">{{ number_format($metrics['total_quantity'], 2) }}</div>
            </div>
        </div>

        <div class="erp-card inventory-kpi-card mb-0">
            <div class="inventory-kpi-icon warning">
                <x-icon name="box" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي الكراتين والطرود</div>
                <div class="font-numeric text-xl font-extrabold text-[#f59e0b] mt-0.5">{{ number_format($metrics['total_packages']) }}</div>
            </div>
        </div>

        <div class="erp-card inventory-kpi-card mb-0">
            <div class="inventory-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <x-icon name="dollar-sign" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي القيمة التقديرية</div>
                <div class="font-numeric text-xl font-extrabold text-emerald-600 mt-0.5">{{ number_format($metrics['total_amount'], 2) }} ج.م</div>
            </div>
        </div>
    </div>

    <!-- 2. Search & Filter Bar -->
    <div class="erp-card">
        <div class="erp-card-body p-4">
            <form method="GET" action="{{ route('packing-list.archive') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[240px]">
                    <label class="erp-form-label text-xs font-semibold mb-1">بحث بالعميل، رقم القائمة، أو الملاحظات</label>
                    <div class="relative w-full">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            class="erp-input text-xs font-medium" 
                            placeholder="ابحث باسم العميل (مثال: الحافظ) أو كود القائمة..."
                        >
                    </div>
                </div>

                <div class="w-40">
                    <label class="erp-form-label text-xs font-semibold mb-1">من تاريخ</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="erp-input text-xs font-numeric">
                </div>

                <div class="w-40">
                    <label class="erp-form-label text-xs font-semibold mb-1">إلى تاريخ</label>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="erp-input text-xs font-numeric">
                </div>

                <div class="w-36">
                    <label class="erp-form-label text-xs font-semibold mb-1">الحالة</label>
                    <select name="status" class="erp-input erp-select text-xs">
                        <option value="">كافة الحالات</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="prepared" {{ $status === 'prepared' ? 'selected' : '' }}>تم التجهيز</option>
                        <option value="shipped" {{ $status === 'shipped' ? 'selected' : '' }}>تم الشحن</option>
                        <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary text-xs">
                        <x-icon name="search" size="14" />
                        <span>تصفية</span>
                    </button>
                    @if($search || $fromDate || $toDate || $status)
                        <a href="{{ route('packing-list.archive') }}" class="erp-btn erp-btn-ghost text-xs text-rose-600">
                            <x-icon name="x" size="14" />
                            <span>إلغاء الفلتر</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Saved Packing Lists Table -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="list" size="16" class="text-[var(--primary)]" />
                <span>قوائم التعبئة المسجلة بالسجل</span>
            </div>
            <div class="text-xs text-[var(--text-muted)]">
                💡 يمكنك فتح أي قائمة سابقة لإعادة تعديلها، استكمالها، أو طباعتها وتصديرها إكسيل.
            </div>
        </div>

        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="w-36 text-center">رقم القائمة</th>
                            <th class="w-28 text-center">التاريخ</th>
                            <th class="min-w-[180px]">العميل / المستلم</th>
                            <th class="w-28 text-center">المستودع</th>
                            <th class="w-24 text-center">الأصناف</th>
                            <th class="w-28 text-center">إجمالي الكمية</th>
                            <th class="w-24 text-center">الطرود</th>
                            <th class="w-32 text-center">القيمة التقديرية</th>
                            <th class="w-28 text-center">الحالة</th>
                            <th class="w-48 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packingLists as $idx => $pl)
                            <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                                <td class="text-center font-numeric text-[var(--text-muted)]">
                                    {{ $packingLists->firstItem() + $idx }}
                                </td>
                                <td class="text-center font-mono font-bold text-[var(--primary)]">
                                    <a href="{{ route('packing-list.index', ['load_id' => $pl->id]) }}" class="hover:underline" title="فتح وتعديل القائمة">
                                        {{ $pl->packing_number }}
                                    </a>
                                </td>
                                <td class="text-center font-numeric text-xs">
                                    {{ $pl->packing_date->format('Y-m-d') }}
                                </td>
                                <td>
                                    <div class="font-bold text-[var(--text-main)]">{{ $pl->customer_name ?: 'عميل عام' }}</div>
                                    @if($pl->notes)
                                        <div class="text-[11px] text-[var(--text-muted)] truncate max-w-xs">{{ $pl->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-center text-xs text-[var(--text-muted)]">
                                    {{ $pl->warehouse_name ?: 'المخزن الرئيسي' }}
                                </td>
                                <td class="text-center font-numeric font-bold">
                                    <span class="erp-badge erp-badge-neutral text-xs">{{ $pl->items_count }} صنف</span>
                                </td>
                                <td class="text-center font-numeric font-bold text-[#0284c7]">
                                    {{ number_format((float) $pl->total_quantity, 2) }}
                                </td>
                                <td class="text-center font-numeric">
                                    {{ $pl->total_packages > 0 ? number_format($pl->total_packages) : '—' }}
                                </td>
                                <td class="text-center font-numeric font-bold text-emerald-600">
                                    {{ $pl->total_amount > 0 ? number_format((float) $pl->total_amount, 2).' ج.م' : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($pl->status === 'delivered')
                                        <span class="erp-badge erp-badge-success text-[11px]">تم التسليم</span>
                                    @elseif($pl->status === 'shipped')
                                        <span class="erp-badge erp-badge-info text-[11px]">تم الشحن</span>
                                    @elseif($pl->status === 'prepared')
                                        <span class="erp-badge erp-badge-warning text-[11px]">تم التجهيز</span>
                                    @else
                                        <span class="erp-badge erp-badge-neutral text-[11px]">مسودة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit / Open in Builder -->
                                        <a href="{{ route('packing-list.index', ['load_id' => $pl->id]) }}" class="erp-btn erp-btn-secondary erp-btn-sm" title="فتح وتعديل في المحرر">
                                            <x-icon name="edit" size="14" />
                                            <span>فتح</span>
                                        </a>

                                        <!-- Direct Print Dropdown -->
                                        <div class="erp-dropdown">
                                            <button type="button" class="erp-btn erp-btn-secondary erp-btn-sm" data-dropdown-toggle="printMenu_{{ $pl->id }}" title="طباعة مباشرة">
                                                <x-icon name="printer" size="14" />
                                            </button>
                                            <div id="printMenu_{{ $pl->id }}" class="erp-dropdown-menu">
                                                <a href="{{ route('packing-list.print-model', [$pl, 'format' => 'a4']) }}" target="_blank" class="erp-dropdown-item">
                                                    <x-icon name="file-text" size="14" />
                                                    <span>طباعة A4 قياسي</span>
                                                </a>
                                                <a href="{{ route('packing-list.print-model', [$pl, 'format' => 'a5']) }}" target="_blank" class="erp-dropdown-item">
                                                    <x-icon name="file" size="14" />
                                                    <span>طباعة A5 وسط</span>
                                                </a>
                                                <a href="{{ route('packing-list.print-model', [$pl, 'format' => 'thermal_80mm']) }}" target="_blank" class="erp-dropdown-item">
                                                    <x-icon name="printer" size="14" />
                                                    <span>طباعة 80 مم حراري</span>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Direct Excel Export -->
                                        <a href="{{ route('packing-list.export-excel-model', $pl) }}" class="erp-btn erp-btn-success erp-btn-sm" title="تصدير إكسيل">
                                            <x-icon name="download" size="14" />
                                        </a>

                                        <!-- Delete with Confirmation -->
                                        <form method="POST" action="{{ route('packing-list.destroy', $pl) }}" onsubmit="return confirm('هل أنت متأكد من حذف قائمة التعبئة رقم {{ $pl->packing_number }} نهائياً من السجل؟');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="erp-btn erp-btn-ghost erp-btn-sm text-rose-600 hover:text-rose-800" title="حذف">
                                                <x-icon name="trash-2" size="14" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-[var(--text-muted)]">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-icon name="clipboard" size="36" class="text-gray-300 dark:text-gray-600" />
                                        <span class="text-sm font-semibold">لم يتم العثور على أي قوائم تعبئة سابقة</span>
                                        @if($search)
                                            <span class="text-xs">جرب البحث بكلمة أخرى أو إلغاء الفلتر</span>
                                        @else
                                            <span class="text-xs">قم بإنشاء وحفظ أول قائمة تعبئة عبر زر "إنشاء قائمة تعبئة جديدة" بالأعلى</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($packingLists->hasPages())
                <div class="p-3 border-t border-[var(--border-color)]">
                    {{ $packingLists->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
