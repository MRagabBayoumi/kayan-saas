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
                <span>طباعة تقرير مخزون: {{ $warehouse->name }}</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" class="toolbar-format-btn {{ in_array($format, ['thermal_80mm', 'thermal'], true) ? 'active' : '' }}">
                    80 مم حراري
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                الأصناف: {{ number_format($breakdown['items_count']) }} | إجمالي الكمية: {{ number_format($breakdown['total_quantity'], 1) }}
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
        @if(!in_array($format, ['thermal_80mm', 'thermal'], true))
            <!-- 1. Enterprise Dispatch Header (A4 / A5 Layout) -->
            <header class="dispatch-header">
                <!-- Right: Warehouse Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">تقرير أرصدة وجرد المستودع</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">كود المستودع :</span>
                        <strong class="dispatch-field-val font-numeric">#{{ $warehouse->code }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">اسم المستودع :</span>
                        <strong class="dispatch-field-val">{{ $warehouse->name }}</strong>
                    </div>
                    @if($warehouse->location)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الموقع الجغرافي :</span>
                        <span class="dispatch-field-val">{{ $warehouse->location }}</span>
                    </div>
                    @endif
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ التقرير :</span>
                        <span class="dispatch-field-val font-numeric">{{ date('Y-m-d H:i') }}</span>
                    </div>
                </div>

                <!-- Center: Company Brand / Name -->
                <div class="dispatch-col-center">
                    @if(!empty($company->logo_path))
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->company_name ?? 'الشركة' }}" class="dispatch-logo" />
                    @else
                        <div class="dispatch-company-fallback">{{ $company->company_name ?? config('app.name', 'كيان للحلول البرمجية') }}</div>
                    @endif
                    <div class="dispatch-company-sub">{{ $company->activity_type ?? 'نظام إدارة الموارد وتتبع سلاسل الإمداد' }}</div>
                </div>

                <!-- Left: Quick Warehouse Summary -->
                <div class="dispatch-col-left">
                    <div class="meta-box">
                        <div class="meta-row">
                            <span class="meta-label">إجمالي الأصناف:</span>
                            <span class="meta-value font-numeric">{{ number_format($breakdown['items_count']) }} صنف</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">إجمالي الكمية:</span>
                            <span class="meta-value font-numeric">{{ number_format($breakdown['total_quantity'], 2) }}</span>
                        </div>
                        <div class="meta-row highlight">
                            <span class="meta-label">إجمالي التقييم:</span>
                            <span class="meta-value font-numeric">{{ number_format($breakdown['total_valuation'], 2) }} ج.م</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- 2. Items Table -->
            <section class="invoice-body">
                <table class="dispatch-table">
                    <thead>
                        <tr>
                            <th class="col-num">#</th>
                            <th class="col-code">الكود</th>
                            <th class="col-desc">اسم المنتج والتصنيف</th>
                            <th class="col-unit">الوحدة</th>
                            <th class="col-qty">الرصيد الأساسي</th>
                            <th class="col-pkg">تجزئة الطرود</th>
                            <th class="col-price">التكلفة</th>
                            <th class="col-total">التقييم الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($breakdown['items'] as $index => $item)
                            <tr>
                                <td class="col-num font-numeric">{{ $index + 1 }}</td>
                                <td class="col-code font-numeric font-bold">{{ $item['sku'] }}</td>
                                <td class="col-desc">
                                    <div class="item-title">{{ $item['name'] }}</div>
                                    <div class="item-category">{{ $item['category_name'] }}</div>
                                </td>
                                <td class="col-unit">{{ $item['base_unit'] }}</td>
                                <td class="col-qty font-numeric font-bold">{{ number_format($item['book_balance'], 2) }}</td>
                                <td class="col-pkg">{{ $item['package_breakdown'] }}</td>
                                <td class="col-price font-numeric">{{ number_format($item['cost_price'], 2) }}</td>
                                <td class="col-total font-numeric font-bold">{{ number_format($item['valuation'], 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-muted">لا توجد أرصدة أصناف مسجلة لهذا المستودع.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-summary-row font-bold">
                            <td colspan="4" class="text-right">الإجمالي الكلي لتقييم المخزون:</td>
                            <td class="font-numeric text-center">{{ number_format($breakdown['total_quantity'], 2) }}</td>
                            <td colspan="2"></td>
                            <td class="font-numeric font-bold text-left">{{ number_format($breakdown['total_valuation'], 2) }} ج.م</td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <!-- 3. Signatures & Footer -->
            <footer class="dispatch-footer">
                <div class="signatures-grid">
                    <div class="sig-block">
                        <span class="sig-title">أمين المستودع</span>
                        <span class="sig-dots">......................................</span>
                    </div>
                    <div class="sig-block">
                        <span class="sig-title">مسؤول مراقبة المخزون</span>
                        <span class="sig-dots">......................................</span>
                    </div>
                    <div class="sig-block">
                        <span class="sig-title">مدير الإدارة المالية</span>
                        <span class="sig-dots">......................................</span>
                    </div>
                </div>
            </footer>
        @else
            <!-- Thermal 80mm Layout -->
            <div class="thermal-receipt">
                <div class="thermal-header text-center">
                    <div class="thermal-title font-bold">{{ $company->company_name ?? 'كيان للحلول البرمجية' }}</div>
                    <div class="thermal-sub">تقرير مخزون مستودع</div>
                    <div class="thermal-meta font-numeric text-xs mt-1">
                        {{ $warehouse->name }} ({{ $warehouse->code }})
                    </div>
                    <div class="thermal-meta font-numeric text-xs">
                        {{ date('Y-m-d H:i') }}
                    </div>
                </div>

                <div class="thermal-divider dashed"></div>

                <div class="thermal-summary-box text-xs">
                    <div class="flex justify-between">
                        <span>إجمالي الأصناف:</span>
                        <strong class="font-numeric">{{ number_format($breakdown['items_count']) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>إجمالي الكمية:</span>
                        <strong class="font-numeric">{{ number_format($breakdown['total_quantity'], 2) }}</strong>
                    </div>
                    <div class="flex justify-between mt-1 pt-1 border-t border-dashed">
                        <span>إجمالي التقييم:</span>
                        <strong class="font-numeric">{{ number_format($breakdown['total_valuation'], 2) }} ج.م</strong>
                    </div>
                </div>

                <div class="thermal-divider dashed"></div>

                <table class="thermal-table text-xs">
                    <thead>
                        <tr>
                            <th class="text-right">الصنف</th>
                            <th class="text-center">الرصيد</th>
                            <th class="text-left">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown['items'] as $item)
                            <tr>
                                <td class="text-right">
                                    <div class="font-bold">{{ $item['name'] }}</div>
                                    <div class="text-[10px] text-muted">{{ $item['sku'] }} | {{ $item['package_breakdown'] }}</div>
                                </td>
                                <td class="text-center font-numeric font-bold">
                                    {{ number_format($item['book_balance'], 1) }}
                                </td>
                                <td class="text-left font-numeric">
                                    {{ number_format($item['valuation'], 1) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider dashed"></div>

                <div class="thermal-footer text-center text-xs mt-2">
                    <div>تم استخراج التقرير آلياً عبر نظام كايان ERP</div>
                </div>
            </div>
        @endif
        </article>
    </main>
</body>
</html>
