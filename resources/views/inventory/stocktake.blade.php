@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="جرد المخزون والتسوية الدورية" :breadcrumbs="['المخزون والمنتجات' => route('inventory.index'), 'جرد المخزون' => route('inventory.stocktake')]">
        <x-slot:actions>
            <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمخزون</span>
            </a>
            @if($selectedWarehouse)
                <a href="{{ route('warehouses.show', $selectedWarehouse) }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="warehouse" size="16" />
                    <span>رصيد {{ $selectedWarehouse->name }}</span>
                </a>
                <a href="{{ route('inventory.stocktake.template', ['warehouse_id' => $selectedWarehouse->id]) }}" class="erp-btn erp-btn-success">
                    <x-icon name="download" size="16" />
                    <span>تحميل شيت الجرد للمستودع (.CSV)</span>
                </a>
            @endif
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>المستودعات المتاحة:</span>
                <strong>{{ $warehouses->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="flex flex-col gap-6">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check-circle" size="18" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="erp-alert erp-alert-danger" role="alert">
            <x-icon name="alert-triangle" size="18" />
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @php
        $preview = session('stocktake_preview');
    @endphp

    <!-- Step 1: Warehouse Selection & Instructions -->
    <div class="erp-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border-color)] pb-4 mb-4">
            <div>
                <h2 class="text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                    <x-icon name="clipboard-check" size="20" class="text-emerald-600" />
                    <span>الخطوة 1: اختيار المستودع وتنزيل كشف الجرد</span>
                </h2>
                <p class="text-xs text-[var(--text-secondary)] mt-1">
                    حدد المستودع المراد جرده لتحميل كشف مسجل به جميع الأصناف والرصيد الدفتري الحالي.
                </p>
            </div>

            <!-- Warehouse Switcher Form -->
            <form method="GET" action="{{ route('inventory.stocktake') }}" class="flex items-center gap-2">
                <label for="warehouse_id_select" class="text-xs font-semibold text-[var(--text-secondary)]">المستودع:</label>
                <select id="warehouse_id_select" name="warehouse_id" class="erp-input text-sm">
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $selectedWarehouse && $selectedWarehouse->id === $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }} ({{ $wh->code }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn erp-btn-secondary erp-btn-sm">
                    <span>اختيار</span>
                </button>
            </form>
        </div>

        @if($selectedWarehouse)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-[var(--surface-color)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">1. تنزيل الشيت الجاهز:</strong>
                    <span class="text-[var(--text-secondary)]">قم بتنزيل ملف CSV المعبأ مسبقاً ببيانات الأصناف وأرصدة الدفتر الحالية لمستودع ({{ $selectedWarehouse->name }}).</span>
                    <div class="mt-2">
                        <a href="{{ route('inventory.stocktake.template', ['warehouse_id' => $selectedWarehouse->id]) }}" class="erp-btn erp-btn-sm erp-btn-success inline-flex">
                            <x-icon name="download" size="14" />
                            <span>تنزيل الشيت الآن</span>
                        </a>
                    </div>
                </div>

                <div class="p-3 bg-[var(--surface-color)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">2. إدخال الكميات الفعلية:</strong>
                    <span class="text-[var(--text-secondary)]">قم بملء عمود (الرصيد الفعلي بعد الجرد) بالكميات التي تم عدها في المستودع. اترك الخلايا فارغة للأصناف غير المجرودة.</span>
                </div>

                <div class="p-3 bg-[var(--surface-color)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">3. احتساب الفروقات آلياً:</strong>
                    <span class="text-[var(--text-secondary)]">يقوم النظام تلقائياً باحتساب العجز والفائض والأثر المالي على تكلفة المخزون وتوليد قيود تسوية FIFO.</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Step 2: Upload Physical Count Sheet -->
    @if($selectedWarehouse)
        <div class="erp-card p-5">
            <h2 class="text-base font-bold text-[var(--text-main)] flex items-center gap-2 mb-2">
                <x-icon name="upload" size="20" class="text-blue-600" />
                <span>الخطوة 2: رفع ملف الجرد الفعلي للمراجعة والمطابقة</span>
            </h2>
            <p class="text-xs text-[var(--text-secondary)] mb-4">
                ارفع الملف بعد تعبئة الكميات الفعلية (.xlsx أو .csv). سيتم تدقيق الملف وإظهار الفروقات للمراجعة قبل اعتماد أي تسوية.
            </p>

            <form action="{{ route('inventory.stocktake.preview') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse->id }}" />

                <div class="flex-1 min-w-[280px]">
                    <label class="block text-xs font-semibold text-[var(--text-secondary)] mb-1">ملف الجرد الفعلي (.xlsx أو .csv)</label>
                    <input type="file" name="stocktake_file" accept=".xlsx,.csv,.txt" required class="erp-input w-full" />
                </div>

                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="check-circle" size="16" />
                    <span>فحص ومطابقة بيانات الجرد</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Step 3: Preview & Reconciliation Review (When Available) -->
    @if($preview && is_array($preview))
        <div class="erp-card overflow-hidden border-2 border-emerald-500">
            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-b border-emerald-200 dark:border-emerald-800 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-icon name="check-circle" size="20" class="text-emerald-600 dark:text-emerald-400" />
                    <h3 class="text-base font-bold text-emerald-900 dark:text-emerald-200">
                        نتائج مطابقة الجرد لمستودع: {{ $preview['warehouse']->name }}
                    </h3>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs font-numeric">إجمالي الأصناف: {{ $preview['summary']['total_processed'] }}</span>
                    <span class="erp-badge erp-badge-success text-xs font-numeric">مطابق: {{ $preview['summary']['matched_count'] }}</span>
                    @if($preview['summary']['discrepancies_count'] > 0)
                        <span class="erp-badge erp-badge-warning text-xs font-numeric">فروقات: {{ $preview['summary']['discrepancies_count'] }}</span>
                    @endif
                </div>
            </div>

            <div class="p-5 flex flex-col gap-6">
                <!-- Summary Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="p-4 rounded border border-[var(--border-color)] bg-[var(--surface-color)]">
                        <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">أصناف بها فائض (+)</div>
                        <div class="text-xl font-bold font-numeric text-emerald-600 dark:text-emerald-400">
                            {{ $preview['summary']['surplus_items_count'] }} صنف
                        </div>
                        <div class="text-xs text-[var(--text-muted)] mt-1 font-numeric">
                            قيمة الفائض: +{{ number_format($preview['summary']['total_surplus_cost'], 2) }} ج.م
                        </div>
                    </div>

                    <div class="p-4 rounded border border-[var(--border-color)] bg-[var(--surface-color)]">
                        <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">أصناف بها عجز (-)</div>
                        <div class="text-xl font-bold font-numeric text-rose-600 dark:text-rose-400">
                            {{ $preview['summary']['deficit_items_count'] }} صنف
                        </div>
                        <div class="text-xs text-[var(--text-muted)] mt-1 font-numeric">
                            قيمة العجز: -{{ number_format($preview['summary']['total_deficit_cost'], 2) }} ج.م
                        </div>
                    </div>

                    <div class="p-4 rounded border border-[var(--border-color)] bg-[var(--surface-color)]">
                        <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">صافي الأثر المالي للتسوية</div>
                        <div class="text-xl font-bold font-numeric {{ $preview['summary']['net_cost_impact'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $preview['summary']['net_cost_impact'] >= 0 ? '+' : '' }}{{ number_format($preview['summary']['net_cost_impact'], 2) }} ج.م
                        </div>
                        <div class="text-xs text-[var(--text-muted)] mt-1">تغير تقييم المخزون الإجمالي</div>
                    </div>

                    <div class="p-4 rounded border border-[var(--border-color)] bg-[var(--surface-color)]">
                        <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">حالة الجرد</div>
                        <div class="text-base font-bold text-[var(--text-main)]">
                            @if($preview['summary']['discrepancies_count'] === 0)
                                مطابقة تامة 100%
                            @else
                                يتطلب تسوية معتمدة
                            @endif
                        </div>
                        <div class="text-xs text-[var(--text-muted)] mt-1">
                            {{ $preview['summary']['matched_count'] }} صنف مطابق تماماً
                        </div>
                    </div>
                </div>

                <!-- Discrepancy Items Table -->
                @if(count($preview['discrepancies']) > 0)
                    <div class="border border-[var(--border-color)] rounded overflow-hidden">
                        <div class="p-3 bg-[var(--surface-color)] border-b border-[var(--border-color)] font-bold text-sm text-[var(--text-main)] flex items-center justify-between">
                            <span>جدول الفروقات الجردية المطلوب تسويتها</span>
                            <span class="text-xs font-normal text-[var(--text-secondary)]">عدد الأصناف: {{ count($preview['discrepancies']) }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>كود الصنف SKU</th>
                                        <th>اسم المنتج</th>
                                        <th>الوحدة</th>
                                        <th class="text-center">رصيد الدفتر</th>
                                        <th class="text-center">الجرد الفعلي</th>
                                        <th class="text-center">الفرق</th>
                                        <th class="text-left">سعر التكلفة</th>
                                        <th class="text-left">أثر التكلفة</th>
                                        <th>ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview['discrepancies'] as $idx => $d)
                                        <tr>
                                            <td class="font-numeric text-[var(--text-muted)]">{{ $idx + 1 }}</td>
                                            <td class="font-numeric font-bold">{{ $d['product_sku'] }}</td>
                                            <td class="font-semibold">{{ $d['product_name'] }}</td>
                                            <td>{{ $d['unit'] }}</td>
                                            <td class="text-center font-numeric">{{ number_format($d['book_balance'], 2) }}</td>
                                            <td class="text-center font-numeric font-bold">{{ number_format($d['counted_quantity'], 2) }}</td>
                                            <td class="text-center font-numeric font-bold">
                                                @if($d['difference'] > 0)
                                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($d['difference'], 2) }} (فائض)</span>
                                                @else
                                                    <span class="text-rose-600 dark:text-rose-400">{{ number_format($d['difference'], 2) }} (عجز)</span>
                                                @endif
                                            </td>
                                            <td class="text-left font-numeric">{{ number_format($d['cost_price'], 2) }} ج.م</td>
                                            <td class="text-left font-numeric font-bold {{ $d['cost_impact'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                {{ $d['cost_impact'] >= 0 ? '+' : '' }}{{ number_format($d['cost_impact'], 2) }} ج.م
                                            </td>
                                            <td>{{ $d['notes'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Reconcile Form -->
                    <form action="{{ route('inventory.stocktake.reconcile') }}" method="POST" class="p-4 bg-[var(--surface-color)] rounded border border-[var(--border-color)]">
                        @csrf
                        <input type="hidden" name="warehouse_id" value="{{ $preview['warehouse']->id }}" />
                        <input type="hidden" name="discrepancies" value="{{ json_encode($preview['discrepancies']) }}" />

                        <div class="mb-3">
                            <label for="stocktake_reason" class="block text-xs font-semibold text-[var(--text-secondary)] mb-1">
                                سبب أو بيان التسوية الجردية (اختياري، يظهر في قيود التدقيق وحركات المخزون):
                            </label>
                            <input 
                                id="stocktake_reason" 
                                type="text" 
                                name="reason" 
                                placeholder="مثال: تسوية جرد دوري معتمد لنهاية الربع السنوي" 
                                class="erp-input w-full"
                            />
                        </div>

                        <div class="flex items-center justify-between">
                            <p class="text-xs text-[var(--text-secondary)]">
                                عند التأكيد، سيتم تطبيق التسويات آلياً وتعديل رصيد المخزون وقيد حركات الدفتر وشرائح FIFO المقابلة.
                            </p>
                            <button type="submit" class="erp-btn erp-btn-success font-bold">
                                <x-icon name="check" size="16" />
                                <span>اعتماد وتسجيل التسوية الجردية فوراً</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-4 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 rounded text-center">
                        <x-icon name="check-circle" size="24" class="mx-auto mb-1 text-emerald-600" />
                        <div class="font-bold">تهانينا! جميع الأصناف المجرودة مطابقة تماماً للرصيد الدفتري للنظام.</div>
                        <div class="text-xs mt-1">لا توجد أي فروقات جردية تتطلب إنشاء قيود تسوية.</div>
                    </div>
                @endif

                <!-- Invalid Rows Warnings (If Any) -->
                @if(!empty($preview['invalid_rows']))
                    <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 rounded">
                        <div class="font-bold text-rose-800 dark:text-rose-300 text-sm mb-2 flex items-center gap-2">
                            <x-icon name="alert-triangle" size="16" />
                            <span>تنبيه: تم تجاوز {{ count($preview['invalid_rows']) }} أسطر لوجود أخطاء أو أكواد غير معرفة:</span>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th>رقم السطر</th>
                                        <th>الكود / الباركود</th>
                                        <th>اسم الصنف</th>
                                        <th>سبب الخطأ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview['invalid_rows'] as $inv)
                                        <tr>
                                            <td class="font-numeric text-center">{{ $inv['row_number'] }}</td>
                                            <td class="font-numeric">{{ $inv['sku'] ?? $inv['barcode'] ?? '—' }}</td>
                                            <td>{{ $inv['name'] ?? '—' }}</td>
                                            <td class="text-rose-600 dark:text-rose-400">{{ $inv['error'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
