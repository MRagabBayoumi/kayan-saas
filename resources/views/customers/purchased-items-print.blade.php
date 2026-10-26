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
                <span>طباعة تقرير مسحوبات العميل: {{ $customer->name }}</span>
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
                {{ $report['items_count'] }} بند مسجل · الإجمالي: {{ number_format($report['total_spend'], 2) }} ج.م
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
                <!-- Right: Customer Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">تقرير أصناف ومسحوبات العميل</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">كود العميل :</span>
                        <strong class="dispatch-field-val font-numeric">#{{ $customer->id }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">اسم العميل :</span>
                        <strong class="dispatch-field-val">{{ $customer->name }}</strong>
                    </div>
                    @if($customer->company_name)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الشركة/النشاط :</span>
                        <span class="dispatch-field-val">{{ $customer->company_name }}</span>
                    </div>
                    @endif
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">رقم الهاتف :</span>
                        <span class="dispatch-field-val font-numeric">{{ $customer->phone }}</span>
                    </div>
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">تقرير مسحوبات الأصناف</span>
                        <span class="stamp-date font-numeric">{{ now()->format('d/m/Y') }}</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left: Date & Time, Company Info -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المؤسسة :</span>
                        <strong class="dispatch-field-val">{{ $company->company_name }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ التقرير :</span>
                        <span class="dispatch-field-val font-numeric">{{ now()->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الفترة المحددة :</span>
                        <span class="dispatch-field-val font-numeric">
                            {{ $fromDate ?: 'كافة المبيعات' }} إلى {{ $toDate ?: 'اليوم' }}
                        </span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">فئة التسعير المعتمدة :</span>
                        <span class="dispatch-field-val">{{ $customer->getPricingTierLabel() }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Items Table Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-10 text-center">التاريخ</th>
                        <th class="col-w-12 text-center">الفاتورة</th>
                        <th class="col-w-25 text-right">اسم الصنف / الكود</th>
                        <th class="col-w-10 text-center">التصنيف</th>
                        <th class="col-w-8 text-center">الكمية</th>
                        <th class="col-w-8 text-center">الوحدة</th>
                        <th class="col-w-10 text-center">سعر البيع</th>
                        <th class="col-w-12 text-center">الإجمالي (ج.م)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['items'] as $idx => $item)
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td class="text-center font-numeric">{{ $item['invoice_date'] }}</td>
                            <td class="text-center font-numeric font-bold">{{ $item['invoice_number'] }}</td>
                            <td>
                                <strong class="text-main">{{ $item['product_name'] }}</strong>
                                <div class="text-xs text-muted font-numeric">{{ $item['product_sku'] }}</div>
                            </td>
                            <td class="text-center text-xs">{{ $item['category_name'] }}</td>
                            <td class="text-center font-numeric font-bold">{{ number_format($item['quantity'], 2) }}</td>
                            <td class="text-center text-xs">{{ $item['unit_name'] }}</td>
                            <td class="text-center font-numeric">{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="text-center font-numeric font-bold">{{ number_format($item['subtotal'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                لا توجد مسحوبات أصناف مسجلة لهذا العميل خلال الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Totals Section -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box">
                    <div class="dispatch-notes-title">إحصائيات المسحوبات</div>
                    <div class="dispatch-notes-content">
                        إجمالي عدد الأصناف والبنود المسحوبة: {{ $report['items_count'] }} بند
                        · إجمالي الكميات المسحوبة: {{ number_format($report['total_quantity'], 2) }} وحدة.
                    </div>
                </div>

                <div class="dispatch-totals-box">
                    <table class="dispatch-totals-table">
                        <tr>
                            <td class="total-label">إجمالي الكميات المسحوبة :</td>
                            <td class="total-val font-numeric font-bold">{{ number_format($report['total_quantity'], 2) }}</td>
                        </tr>
                        <tr class="dispatch-grand-total">
                            <td class="total-label font-black">إجمالي قيمة المسحوبات :</td>
                            <td class="total-val font-numeric font-black text-primary">
                                {{ number_format($report['total_spend'], 2) }} ج.م
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 4. Signatures Bar -->
            <footer class="dispatch-footer-bar">
                <div class="dispatch-footer-box">
                    اسم المستخدم: {{ auth()->user()->name ?? 'الإدارة' }}
                </div>
                <div class="dispatch-footer-box">
                    عدد البنود: {{ $report['items_count'] }}
                </div>
                <div class="dispatch-footer-box font-numeric">
                    تاريخ ووقت الطباعة: {{ now()->format('Y-m-d H:i') }}
                </div>
            </footer>
        @else
            <!-- Thermal 80mm Layout -->
            <div class="thermal-receipt">
                <div class="thermal-header text-center">
                    <div class="thermal-brand font-bold">{{ $company->company_name }}</div>
                    <div class="thermal-title">مسحوبات العميل من الأصناف</div>
                    <div class="thermal-meta font-numeric">{{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div class="thermal-divider"></div>

                <div class="thermal-info">
                    <div><strong>العميل:</strong> {{ $customer->name }}</div>
                    <div><strong>الفترة:</strong> {{ $fromDate ?: 'الكل' }} - {{ $toDate ?: 'اليوم' }}</div>
                </div>

                <div class="thermal-divider"></div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th>الصنف</th>
                            <th class="text-center">الكمية</th>
                            <th class="text-center">السعر</th>
                            <th class="text-left">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['items'] as $item)
                            <tr>
                                <td>
                                    <div class="font-bold">{{ $item['product_name'] }}</div>
                                    <div class="text-xs text-muted">{{ $item['invoice_number'] }}</div>
                                </td>
                                <td class="text-center font-numeric">{{ number_format($item['quantity'], 1) }}</td>
                                <td class="text-center font-numeric">{{ number_format($item['unit_price'], 1) }}</td>
                                <td class="text-left font-numeric font-bold">{{ number_format($item['subtotal'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider"></div>

                <div class="thermal-totals">
                    <div class="thermal-row">
                        <span>إجمالي الكميات:</span>
                        <strong class="font-numeric">{{ number_format($report['total_quantity'], 2) }}</strong>
                    </div>
                    <div class="thermal-divider-double"></div>
                    <div class="thermal-row thermal-grand">
                        <span>إجمالي المسحوبات:</span>
                        <strong class="font-numeric font-black">{{ number_format($report['total_spend'], 2) }} ج.م</strong>
                    </div>
                </div>

                <div class="thermal-footer text-center mt-3 text-xs">
                    <div>شكراً لتعاملكم معنا</div>
                    <div class="font-numeric">{{ $company->company_phone ?? '' }}</div>
                </div>
            </div>
        @endif
        </article>
    </main>

</body>
</html>
