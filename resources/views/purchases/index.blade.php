@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="فواتير الشراء والتوريد" :breadcrumbs="['المشتريات والموردين' => '#', 'فواتير الشراء' => '#']">
        <x-slot:actions>
            <a href="{{ route('purchases.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>فاتورة شراء جديدة</span>
            </a>
            <a href="{{ route('purchase-returns.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="rotate-ccw" size="16" />
                <span>تسجيل مرتجع مشتريات</span>
            </a>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="factory" size="16" />
                <span>دليل الموردين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="shopping-bag" size="14" />
                <span>إجمالي الفواتير: {{ $metrics['total_count'] }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchases-index">
        <!-- Enterprise Sub-Navigation Tabs Bar (Faithful to Kayan ERP Design System) -->
        <div class="erp-card erp-tab-nav-card mb-4">
            <nav class="erp-tab-bar" aria-label="أقسام فواتير ومرتجعات الشراء">
                <a href="{{ route('purchases.index') }}" class="erp-tab-link active">
                    <x-icon name="shopping-bag" size="16" />
                    <span>فواتير الشراء والتوريد</span>
                    <span class="erp-badge erp-badge-neutral mr-1.5 px-2 py-0.5 text-xs font-bold font-numeric">
                        {{ $metrics['total_count'] }}
                    </span>
                </a>
                <a href="{{ route('purchase-returns.index') }}" class="erp-tab-link">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>مرتجعات المشتريات</span>
                </a>
            </nav>
        </div>

        <!-- Status Alerts -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Summary KPI Card -->
        <div class="erp-card mb-4 p-4">
            <div class="purchases-kpi-row">
                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap">
                        <x-icon name="shopping-bag" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">إجمالي فواتير الشراء</div>
                        <div class="purchases-kpi-value font-numeric">{{ $metrics['total_count'] }}</div>
                        <div class="purchases-kpi-desc">جميع الفواتير المسجلة</div>
                    </div>
                </div>

                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap success">
                        <x-icon name="check-circle" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">مشتريات مُرحلة للمخازن</div>
                        <div class="purchases-kpi-value font-numeric text-[var(--success)]">{{ number_format($metrics['posted_total'], 2) }} ج.م</div>
                        <div class="purchases-kpi-desc">تم إيداعها وتوليد دفعات FIFO</div>
                    </div>
                </div>

                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap warning">
                        <x-icon name="file-text" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">مسودات قيد الإعداد</div>
                        <div class="purchases-kpi-value font-numeric text-[var(--warning)]">{{ $metrics['draft_count'] }}</div>
                        <div class="purchases-kpi-desc">تتطلب مراجعة وترحيل</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="erp-card mb-4 p-3">
            <form action="{{ route('purchases.index') }}" method="GET" class="erp-filter-form">
                <div class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $currentSearch }}" 
                            class="erp-input w-full pr-8" 
                            placeholder="بحث برقم الفاتورة أو اسم المورد..."
                        >
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                            <x-icon name="search" size="15" />
                        </span>
                    </div>
                </div>

                <div class="w-40 min-w-[140px]">
                    <select name="status" class="erp-input w-full">
                        <option value="">-- كل الحالات --</option>
                        <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="posted" {{ $currentStatus === 'posted' ? 'selected' : '' }}>مرحلة للمخازن</option>
                        <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>

                <div class="w-48 min-w-[160px]">
                    <select name="warehouse_id" class="erp-input w-full">
                        <option value="">-- كل المستودعات --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$currentWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-48 min-w-[160px]">
                    <select name="supplier_id" class="erp-input w-full">
                        <option value="">-- كل الموردين --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ (string)$currentSupplierId === (string)$sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="erp-btn erp-btn-secondary">
                    <x-icon name="filter" size="15" />
                    <span>تصفية</span>
                </button>

                @if($currentSearch || $currentStatus || $currentWarehouseId || $currentSupplierId)
                    <a href="{{ route('purchases.index') }}" class="erp-btn erp-btn-ghost text-xs text-[var(--danger)]">
                        <x-icon name="x" size="14" />
                        <span>إلغاء التصفية</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Invoices Data Table -->
        <div class="erp-card overflow-hidden">

            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>المورد</th>
                            <th>تاريخ الفاتورة</th>
                            <th>مستودع الاستلام</th>
                            <th>عدد البنود</th>
                            <th>مصاريف الشحن</th>
                            <th>إجمالي الفاتورة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.show', $inv) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="font-semibold text-xs text-[var(--text-main)]">{{ $inv->supplier->name ?? 'غير محدد' }}</div>
                                    <small class="text-[11px] text-[var(--text-muted)]">{{ $inv->supplier->country ?? '' }}</small>
                                </td>
                                <td class="font-numeric text-xs">
                                    {{ $inv->invoice_date->format('Y-m-d') }}
                                </td>
                                <td>
                                    <span class="text-xs">{{ $inv->warehouse->name ?? '—' }}</span>
                                </td>
                                <td class="font-numeric text-center text-xs">
                                    {{ $inv->items->count() }}
                                </td>
                                <td class="font-numeric text-xs">
                                    {{ number_format((float)$inv->shipping_cost, 2) }} ج.م
                                </td>
                                <td class="font-numeric font-bold text-xs text-[var(--text-main)]">
                                    {{ number_format((float)$inv->total_amount, 2) }} ج.م
                                </td>
                                <td>
                                    <span class="erp-badge {{ $inv->status_badge_class }}">
                                        {{ $inv->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('purchases.show', $inv) }}" class="erp-btn erp-btn-ghost erp-btn-icon" title="عرض التفاصيل">
                                            <x-icon name="eye" size="14" />
                                        </a>

                                        <!-- Print Combobox Dropdown -->
                                        <x-print-dropdown 
                                            size="sm"
                                            title="طباعة"
                                            code="{{ $inv->invoice_number }}"
                                            urlThermal="{{ route('purchases.print', [$inv, 'format' => 'thermal_80mm']) }}"
                                            directThermal="{{ route('purchases.direct-print', [$inv, 'format' => 'thermal_80mm']) }}"
                                            urlA4="{{ route('purchases.print', [$inv, 'format' => 'a4']) }}"
                                            urlA5="{{ route('purchases.print', [$inv, 'format' => 'a5']) }}"
                                        />

                                        @if($inv->status === 'draft')
                                            <form action="{{ route('purchases.post', $inv) }}" method="POST" data-confirm="هل أنت متأكد من ترحيل هذه الفاتورة وإيداع البضاعة في المستودع؟">
                                                @csrf
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--success)]" title="ترحيل للمخازن">
                                                    <x-icon name="check-circle" size="14" />
                                                </button>
                                            </form>
                                            <form action="{{ route('purchases.cancel', $inv) }}" method="POST" data-confirm="هل أنت متأكد من إلغاء هذه المسودة؟">
                                                @csrf
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon text-amber-600 hover:text-amber-700" title="إلغاء الفاتورة">
                                                    <x-icon name="x-circle" size="14" />
                                                </button>
                                            </form>
                                        @endif

                                        @if($inv->status !== 'posted')
                                            <form action="{{ route('purchases.destroy', $inv) }}" method="POST" class="inline" data-confirm="هل أنت متأكد من مسح فاتورة الشراء (#{{ $inv->invoice_number }}) نهائياً؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon text-rose-600 hover:text-rose-700" title="مسح الفاتورة نهائياً">
                                                    <x-icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-[var(--text-muted)]">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-icon name="shopping-bag" size="36" class="opacity-40" />
                                        <p class="m-0 text-sm font-semibold">لا توجد فواتير شراء مسجلة تطابق معايير البحث.</p>
                                        <a href="{{ route('purchases.create') }}" class="erp-btn erp-btn-primary text-xs mt-1">
                                            <x-icon name="plus" size="14" />
                                            <span>إنشاء فاتورة شراء جديدة</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="p-3 border-t border-[var(--border-color)]">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
