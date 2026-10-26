<!DOCTYPE html>
<html lang="ar" dir="rtl" class="{{ $colorClass ?? 'emerald' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>

    <!-- Google Fonts: Cairo -->
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body data-format="{{ $format }}" data-auto-print="{{ $autoPrint ? 'true' : 'false' }}">

    <!-- Screen-Only Navigation & Print Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>طباعة كشف حركة الصنف: {{ $product->name }}</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" class="toolbar-format-btn {{ $format === 'thermal_80mm' ? 'active' : '' }}">
                    80 مم حراري
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                {{ $movementsCount }} حركات مسجلة · الرصيد: {{ number_format((float)$product->total_stock, 2) }} {{ $product->unit }}
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="python-direct-print" data-url="{{ route('products.ledger.direct-print', $product) }}" class="toolbar-btn toolbar-btn-primary" title="طباعة مباشرة عبر بايثون">
                <x-icon name="printer" size="16" />
                <span>طباعة بايثون المباشرة</span>
            </button>
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة المتصفح</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- Document Paper Sheet Container -->
    <main class="invoice-page-container">
        <article 
            class="invoice-sheet" 
            data-format="{{ $format }}" 
            data-density="{{ $template->getSetting('table_columns.density', 'comfortable') }}"
            data-font-size="{{ $template->getSetting('general.font_size', 'md') }}"
        >
        @if($format !== 'thermal_80mm')
            <!-- 1. Enterprise Dispatch Header (A4 / A5 Layout) -->
            <header class="dispatch-header">
                <!-- Right: Product Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">كشف حركة مخزنية</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">كود الصنف :</span>
                        <strong class="dispatch-field-val font-numeric">{{ $product->sku }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">اسم الصنف :</span>
                        <strong class="dispatch-field-val">{{ $product->name }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">التصنيف والوحدة :</span>
                        <span class="dispatch-field-val">{{ $product->category->name ?? 'عام' }} ({{ $product->unit }})</span>
                    </div>
                    @if(!empty($product->barcode))
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الباركود :</span>
                        <span class="dispatch-field-val font-numeric">{{ $product->barcode }}</span>
                    </div>
                    @endif
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">كشف حركة الصنف المخزنية</span>
                        <span class="stamp-date font-numeric">{{ now()->format('d/m/Y') }}</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left: Date & Time, Scope, Stock -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ التقرير :</span>
                        <span class="dispatch-field-val font-numeric">{{ now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المستودع :</span>
                        <span class="dispatch-field-val">{{ $selectedWarehouse?->name ?: 'كافة الفروع' }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">نوع الحركة :</span>
                        <span class="dispatch-field-val">
                            @if($currentType === 'in')
                                وارد / إيداع فقط
                            @elseif($currentType === 'out')
                                منصرف / مبيعات فقط
                            @elseif($currentType === 'transfer')
                                تحويلات فقط
                            @elseif($currentType === 'adjustment')
                                تسويات جردية فقط
                            @else
                                كافة الحركات
                            @endif
                        </span>
                    </div>
                    @if($currentDateFrom || $currentDateTo)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الفترة :</span>
                        <span class="dispatch-field-val font-numeric">{{ $currentDateFrom ?: 'البداية' }} إلي {{ $currentDateTo ?: 'الآن' }}</span>
                    </div>
                    @endif
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الرصيد الفعلي :</span>
                        <strong class="dispatch-field-val font-numeric">{{ $product->formatStockBreakdown() }}</strong>
                    </div>
                </div>
            </header>

            <!-- 2. KPI Summary Bar -->
            <div class="dispatch-bottom-section mb-2">
                <div class="dispatch-notes-area flex gap-4 text-xs font-bold">
                    <span>إجمالي الوارد: <strong class="font-numeric text-in">+{{ number_format($totalIn, 2) }}</strong></span>
                    <span>إجمالي المنصرف: <strong class="font-numeric text-out">-{{ number_format($totalOut, 2) }}</strong></span>
                    <span>عدد الحركات: <strong class="font-numeric">{{ $movementsCount }}</strong></span>
                </div>
            </div>

            <!-- 3. Solid Grid Movements Table -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-seq">م</th>
                        <th class="w-[110px]">التاريخ والوقت</th>
                        <th class="w-[85px]">نوع الحركة</th>
                        <th class="col-wh">المخزن</th>
                        <th class="w-[80px]">الكمية</th>
                        <th class="w-[90px]">الرصيد بعدها</th>
                        <th>البيان / رقم المرجع</th>
                        <th class="w-[90px]">المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $idx => $m)
                        <tr>
                            <td class="col-seq font-numeric">{{ $idx + 1 }}</td>
                            <td class="font-numeric text-center">{{ \Carbon\Carbon::parse($m->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-center">
                                @if($m->type === 'in')
                                    <span class="text-in">وارد (+)</span>
                                @elseif($m->type === 'out')
                                    <span class="text-out">منصرف (-)</span>
                                @elseif($m->type === 'transfer')
                                    <span>تحويل</span>
                                @else
                                    <span>تسوية</span>
                                @endif
                            </td>
                            <td class="col-wh">{{ $m->warehouse?->code ?? $m->warehouse?->name ?? '20' }}</td>
                            <td class="font-numeric text-center font-bold">
                                {{ $m->type === 'out' ? '-' : '+' }}{{ number_format(abs((float)$m->quantity), 2) }}
                            </td>
                            <td class="font-numeric text-center font-bold">
                                {{ number_format((float)($m->balance_after ?? 0), 2) }}
                            </td>
                            <td>
                                @if($m->movement_number)
                                    <div class="font-numeric font-bold">{{ $m->movement_number }}</div>
                                @endif
                                <div>{{ $m->notes ?: ($m->reference_type ? class_basename($m->reference_type) . ' #' . $m->reference_id : 'حركة مخزنية') }}</div>
                            </td>
                            <td class="text-center">
                                {{ $m->user?->name ?? 'النظام' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-4">لا توجد حركات مسجلة لهذا الصنف بالمعايير الحالية</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 4. Bottom Info Bar -->
            <div class="dispatch-footer-bar">
                <div class="footer-box box-user">
                    <span class="box-label">اسم المستخدم :</span>
                    <strong class="box-val">{{ auth()->user()?->name ?? 'مدير النظام' }}</strong>
                </div>
                <div class="footer-box box-pages">
                    <span>الصفحات من 1 إلي 1</span>
                </div>
                <div class="footer-box box-time">
                    <span class="box-label">تاريخ ووقت الطباعة :</span>
                    <span class="box-val font-numeric">{{ now()->format('d/m/Y h:i a') }}</span>
                </div>
            </div>

        @else
            <!-- Thermal 80mm Compact View -->
            <header class="doc-header">
                <div class="doc-brand">
                    <h1 class="doc-company-title">{{ $company?->company_name ?: 'كيان ERP' }}</h1>
                </div>
                <div class="doc-title-block">
                    <h2 class="doc-main-title">حركة صنف</h2>
                    <div class="font-numeric font-bold text-sm">{{ $product->sku }}</div>
                    <div>{{ $product->name }}</div>
                </div>
            </header>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>الكمية</th>
                        <th>الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $m)
                        <tr>
                            <td class="font-numeric text-[9px]">{{ \Carbon\Carbon::parse($m->created_at)->format('m-d H:i') }}</td>
                            <td>{{ $m->type === 'in' ? 'وارد' : 'منصرف' }}</td>
                            <td class="font-numeric font-bold">{{ number_format((float)$m->quantity, 1) }}</td>
                            <td class="font-numeric">{{ number_format((float)($m->balance_after ?? 0), 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="doc-footer-note font-numeric">
                الرصيد الحالي: {{ $product->formatStockBreakdown() }} · {{ now()->format('Y-m-d H:i') }}
            </div>
        @endif
        </article>
    </main>

</body>
</html>
