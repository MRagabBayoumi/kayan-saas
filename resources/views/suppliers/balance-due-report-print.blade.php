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
                <span>طباعة تقرير أرصدة الموردين المستحقة (Balance Due)</span>
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
                {{ $report['total_suppliers_count'] }} موردين مسجلين
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
                        <strong class="dispatch-field-val">أرصدة ومستحقات الموردين</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">عدد الموردين :</span>
                        <span class="dispatch-field-val font-numeric">{{ $report['total_suppliers_count'] }}</span>
                    </div>
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">تقرير أرصدة الموردين المستحقة</span>
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
                        <span class="dispatch-field-label">العملة المحددة :</span>
                        <span class="dispatch-field-val">{{ $currency ?: 'كافة العملات' }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Suppliers Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-25 text-right">اسم المورد / المصنع</th>
                        <th class="col-w-15 text-center">النوع والتصنيف</th>
                        <th class="col-w-15 text-center">بيانات الاتصال</th>
                        <th class="col-w-15 text-center">آخر توريد</th>
                        <th class="col-w-10 text-center">العملة</th>
                        <th class="col-w-15 text-center">الرصيد المستحق</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $idx => $row)
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                @if($row['contact_person'] && $row['contact_person'] !== '—')
                                    <div class="text-xs text-muted">المسؤول: {{ $row['contact_person'] }}</div>
                                @endif
                            </td>
                            <td class="text-center text-xs">{{ $row['type_label'] }}</td>
                            <td class="text-center font-numeric text-xs">{{ $row['phone'] }}</td>
                            <td class="text-center text-xs font-numeric">
                                @if($row['last_purchase_date'])
                                    <div>{{ $row['last_purchase_date'] }}</div>
                                    <div class="text-[10px] text-muted">{{ $row['last_purchase_number'] }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center font-bold font-numeric">{{ $row['currency'] }}</td>
                            <td class="text-center font-numeric font-black text-danger">
                                {{ number_format($row['current_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                لا توجد أرصدة موردين مستحقة مطابقة للخيارات المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Totals Breakdown by Currency -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box">
                    <div class="dispatch-notes-title">ملاحظات تقرير المديونيات</div>
                    <div class="dispatch-notes-content">
                        إجمالي الأرصدة المستحقة للموردين والمصانع الخارجية والمحلية محسوبة وفق قيود التوريد المستندية المعتمدة بالنظام.
                    </div>
                </div>

                <div class="dispatch-totals-box">
                    <table class="dispatch-totals-table">
                        @foreach($report['totals_by_currency'] as $curr => $total)
                            <tr>
                                <td class="total-label">إجمالي المستحق ({{ $curr }}) :</td>
                                <td class="total-val font-numeric font-black text-danger">{{ number_format($total, 2) }} {{ $curr }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>

            <!-- 4. Signatures Bar -->
            <footer class="dispatch-footer-bar">
                <div class="dispatch-footer-box">
                    اسم المستخدم: {{ auth()->user()->name ?? 'الإدارة' }}
                </div>
                <div class="dispatch-footer-box">
                    إجمالي الموردين: {{ $report['total_suppliers_count'] }}
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
                    <div class="thermal-title">مستحقات وأرصدة الموردين</div>
                    <div class="thermal-meta font-numeric">{{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div class="thermal-divider"></div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th>المورد</th>
                            <th class="text-center">العملة</th>
                            <th class="text-left">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr>
                                <td>
                                    <div class="font-bold">{{ $row['name'] }}</div>
                                    <div class="text-xs text-muted">{{ $row['phone'] }}</div>
                                </td>
                                <td class="text-center font-numeric font-bold">{{ $row['currency'] }}</td>
                                <td class="text-left font-numeric font-black text-danger">{{ number_format($row['current_balance'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider"></div>

                <div class="thermal-totals">
                    @foreach($report['totals_by_currency'] as $curr => $total)
                        <div class="thermal-row thermal-grand">
                            <span>إجمالي ({{ $curr }}):</span>
                            <strong class="font-numeric font-black">{{ number_format($total, 2) }}</strong>
                        </div>
                    @endforeach
                </div>

                <div class="thermal-footer text-center mt-3 text-xs">
                    <div>إدارة المشتريات والحسابات</div>
                </div>
            </div>
        @endif
        </article>
    </main>

</body>
</html>
