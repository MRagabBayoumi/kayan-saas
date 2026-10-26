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
                <span>طباعة كشف حساب المورد: {{ $supplier->name }}</span>
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
                الرصيد المستحق: {{ number_format($statement['final_balance'], 2) }} {{ $statement['currency'] }}
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
                <!-- Right: Supplier Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">كشف حساب مورد / مصنع</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">كود المورد :</span>
                        <strong class="dispatch-field-val font-numeric">#{{ $supplier->id }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">اسم المورد :</span>
                        <strong class="dispatch-field-val">{{ $supplier->name }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">نوع المورد :</span>
                        <span class="dispatch-field-val">{{ $supplier->getTypeLabel() }}</span>
                    </div>
                    @if($supplier->phone)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الهاتف :</span>
                        <span class="dispatch-field-val font-numeric">{{ $supplier->phone }}</span>
                    </div>
                    @endif
                    @if($supplier->country)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الدولة / المدينة :</span>
                        <span class="dispatch-field-val">{{ $supplier->country }} {{ $supplier->city ? '- ' . $supplier->city : '' }}</span>
                    </div>
                    @endif
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">كشف حساب المورد</span>
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
                        <span class="dispatch-field-label">تاريخ الكشف :</span>
                        <span class="dispatch-field-val font-numeric">{{ now()->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">الفترة المالية :</span>
                        <span class="dispatch-field-val font-numeric">
                            {{ $fromDate ?: 'بداية التعامل' }} إلى {{ $toDate ?: 'اليوم' }}
                        </span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">عملة الحساب :</span>
                        <span class="dispatch-field-val font-bold">{{ $statement['currency'] }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Statement Ledger Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-15 text-center">التاريخ</th>
                        <th class="col-w-15 text-center">رقم الفاتورة</th>
                        <th class="col-w-30 text-right">البيان والتفاصيل</th>
                        <th class="col-w-12 text-center">دائن / مشتريات ({{ $statement['currency'] }})</th>
                        <th class="col-w-12 text-center">مدين / مدفوع ({{ $statement['currency'] }})</th>
                        <th class="col-w-16 text-center">الرصيد المستحق ({{ $statement['currency'] }})</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Pre-period Row -->
                    <tr>
                        <td class="text-center font-numeric">—</td>
                        <td class="text-center font-numeric">{{ $fromDate ?: '—' }}</td>
                        <td class="text-center font-numeric">OPENING</td>
                        <td>رصيد ما قبل الفترة / الافتتاحي</td>
                        <td class="text-center font-numeric">{{ $statement['pre_period_balance'] > 0 ? number_format($statement['pre_period_balance'], 2) : '0.00' }}</td>
                        <td class="text-center font-numeric">{{ $statement['pre_period_balance'] < 0 ? number_format(abs($statement['pre_period_balance']), 2) : '0.00' }}</td>
                        <td class="text-center font-numeric font-bold">{{ number_format($statement['pre_period_balance'], 2) }}</td>
                    </tr>

                    @forelse($statement['rows'] as $idx => $row)
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td class="text-center font-numeric">{{ $row['date'] }}</td>
                            <td class="text-center font-numeric font-bold">{{ $row['doc_number'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-center font-numeric font-bold {{ $row['credit'] > 0 ? 'text-positive' : '' }}">
                                {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '0.00' }}
                            </td>
                            <td class="text-center font-numeric font-bold {{ $row['debit'] > 0 ? 'text-negative' : '' }}">
                                {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '0.00' }}
                            </td>
                            <td class="text-center font-numeric font-black">
                                {{ number_format($row['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                لا توجد حركات توريد مسجلة لهذا المورد خلال الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Totals and Balance Summary -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box">
                    <div class="dispatch-notes-title">ملاحظات تسوية حساب المورد</div>
                    <div class="dispatch-notes-content">
                        كشف حساب إلكتروني رسمي للموردين والمصانع صادر من نظام كايان لإدارة الموارد.
                    </div>
                </div>

                <div class="dispatch-totals-box">
                    <table class="dispatch-totals-table">
                        <tr>
                            <td class="total-label">إجمالي التوريدات (دائن) :</td>
                            <td class="total-val font-numeric font-bold">{{ number_format($statement['total_credits'], 2) }} {{ $statement['currency'] }}</td>
                        </tr>
                        <tr>
                            <td class="total-label">إجمالي المسدد (مدين) :</td>
                            <td class="total-val font-numeric font-bold text-success">{{ number_format($statement['total_debits'], 2) }} {{ $statement['currency'] }}</td>
                        </tr>
                        <tr class="dispatch-grand-total">
                            <td class="total-label font-black">صافي المستحق للمورد :</td>
                            <td class="total-val font-numeric font-black {{ $statement['final_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($statement['final_balance'], 2) }} {{ $statement['currency'] }}
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
                <div class="dispatch-footer-box font-numeric">
                    العملة: {{ $statement['currency'] }}
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
                    <div class="thermal-title">كشف حساب مورد</div>
                    <div class="thermal-meta font-numeric">{{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div class="thermal-divider"></div>

                <div class="thermal-info">
                    <div><strong>المورد:</strong> {{ $supplier->name }}</div>
                    <div><strong>العملة:</strong> {{ $statement['currency'] }}</div>
                    <div><strong>الفترة:</strong> {{ $fromDate ?: 'البداية' }} - {{ $toDate ?: 'اليوم' }}</div>
                </div>

                <div class="thermal-divider"></div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th>المستند</th>
                            <th class="text-center">دائن</th>
                            <th class="text-center">مدين</th>
                            <th class="text-left">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>رصيد سابق</td>
                            <td class="text-center font-numeric">{{ $statement['pre_period_balance'] > 0 ? number_format($statement['pre_period_balance'], 1) : '0' }}</td>
                            <td class="text-center font-numeric">{{ $statement['pre_period_balance'] < 0 ? number_format(abs($statement['pre_period_balance']), 1) : '0' }}</td>
                            <td class="text-left font-numeric font-bold">{{ number_format($statement['pre_period_balance'], 1) }}</td>
                        </tr>
                        @foreach($statement['rows'] as $row)
                            <tr>
                                <td>
                                    <div class="font-bold">{{ $row['doc_number'] }}</div>
                                    <div class="text-xs">{{ $row['date'] }}</div>
                                </td>
                                <td class="text-center font-numeric">{{ $row['credit'] > 0 ? number_format($row['credit'], 1) : '0' }}</td>
                                <td class="text-center font-numeric">{{ $row['debit'] > 0 ? number_format($row['debit'], 1) : '0' }}</td>
                                <td class="text-left font-numeric font-bold">{{ number_format($row['running_balance'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider"></div>

                <div class="thermal-totals">
                    <div class="thermal-row">
                        <span>إجمالي التوريد:</span>
                        <strong class="font-numeric">{{ number_format($statement['total_credits'], 2) }}</strong>
                    </div>
                    <div class="thermal-row">
                        <span>إجمالي المسدد:</span>
                        <strong class="font-numeric">{{ number_format($statement['total_debits'], 2) }}</strong>
                    </div>
                    <div class="thermal-divider-double"></div>
                    <div class="thermal-row thermal-grand">
                        <span>الرصيد المستحق:</span>
                        <strong class="font-numeric font-black">{{ number_format($statement['final_balance'], 2) }} {{ $statement['currency'] }}</strong>
                    </div>
                </div>

                <div class="thermal-footer text-center mt-3 text-xs">
                    <div>إدارة المشتريات والتوريد</div>
                </div>
            </div>
        @endif
        </article>
    </main>

</body>
</html>
