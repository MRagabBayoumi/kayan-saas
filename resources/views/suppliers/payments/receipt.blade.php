<!DOCTYPE html>
<html lang="ar" dir="rtl" class="navy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    <!-- Google Fonts: Cairo -->
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body data-format="{{ $format }}" data-auto-print="false">

    <!-- Screen-Only Navigation & Print Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>{{ $title }}</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" class="toolbar-format-btn {{ in_array($format, ['thermal_80mm', 'thermal'], true) ? 'active' : '' }}">
                    80 مم حراري
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                المبلغ: {{ number_format((float)$payment->amount, 2) }} ج.م
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة سند الصرف</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <main class="invoice-page-container">
        <article class="invoice-sheet" data-format="{{ $format }}">
            @if(!in_array($format, ['thermal_80mm', 'thermal'], true))
                <!-- A4 / A5 Layout -->
                <div class="doc-header flex justify-between items-start pb-4 border-b-2 border-slate-300">
                    <div>
                        <h1 class="text-2xl font-black text-slate-800">{{ $company->company_name ?? 'كيان للحلول المتكاملة' }}</h1>
                        <p class="text-xs text-slate-500 mt-0.5">استيراد وتوزيع إكسسوارات حريمي</p>
                        @if(!empty($company?->tax_number))
                            <p class="text-[11px] text-slate-500 font-numeric">الرقم الضريبي: {{ $company->tax_number }}</p>
                        @endif
                    </div>
                    <div class="text-left">
                        <div class="inline-block px-4 py-1.5 bg-rose-50 border-2 border-rose-600 rounded-lg">
                            <span class="text-lg font-black text-rose-800">سند صرف مالي</span>
                        </div>
                        <div class="mt-1 font-mono text-xs font-bold text-slate-700">{{ $payment->payment_number }}</div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-4 py-4 border-b border-slate-200 text-xs">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-700 w-28">يُصرف للسيد / الجهة:</span>
                            <span class="font-black text-sm text-slate-900">{{ $payment->supplier?->name }}</span>
                        </div>
                        @if($payment->supplier?->phone)
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-700 w-28">رقم الهاتف:</span>
                                <span class="font-numeric text-slate-800">{{ $payment->supplier->phone }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-700 w-28">طريقة الصرف:</span>
                            <span class="font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-800 border">
                                {{ $payment->payment_method_label }}
                            </span>
                            @if($payment->reference_number)
                                <span class="text-slate-500 font-mono">({{ $payment->reference_number }})</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-700 w-28">الخزينة / المصدر:</span>
                            <span class="text-slate-800 font-medium">{{ $payment->treasury?->name ?? 'درج الكاشير' }}</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-left">
                        <div class="flex items-center justify-end gap-2">
                            <span class="font-bold text-slate-700">تاريخ السند:</span>
                            <span class="font-numeric text-slate-900 font-bold">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : date('Y-m-d') }}</span>
                        </div>
                        @if($payment->purchaseInvoice)
                            <div class="flex items-center justify-end gap-2">
                                <span class="font-bold text-slate-700">فاتورة الشراء رقم:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $payment->purchaseInvoice->invoice_number }}</span>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <span class="font-bold text-slate-700">متبقي الفاتورة بعدها:</span>
                                <span class="font-numeric font-bold text-slate-800">{{ number_format((float)$payment->purchaseInvoice->remaining_amount, 2) }} ج.م</span>
                            </div>
                        @else
                            <div class="flex items-center justify-end gap-2">
                                <span class="text-slate-500 font-medium">سند صرف لحساب المورد العام</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-end gap-2">
                            <span class="font-bold text-slate-700">أمين الخزينة / المسؤول:</span>
                            <span class="text-slate-800">{{ $payment->creator?->name ?? 'النظام' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Amount Box -->
                <div class="my-5 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-500 block">المبلغ المنصرف رقماً:</span>
                            <span class="text-2xl font-black font-numeric text-rose-700">
                                {{ number_format((float)$payment->amount, 2) }}
                                <span class="text-sm font-semibold">جنيه مصري لا غير</span>
                            </span>
                        </div>
                        <div class="text-left">
                            <span class="text-xs text-slate-500 block">الرصيد المتبقي للمورد:</span>
                            <span class="text-lg font-bold font-numeric text-slate-800">
                                {{ number_format((float)$payment->supplier?->current_balance, 2) }} ج.م
                            </span>
                        </div>
                    </div>
                    @if($payment->notes)
                        <div class="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-600">
                            <strong>البيان والسبب:</strong> {{ $payment->notes }}
                        </div>
                    @endif
                </div>

                <!-- Signatures -->
                <div class="grid grid-cols-4 gap-4 mt-8 pt-6 border-t-2 border-slate-200 text-center text-xs">
                    <div>
                        <div class="font-bold text-slate-700 mb-8">المستلم / المورد</div>
                        <div class="border-t border-slate-400 pt-1 text-slate-500">التوقيع والاسم</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-700 mb-8">أمين الخزينة</div>
                        <div class="border-t border-slate-400 pt-1 text-slate-500">التوقيع</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-700 mb-8">المحاسب المسؤول</div>
                        <div class="border-t border-slate-400 pt-1 text-slate-500">التوقيع</div>
                    </div>
                    <div>
                        <div class="font-bold text-slate-700 mb-8">اعتماد الإدارة</div>
                        <div class="border-t border-slate-400 pt-1 text-slate-500">الختم / التوقيع</div>
                    </div>
                </div>

            @else
                <!-- Thermal 80mm Layout -->
                <div class="thermal-ticket text-center text-xs">
                    <div class="font-black text-base">{{ $company->company_name ?? 'كيان' }}</div>
                    <div class="text-[10px] text-slate-500">سند صرف مالي للمورد</div>
                    <div class="border-b border-dashed my-2"></div>

                    <div class="font-mono font-bold text-sm">{{ $payment->payment_number }}</div>
                    <div class="text-[11px] text-slate-600 font-numeric">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : date('Y-m-d') }}</div>

                    <div class="border-b border-dashed my-2"></div>

                    <div class="text-right space-y-1 text-[11px]">
                        <div><strong>المورد:</strong> {{ $payment->supplier?->name }}</div>
                        @if($payment->purchaseInvoice)
                            <div><strong>الفاتورة:</strong> {{ $payment->purchaseInvoice->invoice_number }}</div>
                        @endif
                        <div><strong>طريقة الصرف:</strong> {{ $payment->payment_method_label }}</div>
                        <div><strong>الخزينة:</strong> {{ $payment->treasury?->name ?? 'الدرج' }}</div>
                        @if($payment->notes)
                            <div><strong>ملاحظات:</strong> {{ $payment->notes }}</div>
                        @endif
                    </div>

                    <div class="border-b border-dashed my-2"></div>

                    <div class="py-2">
                        <div class="text-[11px] text-slate-500">المبلغ المنصرف:</div>
                        <div class="text-xl font-black font-numeric text-slate-900">
                            {{ number_format((float)$payment->amount, 2) }} ج.م
                        </div>
                    </div>

                    <div class="border-b border-dashed my-2"></div>

                    <div class="pt-4 text-center text-[10px] text-slate-500">
                        <div>المستلم: _____________________</div>
                        <div class="mt-2">المسؤول: {{ $payment->creator?->name ?? 'النظام' }}</div>
                    </div>
                </div>
            @endif
        </article>
    </main>
</body>
</html>
