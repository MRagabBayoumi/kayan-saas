<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تقرير إغلاق وردية كاشير - {{ $shift->shift_number }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/invoice-document.css', 'resources/css/print/shift-receipt.css', 'resources/js/pages/invoice-print.js'])
</head>
@php
    $currentFormat = $format ?? 'thermal_80mm';
    $isDocumentFormat = in_array($currentFormat, ['a4', 'a5'], true);
@endphp
<body class="{{ $isDocumentFormat ? 'dispatch-print-body' : 'shift-print-body' }}" data-format="{{ $currentFormat }}" data-auto-print="true">

    <!-- Screen-Only Floating Action Toolbar -->
    <header class="dispatch-toolbar no-print">
        <div class="dispatch-toolbar-info">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>تقرير وردية: {{ $shift->shift_number }}</span>
        </div>

        <div class="dispatch-toolbar-actions">
            <!-- Format Switchers -->
            <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" class="dispatch-format-btn {{ $currentFormat === 'thermal_80mm' ? 'active' : '' }}">
                80 مم حراري
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="dispatch-format-btn {{ $currentFormat === 'a4' ? 'active' : '' }}">
                A4 قياسي
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="dispatch-format-btn {{ $currentFormat === 'a5' ? 'active' : '' }}">
                A5 مصغر
            </a>

            <!-- Direct Python Print -->
            <button type="button" data-action="python-direct-print" data-url="{{ route('shifts.direct-print', $shift) }}" class="dispatch-btn dispatch-btn-primary" title="طباعة فورية مباشرة بطابعة الإيصالات عبر بايثون">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>طباعة فورية (Instant Print)</span>
            </button>

            <!-- Print Now -->
            <button type="button" data-action="print-now" class="dispatch-btn dispatch-btn-secondary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>طباعة المتصفح</span>
            </button>

            <!-- Close Window -->
            <button type="button" data-action="close-window" class="dispatch-btn dispatch-btn-secondary">
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    @if($isDocumentFormat)
        <!-- =========================================================================
             A4 / A5 Enterprise Dispatch Document Layout (Matches Physical Invoice Sample)
             ========================================================================= -->
        <main class="dispatch-print-wrapper">
            <div class="dispatch-sheet-container">

                <!-- 1. Header (3 Columns: Original badge + metadata, Stamp frame, Date & Shift meta) -->
                <header class="dispatch-header">
                    <!-- Right Column -->
                    <div class="dispatch-col-right">
                        <div class="dispatch-badge-original">Original</div>
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">رقم المستند :</span>
                            <span class="dispatch-meta-val font-numeric">{{ $shift->shift_number }}</span>
                        </div>
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">كود الكاشير :</span>
                            <span class="dispatch-meta-val font-numeric">{{ $shift->user?->id ?? '—' }}</span>
                        </div>
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">اسم الكاشير :</span>
                            <span class="dispatch-meta-val font-bold">{{ $shift->user?->name ?? 'كاشير' }}</span>
                        </div>
                        @if($shift->opened_by && $shift->opened_by !== $shift->user_id)
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">فاتح الوردية :</span>
                            <span class="dispatch-meta-val font-bold">{{ $shift->openedBy?->name ?? 'مدير النظام' }}</span>
                        </div>
                        @endif
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">الفرع / المستودع :</span>
                            <span class="dispatch-meta-val">{{ $shift->warehouse?->name ?? 'الرئيسي' }}</span>
                        </div>
                    </div>

                    <!-- Center Column: Stamp Frame with dashed border and stars -->
                    <div class="dispatch-col-center">
                        <div class="dispatch-stamp-frame">
                            <div class="dispatch-stamp-stars">★ ★ ★</div>
                            <div class="dispatch-stamp-title">تقرير إغلاق وردية</div>
                            <div class="dispatch-stamp-stars">★ ★ ★</div>
                            <div class="dispatch-stamp-date font-numeric">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d') : now()->format('Y-m-d') }}</div>
                        </div>
                    </div>

                    <!-- Left Column -->
                    <div class="dispatch-col-left">
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">التاريخ والوقت :</span>
                            <span class="dispatch-meta-val font-numeric">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">وقت الافتتاح :</span>
                            <span class="dispatch-meta-val font-numeric">{{ $shift->opened_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="dispatch-meta-row">
                            <span class="dispatch-meta-label">معتمد الإغلاق :</span>
                            <span class="dispatch-meta-val">{{ $shift->closedBy?->name ?? 'قيد الاعتماد' }}</span>
                        </div>
                    </div>
                </header>

                <!-- 2. Financial Summary Table Grid (1px Solid Black Borders) -->
                <div class="shift-a4-section">
                    <div class="shift-a4-table-header">أولاً: كشف التسوية وحركة الخزينة بالوردية</div>
                    <table class="dispatch-table">
                        <thead>
                            <tr>
                                <th class="col-w-5 text-center">م</th>
                                <th class="col-w-45 text-right">بيان الحركة المالية بالدرج</th>
                                <th class="col-w-25 text-center">المبلغ الدفتري (ج.م)</th>
                                <th class="col-w-25 text-center">ملاحظات التدقيق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center font-numeric">1</td>
                                <td>(+) رصيد بداية العهدة (الافتتاحي)</td>
                                <td class="text-center font-numeric font-bold">{{ number_format((float)$shift->opening_balance, 2) }}</td>
                                <td class="text-center">عهدة نقدية بالدرج</td>
                            </tr>
                            <tr>
                                <td class="text-center font-numeric">2</td>
                                <td>(+) مبيعات نقدية ({{ $shift->salesInvoices->count() }} فاتورة بيع)</td>
                                <td class="text-center font-numeric font-bold text-positive">+{{ number_format((float)$shift->cash_sales_amount, 2) }}</td>
                                <td class="text-center">إجمالي المتحصلات النقدية</td>
                            </tr>
                            <tr>
                                <td class="text-center font-numeric">3</td>
                                <td>(+) إيداعات إضافية بالدرج</td>
                                <td class="text-center font-numeric font-bold text-positive">+{{ number_format((float)$shift->cash_additions, 2) }}</td>
                                <td class="text-center">وارد نقدي إضافي</td>
                            </tr>
                            <tr>
                                <td class="text-center font-numeric">4</td>
                                <td>(−) مسحوبات ومصروفات نقدية</td>
                                <td class="text-center font-numeric font-bold text-negative">−{{ number_format((float)$shift->cash_expenses, 2) }}</td>
                                <td class="text-center">مصروفات موثقة بسندات</td>
                            </tr>
                            <tr>
                                <td class="text-center font-numeric">5</td>
                                <td>(−) مرتجعات نقدية للعملاء</td>
                                <td class="text-center font-numeric font-bold text-negative">−{{ number_format((float)$shift->cash_returns_amount, 2) }}</td>
                                <td class="text-center">مبالغ مستردة نقدياً</td>
                            </tr>
                            <tr class="shift-row-expected">
                                <td class="text-center font-numeric">6</td>
                                <td>(=) الرصيد الدفتري المتوقع بالدرج</td>
                                <td class="text-center font-numeric font-bold">{{ number_format((float)$shift->expected_closing_balance, 2) }}</td>
                                <td class="text-center">إجمالي المتوقع دفترياً</td>
                            </tr>
                            @if($shift->actual_closing_balance !== null)
                                <tr class="shift-row-actual">
                                    <td class="text-center font-numeric">7</td>
                                    <td>الجرد الفعلي للنقدية المستلمة</td>
                                    <td class="text-center font-numeric font-bold">{{ number_format((float)$shift->actual_closing_balance, 2) }}</td>
                                    <td class="text-center">المبلغ المستلم فعلياً</td>
                                </tr>
                                <tr class="shift-row-bold">
                                    <td class="text-center font-numeric">8</td>
                                    <td>فارق الدرج النهائي</td>
                                    <td class="text-center font-numeric font-bold {{ $shift->difference < 0 ? 'text-negative' : ($shift->difference > 0 ? 'text-positive' : '') }}">
                                        {{ $shift->difference > 0 ? '+' : '' }}{{ number_format((float)$shift->difference, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ $shift->difference == 0 ? 'مطابق تماماً دون فروقات' : ($shift->difference > 0 ? 'زيادة بالدرج' : 'عجز نقدي بالدرج') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- 3. Sold Items Breakdown Table Grid (1px Solid Black Borders) -->
                @if(isset($soldItems) && count($soldItems) > 0)
                    <div class="shift-a4-section-lg">
                        <div class="shift-a4-table-header">ثانياً: تفاصيل الأصناف المباعة بالوردية ({{ count($soldItems) }} صنف)</div>
                        <table class="dispatch-table">
                            <thead>
                                <tr>
                                    <th class="col-w-5 text-center">م</th>
                                    <th class="col-w-15 text-center">كود الصنف</th>
                                    <th class="col-w-40 text-right">اسم الصنف والبيان</th>
                                    <th class="col-w-12 text-center">الكمية</th>
                                    <th class="col-w-13 text-center">وحده القياس</th>
                                    <th class="col-w-15 text-center">الإجمالي (ج.م)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($soldItems as $index => $sItem)
                                    <tr>
                                        <td class="text-center font-numeric">{{ $index + 1 }}</td>
                                        <td class="text-center font-numeric">{{ $sItem->product_sku ?: '—' }}</td>
                                        <td class="text-right">{{ $sItem->product_name }}</td>
                                        <td class="text-center font-numeric font-bold">{{ (float)$sItem->total_qty }}</td>
                                        <td class="text-center">{{ $sItem->unit_name ?: 'قطعة' }}</td>
                                        <td class="text-center font-numeric font-bold">{{ number_format((float)$sItem->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- 4. Notes & Signatures Section -->
                <div class="dispatch-bottom-section shift-a4-section-lg">
                    <div class="dispatch-notes-box">
                        <div class="dispatch-notes-title">ملاحظات واعتماد الوردية</div>
                        <div class="dispatch-notes-content">
                            {{ $shift->closing_notes ?: 'تم جرد ومطابقة الخزينة النقدية بحضور الكاشير والمشرف المسؤول وتسليم العهدة للإدارة.' }}
                        </div>
                    </div>

                    <div class="dispatch-totals-box">
                        <table class="dispatch-totals-table">
                            <tr>
                                <td class="total-label font-bold">إجمالي المبيعات النقدية :</td>
                                <td class="total-val font-numeric font-bold">{{ number_format((float)$shift->cash_sales_amount, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <td class="total-label font-bold">الرصيد الدفتري المتوقع :</td>
                                <td class="total-val font-numeric font-bold">{{ number_format((float)$shift->expected_closing_balance, 2) }} ج.م</td>
                            </tr>
                            @if($shift->actual_closing_balance !== null)
                                <tr class="total-row-net">
                                    <td class="total-label font-bold">النقدية الفعلية المستلمة :</td>
                                    <td class="total-val font-numeric font-bold">{{ number_format((float)$shift->actual_closing_balance, 2) }} ج.م</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- 5. Standard Bottom Bar (3 Side-by-Side Bordered Boxes Matching Photo) -->
                <footer class="dispatch-footer-bar">
                    <div class="dispatch-footer-box">
                        اسم المستخدم : {{ auth()->user()->name ?? $shift->user?->name ?? 'المسؤول' }}
                    </div>
                    <div class="dispatch-footer-box">
                        الصفحات من 1 إلي 1
                    </div>
                    <div class="dispatch-footer-box font-numeric">
                        تاريخ ووقت الطباعة : {{ now()->format('Y-m-d H:i') }}
                    </div>
                </footer>

            </div>
        </main>
    @else
        <!-- =========================================================================
             80mm Thermal Receipt Layout (Cashier POS)
             ========================================================================= -->
        <div class="shift-print-wrapper">
            <div class="shift-receipt-container">
                <!-- Header & Branding -->
                <div class="shift-receipt-header">
                    @if(!empty($company?->logo_url))
                        <img src="{{ $company->logo_url }}" alt="Logo" class="shift-receipt-logo">
                    @endif
                    <h1 class="shift-receipt-title">{{ $company?->company_name ?: config('app.name', 'شركة كيان التجارية') }}</h1>
                    @if(!empty($company?->company_phone) || !empty($company?->company_address))
                        <div class="shift-receipt-meta">
                            {{ $company?->company_phone ? 'هاتف: ' . $company->company_phone : '' }}
                            {{ $company?->company_address ? ' · ' . $company->company_address : '' }}
                        </div>
                    @endif
                    <div class="shift-receipt-subtitle mt-1">تقرير إغلاق وجرد وردية كاشير</div>
                    <div class="shift-receipt-meta bold font-numeric">{{ $shift->shift_number }}</div>
                </div>

                <!-- Shift Details -->
                <div class="shift-receipt-section">
                    <div class="shift-receipt-row">
                        <span>الكاشير:</span>
                        <strong>{{ $shift->user?->name }}</strong>
                    </div>

                    <div class="shift-receipt-row">
                        <span>الفرع / المستودع:</span>
                        <strong>{{ $shift->warehouse?->name }}</strong>
                    </div>

                    <div class="shift-receipt-row">
                        <span>وقت الافتتاح:</span>
                        <span class="font-numeric">{{ $shift->opened_at->format('Y-m-d H:i') }}</span>
                    </div>

                    <div class="shift-receipt-row">
                        <span>وقت الإغلاق:</span>
                        <span class="font-numeric">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : 'نشطة' }}</span>
                    </div>

                    @if($shift->closedBy)
                        <div class="shift-receipt-row">
                            <span>معتمد الإغلاق:</span>
                            <span>{{ $shift->closedBy->name }}</span>
                        </div>
                    @endif
                </div>

                <!-- Financial Breakdown -->
                <div class="shift-receipt-section">
                    <div class="shift-receipt-row bold">
                        <span>(+) رصيد بداية العهدة:</span>
                        <span class="font-numeric">{{ number_format((float)$shift->opening_balance, 2) }} ج.م</span>
                    </div>

                    <div class="shift-receipt-row">
                        <span>(+) مبيعات نقدية ({{ $shift->salesInvoices->count() }} فاتورة):</span>
                        <span class="font-numeric">+{{ number_format((float)$shift->cash_sales_amount, 2) }} ج.م</span>
                    </div>

                    <div class="shift-receipt-row">
                        <span>(+) إيداعات إضافية بالدرج:</span>
                        <span class="font-numeric">+{{ number_format((float)$shift->cash_additions, 2) }} ج.م</span>
                    </div>

                    <div class="shift-receipt-row">
                        <span>(−) مسحوبات ومصروفات:</span>
                        <span class="font-numeric">−{{ number_format((float)$shift->cash_expenses, 2) }} ج.م</span>
                    </div>

                    <div class="shift-receipt-row">
                        <span>(−) مرتجعات نقدية:</span>
                        <span class="font-numeric">−{{ number_format((float)$shift->cash_returns_amount, 2) }} ج.م</span>
                    </div>

                    <div class="shift-receipt-row bold divider">
                        <span>(=) الرصيد الدفتري المتوقع:</span>
                        <span class="font-numeric">{{ number_format((float)$shift->expected_closing_balance, 2) }} ج.م</span>
                    </div>
                </div>

                <!-- Sold Items Breakdown Report -->
                @if(isset($soldItems) && count($soldItems) > 0)
                    <div class="shift-receipt-section">
                        <div class="shift-section-title">الأصناف المباعة بالوردية ({{ count($soldItems) }} صنف):</div>
                        <table class="shift-sold-items-table">
                            <thead>
                                <tr>
                                    <th>اسم الصنف</th>
                                    <th class="text-center">الكمية</th>
                                    <th class="text-left">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($soldItems as $sItem)
                                    <tr>
                                        <td>
                                            <div class="item-name">{{ $sItem->product_name }}</div>
                                            @if($sItem->product_sku)
                                                <div class="item-sku font-numeric">{{ $sItem->product_sku }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center font-numeric">
                                            {{ (float)$sItem->total_qty }} {{ $sItem->unit_name ?: 'قطعة' }}
                                        </td>
                                        <td class="text-left font-numeric font-bold">
                                            {{ number_format((float)$sItem->total_amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Actual Count & Discrepancy -->
                @if($shift->actual_closing_balance !== null)
                    <div class="shift-receipt-section">
                        <div class="shift-receipt-row grand">
                            <span>الجرد الفعلي للنقدية:</span>
                            <span class="font-numeric">{{ number_format((float)$shift->actual_closing_balance, 2) }} ج.م</span>
                        </div>

                        <div class="shift-receipt-row bold">
                            <span>فارق الدرج:</span>
                            <span class="font-numeric">
                                {{ $shift->difference > 0 ? '+' : '' }}{{ number_format((float)$shift->difference, 2) }} ج.م
                                ({{ $shift->difference == 0 ? 'مطابق تماماً' : ($shift->difference > 0 ? 'زيادة' : 'عجز') }})
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($shift->closing_notes)
                    <div class="shift-receipt-section">
                        <div class="shift-receipt-meta">
                            <strong>ملاحظات:</strong> {{ $shift->closing_notes }}
                        </div>
                    </div>
                @endif

                <!-- Footer Signatures -->
                <div class="shift-receipt-footer">
                    <div>طُبع بتاريخ: {{ now()->format('Y-m-d H:i:s') }}</div>
                    <div class="shift-signatures">
                        <span>توقيع الكاشير: ...............</span>
                        <span>توقيع المشرف: ...............</span>
                    </div>
                    <div class="barcode-box">*{{ $shift->shift_number }}*</div>
                </div>
            </div>
        </div>
    @endif

</body>
</html>
