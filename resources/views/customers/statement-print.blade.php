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
                <span>طباعة كشف حساب العميل: {{ $customer->name }}</span>
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
                الرصيد: {{ number_format($statement['final_balance'], 2) }} ج.م
                ({{ $statement['balance_status_label'] }})
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
                    <div class="dispatch-badge-original">كشف حساب مالي رسمي</div>
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
                    @if($customer->city)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المدينة/الفرع :</span>
                        <span class="dispatch-field-val">{{ $customer->city }}</span>
                    </div>
                    @endif
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">كشف حساب العميل</span>
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
                        <span class="dispatch-field-label">فئة التسعير :</span>
                        <span class="dispatch-field-val">{{ $customer->getPricingTierLabel() }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Statement Ledger Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-15 text-center">التاريخ</th>
                        <th class="col-w-15 text-center">رقم المستند</th>
                        <th class="col-w-30 text-right">البيان والتفاصيل</th>
                        <th class="col-w-12 text-center">مدين (ج.م)</th>
                        <th class="col-w-12 text-center">دائن (ج.م)</th>
                        <th class="col-w-16 text-center">الرصيد التراكمي (ج.م)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
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
                            <td class="text-center font-numeric font-bold {{ $row['debit'] > 0 ? 'text-positive' : '' }}">
                                {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '0.00' }}
                            </td>
                            <td class="text-center font-numeric font-bold {{ $row['credit'] > 0 ? 'text-negative' : '' }}">
                                {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '0.00' }}
                            </td>
                            <td class="text-center font-numeric font-black">
                                {{ number_format($row['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                لا توجد حركات مالية مسجلة خلال الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Totals and Balance Summary -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box">
                    <div class="dispatch-notes-title">ملاحظات واعتماد الحساب</div>
                    <div class="dispatch-notes-content">
                        كشف حساب إلكتروني معتمد ومستخرج آلياً من نظام كايان لإدارة الموارد.
                        يعتبر الحساب صحيحاً ومطابقاً ما لم يرد اعتراض كتابي رسمي خلال 7 أيام من تاريخه.
                    </div>
                </div>

                <div class="dispatch-totals-box">
                    <table class="dispatch-totals-table">
                        <tr>
                            <td class="total-label">إجمالي المسحوبات (مدين) :</td>
                            <td class="total-val font-numeric font-bold">{{ number_format($statement['total_debits'], 2) }} ج.م</td>
                        </tr>
                        <tr>
                            <td class="total-label">إجمالي المدفوعات (دائن) :</td>
                            <td class="total-val font-numeric font-bold text-success">{{ number_format($statement['total_credits'], 2) }} ج.م</td>
                        </tr>
                        <tr class="dispatch-grand-total">
                            <td class="total-label font-black">صافي الرصيد الحالي :</td>
                            <td class="total-val font-numeric font-black {{ $statement['is_debt'] ? 'text-danger' : 'text-success' }}">
                                {{ number_format($statement['final_balance'], 2) }} ج.م
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
                    حالة الحساب: {{ $statement['balance_status_label'] }}
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
                    <div class="thermal-title">كشف حساب عميل</div>
                    <div class="thermal-meta font-numeric">{{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div class="thermal-divider"></div>

                <div class="thermal-info">
                    <div><strong>العميل:</strong> {{ $customer->name }}</div>
                    <div><strong>الهاتف:</strong> {{ $customer->phone }}</div>
                    <div><strong>الفترة:</strong> {{ $fromDate ?: 'البداية' }} - {{ $toDate ?: 'اليوم' }}</div>
                </div>

                <div class="thermal-divider"></div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th>المستند</th>
                            <th class="text-center">مدين</th>
                            <th class="text-center">دائن</th>
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
                                <td class="text-center font-numeric">{{ $row['debit'] > 0 ? number_format($row['debit'], 1) : '0' }}</td>
                                <td class="text-center font-numeric">{{ $row['credit'] > 0 ? number_format($row['credit'], 1) : '0' }}</td>
                                <td class="text-left font-numeric font-bold">{{ number_format($row['running_balance'], 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-divider"></div>

                <div class="thermal-totals">
                    <div class="thermal-row">
                        <span>إجمالي المدين:</span>
                        <strong class="font-numeric">{{ number_format($statement['total_debits'], 2) }} ج.م</strong>
                    </div>
                    <div class="thermal-row">
                        <span>إجمالي الدائن:</span>
                        <strong class="font-numeric">{{ number_format($statement['total_credits'], 2) }} ج.م</strong>
                    </div>
                    <div class="thermal-divider-double"></div>
                    <div class="thermal-row thermal-grand">
                        <span>الرصيد النهائي:</span>
                        <strong class="font-numeric font-black">{{ number_format($statement['final_balance'], 2) }} ج.م</strong>
                    </div>
                    <div class="text-center text-xs mt-1">({{ $statement['balance_status_label'] }})</div>
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
