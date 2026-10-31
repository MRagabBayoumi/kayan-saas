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
                <span>طباعة تقرير أرصدة ومديونيات العملاء (حسابات العملاء)</span>
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
                {{ $report['total_customers_count'] }} عملاء مسجلين
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
                <!-- Right: Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">تقرير مالي رقابي</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">نوع التقرير :</span>
                        <strong class="dispatch-field-val">كشف مديونيات وحسابات العملاء</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">عدد العملاء :</span>
                        <span class="dispatch-field-val font-numeric">{{ $report['total_customers_count'] }}</span>
                    </div>
                    @if($customerType)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">فئة العملاء :</span>
                        <span class="dispatch-field-val">
                            {{ match($customerType) { 'wholesale' => 'تجار جملة', 'retail' => 'تجزئة', 'distributor' => 'موزعين', default => $customerType } }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">كشف حسابات وأرصدة العملاء</span>
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
                        <span class="dispatch-field-label">الحالة المحددة :</span>
                        <span class="dispatch-field-val">{{ $onlyWithBalance ? 'العملاء المدينين فقط' : 'كافة العملاء' }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Customers Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-25 text-right">اسم العميل / النشاط</th>
                        <th class="col-w-10 text-center">الفئة</th>
                        <th class="col-w-15 text-center">الهاتف والمدينة</th>
                        <th class="col-w-15 text-center">المندوب</th>
                        <th class="col-w-15 text-center">آخر حركة مبيعات</th>
                        <th class="col-w-15 text-center">الرصيد المستحق (عليه كام)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $idx => $row)
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                @if($row['company_name'] && $row['company_name'] !== '—')
                                    <div class="text-xs text-muted">{{ $row['company_name'] }}</div>
                                @endif
                            </td>
                            <td class="text-center text-xs">{{ $row['type_label'] }}</td>
                            <td class="text-center text-xs">
                                <span class="font-numeric">{{ $row['phone'] }}</span>
                                @if($row['city'] && $row['city'] !== '—')
                                    <div class="text-[10px] text-muted">{{ $row['city'] }}</div>
                                @endif
                            </td>
                            <td class="text-center text-xs">{{ $row['sales_rep_name'] }}</td>
                            <td class="text-center text-xs font-numeric">
                                @if($row['last_sale_date'])
                                    <div>{{ $row['last_sale_date'] }}</div>
                                    <div class="text-[10px] text-muted">{{ number_format($row['last_sale_amount'], 2) }} ج.م</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center font-numeric font-black {{ $row['current_balance'] > 0 ? 'text-danger' : ($row['current_balance'] < 0 ? 'text-emerald-600' : 'text-muted') }}">
                                {{ number_format($row['current_balance'], 2) }} ج.م
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                لا توجد حسابات عملاء مطابقة للخيارات المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Totals Breakdown -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box">
                    <div class="dispatch-notes-title">ملاحظات تقرير مديونيات وحسابات العملاء</div>
                    <div class="dispatch-notes-content">
                        كافة الأرصدة والمديونيات مستخرجة آلياً من واقع فواتير المبيعات وسندات القبض المعتمدة بالنظام حتى تاريخ إصدار هذا المستند.
                    </div>
                </div>

                <div class="dispatch-totals-box">
                    <table class="dispatch-totals-table">
                        <tr>
                            <td class="total-label">إجمالي المديونيات المستحقة :</td>
                            <td class="total-val font-numeric font-black text-danger">{{ number_format($report['total_receivables'], 2) }} ج.م</td>
                        </tr>
                        @if($report['total_credit_balances'] > 0)
                        <tr>
                            <td class="total-label">أرصدة دائنة (مدفوعة مقدماً) :</td>
                            <td class="total-val font-numeric font-bold text-emerald-600">{{ number_format($report['total_credit_balances'], 2) }} ج.م</td>
                        </tr>
                        <tr>
                            <td class="total-label">صافي المديونيات العامة :</td>
                            <td class="total-val font-numeric font-black text-primary">{{ number_format($report['net_receivables'], 2) }} ج.م</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- 4. Signatures Bar -->
            <footer class="dispatch-footer-bar">
                <div class="dispatch-footer-box">
                    المستخدم: {{ auth()->user()->name ?? 'الإدارة' }}
                </div>
                <div class="dispatch-footer-box">
                    عدد العملاء المدينين: {{ $report['customers_with_balance_count'] }}
                </div>
                <div class="dispatch-footer-box font-numeric">
                    تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
                </div>
            </footer>
        @else
            <!-- Thermal 80mm Layout -->
            <div class="thermal-receipt">
                <div class="thermal-header text-center">
                    <div class="thermal-brand font-bold">{{ $company->company_name }}</div>
                    <div class="thermal-title">مديونيات وحسابات العملاء</div>
                    <div class="thermal-meta font-numeric">{{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div class="thermal-divider"></div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th class="text-center">الهاتف</th>
                            <th class="text-left">الرصيد المستحق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr>
                                <td>
                                    <div class="font-bold">{{ $row['name'] }}</div>
                                    @if($row['company_name'] && $row['company_name'] !== '—')
                                        <div class="text-[10px] text-muted">{{ $row['company_name'] }}</div>
                                    @endif
                                </td>
                                <td class="text-center font-numeric text-xs">{{ $row['phone'] }}</td>
                                <td class="text-left font-numeric font-black {{ $row['current_balance'] > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ number_format($row['current_balance'], 1) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider"></div>

                <div class="thermal-totals">
                    <div class="thermal-row thermal-grand">
                        <span>إجمالي المستحق:</span>
                        <strong class="font-numeric font-black">{{ number_format($report['total_receivables'], 2) }} ج.م</strong>
                    </div>
                </div>

                <div class="thermal-footer text-center mt-3 text-xs">
                    <div>إدارة الحسابات والمبيعات</div>
                </div>
            </div>
        @endif
        </article>
    </main>

</body>
</html>
