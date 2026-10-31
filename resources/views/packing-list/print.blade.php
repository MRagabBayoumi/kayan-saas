<!DOCTYPE html>
<html lang="ar" dir="rtl" class="emerald">
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
                <span>طباعة قائمة التعبئة والتجهيز (Packing List)</span>
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
                {{ count($items) }} أصناف مشمولة
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة المستند</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- Document Paper Sheet Container -->
    <main class="invoice-page-container">
        <article class="invoice-sheet" data-format="{{ $format }}">

        @if($format !== 'thermal_80mm')
            <!-- =================================================================
                 A4 / A5 Enterprise Dispatch Layout
                 ================================================================= -->
            <header class="dispatch-header">
                <!-- Right: Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">بيان شحن وتجهيز بضاعة</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">رقم القائمة :</span>
                        <strong class="dispatch-field-val font-numeric font-mono">{{ $docNumber }}</strong>
                    </div>
                    @if($customerName)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المستلم / العميل :</span>
                        <strong class="dispatch-field-val">{{ $customerName }}</strong>
                    </div>
                    @endif
                    @if($warehouseName)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المستودع المصدّر :</span>
                        <span class="dispatch-field-val">{{ $warehouseName }}</span>
                    </div>
                    @endif
                </div>

                <!-- Center: Seal & Title -->
                <div class="dispatch-col-center">
                    <div class="dispatch-title">قائمة التعبئة والتجهيز</div>
                    <div class="dispatch-subtitle">PACKING & DISPATCH LIST</div>
                    <div class="dispatch-stamp-box">
                        <span class="stamp-word">معتمد للتجهيز</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left: Date & Company Info -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المؤسسة :</span>
                        <strong class="dispatch-field-val">{{ $company->company_name ?: 'شركة كيان التجارية' }}</strong>
                    </div>
                    @if(!empty($company->company_phone))
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الهاتف :</span>
                        <span class="dispatch-field-val font-numeric">{{ $company->company_phone }}</span>
                    </div>
                    @endif
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ الإصدار :</span>
                        <span class="dispatch-field-val font-numeric">{{ $docDate }}</span>
                    </div>
                </div>
            </header>

            @if(!empty($notes))
            <div class="doc-panel mb-3 p-2 bg-slate-50 border border-slate-200 text-xs">
                <strong>ملاحظات التعبئة والتسليم:</strong> {{ $notes }}
            </div>
            @endif

            <!-- Items Table -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-15 text-center">كود الصنف (SKU)</th>
                        <th class="col-w-15 text-center">الباركود</th>
                        <th class="col-w-25 text-right">اسم المنتج / الصنف</th>
                        <th class="col-w-8 text-center">الوحدة</th>
                        @if($showQuantity)
                            <th class="col-w-8 text-center font-bold">الكمية</th>
                        @endif
                        @if($showPackages)
                            <th class="col-w-8 text-center">الطرود/الكراتين</th>
                        @endif
                        @if($showPrice)
                            <th class="col-w-10 text-center font-bold">السعر</th>
                            <th class="col-w-10 text-center font-bold">الإجمالي</th>
                        @endif
                        <th class="col-w-15 text-right">ملاحظات التعبئة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $idx => $item)
                        @php
                            $qty = (float) ($item['qty'] ?? 0);
                            $packages = (float) ($item['packages_count'] ?? 0);
                            $price = (float) ($item['price'] ?? 0);
                            $lineTotal = (float) ($item['total'] ?? ($qty * $price));
                        @endphp
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td class="text-center font-numeric font-bold font-mono">{{ $item['sku'] ?: '-' }}</td>
                            <td class="text-center font-numeric font-mono text-xs">{{ $item['barcode'] ?: '-' }}</td>
                            <td>
                                <strong>{{ $item['name'] }}</strong>
                                @if(!empty($item['category_name']) && $item['category_name'] !== 'عام')
                                    <span class="text-xs text-muted block">({{ $item['category_name'] }})</span>
                                @endif
                            </td>
                            <td class="text-center text-xs font-semibold">{{ $item['unit'] ?: 'قطعة' }}</td>
                            @if($showQuantity)
                                <td class="text-center font-numeric font-bold text-sm">{{ number_format($qty, 2) }}</td>
                            @endif
                            @if($showPackages)
                                <td class="text-center font-numeric">{{ $packages > 0 ? number_format($packages, 0) : '-' }}</td>
                            @endif
                            @if($showPrice)
                                <td class="text-center font-numeric font-bold">{{ $price > 0 ? number_format($price, 2) : '-' }}</td>
                                <td class="text-center font-numeric font-bold text-emerald-700">{{ $lineTotal > 0 ? number_format($lineTotal, 2) : '-' }}</td>
                            @endif
                            <td class="text-xs">{{ $item['notes'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                لا توجد أصناف في هذه القائمة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" class="text-left font-bold py-2">
                            الإجمالي الكلي ({{ count($items) }} أصناف):
                        </td>
                        @if($showQuantity)
                            <td class="text-center font-numeric font-bold text-sm">{{ number_format($totalQty, 2) }}</td>
                        @endif
                        @if($showPackages)
                            <td class="text-center font-numeric font-bold text-sm">{{ $totalPackages > 0 ? number_format($totalPackages, 0) : '-' }}</td>
                        @endif
                        @if($showPrice)
                            <td>-</td>
                            <td class="text-center font-numeric font-bold text-sm text-emerald-800">{{ number_format($totalAmount, 2) }} ج.م</td>
                        @endif
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Signatures Section -->
            <footer class="doc-signatures-wrap mt-6">
                <div class="sig-box">
                    <div class="sig-title">مسؤول التجهيز بالمخزن</div>
                    <div class="sig-line"></div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">مندوب الشحن / السائق</div>
                    <div class="sig-line"></div>
                </div>
                <div class="sig-box">
                    <div class="sig-title">توقيع المستلم</div>
                    <div class="sig-line"></div>
                </div>
            </footer>

        @else
            <!-- =================================================================
                 Thermal 80mm Cashier Receipt Layout
                 ================================================================= -->
            <header class="doc-header">
                <div class="doc-brand">
                    <h1 class="doc-company-title">{{ $company->company_name ?: 'شركة كيان التجارية' }}</h1>
                    @if(!empty($company->company_phone))
                        <div class="doc-company-meta font-numeric">هاتف: {{ $company->company_phone }}</div>
                    @endif
                </div>
                <div class="doc-title-block">
                    <h2 class="doc-main-title">قائمة التعبئة والتجهيز (PACKING)</h2>
                    <div class="font-numeric font-bold text-sm font-mono">{{ $docNumber }}</div>
                </div>
            </header>

            <section class="doc-meta-section">
                <div class="doc-panel">
                    <div class="doc-info-list font-numeric">
                        <div class="doc-info-row">
                            <span class="doc-info-label">التاريخ:</span>
                            <span class="doc-info-val">{{ $docDate }}</span>
                        </div>
                        @if($customerName)
                        <div class="doc-info-row">
                            <span class="doc-info-label">العميل:</span>
                            <span class="doc-info-val">{{ $customerName }}</span>
                        </div>
                        @endif
                        @if($warehouseName)
                        <div class="doc-info-row">
                            <span class="doc-info-label">المستودع:</span>
                            <span class="doc-info-val">{{ $warehouseName }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </section>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        @if($showQuantity)
                            <th class="doc-table-num">الكمية</th>
                        @endif
                        @if($showPackages)
                            <th class="doc-table-num">الطرود</th>
                        @endif
                        @if($showPrice)
                            <th class="doc-table-price">السعر</th>
                            <th class="doc-table-price">الإجمالي</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php
                            $qty = (float) ($item['qty'] ?? 0);
                            $packages = (float) ($item['packages_count'] ?? 0);
                            $price = (float) ($item['price'] ?? 0);
                            $lineTotal = (float) ($item['total'] ?? ($qty * $price));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $item['name'] }}</strong>
                                @if(!empty($item['sku']))
                                    <div class="text-[10px] text-muted font-mono font-semibold">{{ $item['sku'] }}</div>
                                @endif
                                @if(!empty($item['notes']))
                                    <div class="text-[9px] text-muted">{{ $item['notes'] }}</div>
                                @endif
                            </td>
                            @if($showQuantity)
                                <td class="doc-table-num font-numeric">
                                    <strong>{{ number_format($qty, 1) }}</strong>
                                    <span class="text-[9px] block">{{ $item['unit'] ?: 'قطعة' }}</span>
                                </td>
                            @endif
                            @if($showPackages)
                                <td class="doc-table-num font-numeric">
                                    {{ $packages > 0 ? number_format($packages, 0) : '-' }}
                                </td>
                            @endif
                            @if($showPrice)
                                <td class="doc-table-price font-numeric">{{ number_format($price, 2) }}</td>
                                <td class="doc-table-price font-numeric font-bold">{{ number_format($lineTotal, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="doc-summary-section mt-2">
                <table class="doc-summary-table font-numeric">
                    <tr>
                        <td class="doc-summary-label">عدد الأصناف:</td>
                        <td class="doc-summary-val font-bold">{{ count($items) }}</td>
                    </tr>
                    @if($showQuantity)
                    <tr>
                        <td class="doc-summary-label">إجمالي الكميات:</td>
                        <td class="doc-summary-val font-bold">{{ number_format($totalQty, 2) }}</td>
                    </tr>
                    @endif
                    @if($showPackages && $totalPackages > 0)
                    <tr>
                        <td class="doc-summary-label">إجمالي الكراتين/الطرود:</td>
                        <td class="doc-summary-val font-bold">{{ number_format($totalPackages, 0) }}</td>
                    </tr>
                    @endif
                    @if($showPrice)
                    <tr class="doc-grand-total">
                        <td class="doc-summary-label">الإجمالي المالي:</td>
                        <td class="doc-summary-val font-bold">{{ number_format($totalAmount, 2) }} ج.م</td>
                    </tr>
                    @endif
                </table>
            </section>

            @if(!empty($notes))
                <div class="doc-notes-box text-xs mt-2">
                    {{ $notes }}
                </div>
            @endif

            <div class="text-center text-[10px] text-muted mt-3 pt-2 border-t border-dashed">
                <div>نظام كيان ERP لإدارة المخازن والمبيعات</div>
                <div class="font-numeric">{{ date('Y-m-d H:i') }}</div>
            </div>
        @endif

        </article>
    </main>

</body>
</html>
