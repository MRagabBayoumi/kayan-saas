<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تقرير نواقص المخزون وتنبيهات إعادة الطلب - {{ $company?->company_name ?? config('app.name', 'كيان ERP') }}</title>

    @if(!empty($company?->logo_url))
        <link rel="icon" type="image/png" href="{{ $company->logo_url }}">
        <link rel="apple-touch-icon" href="{{ $company->logo_url }}">
    @endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body data-format="{{ request('format', 'a4') }}" data-auto-print="false">

    <!-- Screen-Only Navigation & Print Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>طباعة تقرير نواقص وتنبيهات المخزون</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ request('format', 'a4') === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ request('format') === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                {{ count($products) }} صنف ناقص
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="python-direct-print" data-url="{{ route('inventory.low-stock.direct-print', ['category_id' => $selectedCategory?->id]) }}" class="toolbar-btn toolbar-btn-primary" title="طباعة مباشرة عبر بايثون">
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

    @php
        $totalShortageQty = 0;
        $totalEstimatedCost = 0;
        foreach($products as $p) {
            $cStock = (float) ($p->stock_balances_sum_quantity ?? 0);
            $mAlert = (int) $p->min_stock_alert;
            $def = max(0, $mAlert - $cStock);
            if ($def == 0 && $cStock <= 0) $def = 1;
            $totalShortageQty += $def;
            $totalEstimatedCost += ($def * (float)$p->base_cost_price);
        }
    @endphp

    <!-- Document Paper Sheet Container -->
    <main class="invoice-page-container">
        <article class="invoice-sheet" data-format="{{ request('format', 'a4') }}">

            @if(!empty($company?->logo_url) || !empty($company?->company_name))
                <div class="print-company-bar">
                    <div>
                        <h2 class="print-company-name">{{ $company?->company_name ?? 'شركة كيان' }}</h2>
                        @if(!empty($company?->company_phone))
                            <div class="print-company-phone">هاتف: <span class="font-numeric">{{ $company->company_phone }}</span></div>
                        @endif
                    </div>
                    @if(!empty($company?->logo_url))
                        <div>
                            <img src="{{ $company->logo_url }}" alt="Logo" class="print-company-logo">
                        </div>
                    @endif
                </div>
            @endif

            <!-- 1. Enterprise Dispatch Header (A4 / A5 Layout) -->
            <header class="dispatch-header">
                <!-- Right: Report Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">تقرير نواقص المخزون</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">نوع التقرير :</span>
                        <strong class="dispatch-field-val">تنبيهات حد الأمان وإعادة الطلب</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">التصنيف :</span>
                        <span class="dispatch-field-val">{{ $selectedCategory?->name ?: 'كافة التصنيفات' }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">عدد الأصناف :</span>
                        <strong class="dispatch-field-val font-numeric">{{ count($products) }} صنف</strong>
                    </div>
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">طلب توريد عاجل</span>
                        <span class="stamp-date font-numeric">{{ now()->format('d/m/Y') }}</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left: Date & Time, Estimated Cost -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ التقرير :</span>
                        <span class="dispatch-field-val font-numeric">{{ now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">إجمالي العجز :</span>
                        <strong class="dispatch-field-val font-numeric text-danger">{{ number_format($totalShortageQty) }} قطعة</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">التكلفة التقديرية :</span>
                        <strong class="dispatch-field-val font-numeric">{{ number_format($totalEstimatedCost, 2) }} ج.م</strong>
                    </div>
                </div>
            </header>

            <!-- 2. Solid Grid Table -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-seq">م</th>
                        <th class="col-sku">كود الصنف</th>
                        <th class="col-name">اسم الصنف</th>
                        <th>التصنيف</th>
                        <th class="col-unit">الوحدة</th>
                        <th class="w-[75px] text-center">الرصيد الفعلي</th>
                        <th class="w-[70px] text-center">حد الأمان</th>
                        <th class="w-[80px] text-center">العجز المطلوب</th>
                        <th class="col-price">التكلفة التقديرية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $product)
                        @php
                            $currentStock = (float) ($product->stock_balances_sum_quantity ?? 0);
                            $minAlert = (int) $product->min_stock_alert;
                            $deficit = max(0, $minAlert - $currentStock);
                            if ($deficit == 0 && $currentStock <= 0) $deficit = 1;
                            $itemEstCost = $deficit * (float) $product->base_cost_price;
                        @endphp
                        <tr>
                            <td class="col-seq font-numeric">{{ $idx + 1 }}</td>
                            <td class="col-sku font-numeric">{{ $product->sku }}</td>
                            <td class="col-name">
                                <strong>{{ $product->name }}</strong>
                            </td>
                            <td>{{ $product->category?->name ?? 'عام' }}</td>
                            <td class="col-unit">{{ $product->unit }}@if(($product->unit_factor ?? 1) > 1) ({{ (int)$product->unit_factor }} قطعة)@endif</td>
                            <td class="font-numeric text-center font-bold {{ $currentStock <= 0 ? 'text-red-700' : 'text-amber-700' }}">
                                {{ $product->formatStockBreakdown($currentStock) }}
                            </td>
                            <td class="font-numeric text-center">{{ $minAlert }}</td>
                            <td class="font-numeric text-center font-extrabold text-red-700">
                                +{{ number_format($deficit) }}
                            </td>
                            <td class="col-price font-numeric">{{ number_format($itemEstCost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-5">
                                لا توجد نواقص في المخزون حالياً. جميع الأصناف تتجاوز حد الأمان.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($products) > 0)
                    <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="5" class="text-right font-extrabold p-1.5">الإجمالي العام:</td>
                            <td class="font-numeric text-center font-extrabold">{{ number_format($products->sum('stock_balances_sum_quantity')) }}</td>
                            <td class="font-numeric text-center font-extrabold">{{ number_format($products->sum('min_stock_alert')) }}</td>
                            <td class="font-numeric text-center font-extrabold text-red-700">+{{ number_format($totalShortageQty) }}</td>
                            <td class="col-price font-numeric font-extrabold">{{ number_format($totalEstimatedCost, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <!-- 3. Bottom Info Bar (اسم المستخدم، الصفحات، تاريخ ووقت الطباعة) -->
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

        </article>
    </main>

</body>
</html>
