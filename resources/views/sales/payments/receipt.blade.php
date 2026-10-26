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
                <span>طباعة سند القبض</span>
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
                        <p class="text-xs text-slate-500 mt-1">{{ $company->tax_card ? 'ب.ض: ' . $company->tax_card : '' }} {{ $company->commercial_register ? '| س.ت: ' . $company->commercial_register : '' }}</p>
                        <p class="text-xs text-slate-600 font-numeric">{{ $company->phone_primary ?? '' }}</p>
                        <p class="text-xs text-slate-500">{{ $company->address ?? '' }}</p>
                    </div>
                    <div class="text-left">
                        <div class="inline-block bg-slate-900 text-white px-4 py-2 rounded font-black text-lg tracking-wider">
                            سند قبض مالي
                        </div>
                        <div class="text-xs text-slate-600 mt-2 font-numeric">
                            <div>رقم السند: <strong class="text-slate-900 font-bold">{{ $payment->payment_number }}</strong></div>
                            <div>تاريخ التحرير: <span class="font-bold">{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d') }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Main Voucher Content Box -->
                <div class="my-6 p-5 border-2 border-slate-200 rounded-lg bg-slate-50/50">
                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                        <div>
                            <span class="text-slate-500 text-xs block">وصلنا من السيد / السادة:</span>
                            <strong class="text-slate-900 text-base font-bold">{{ $payment->customer->name }}</strong>
                            @if($payment->customer->company_name)
                                <span class="text-xs text-slate-600 block">({{ $payment->customer->company_name }})</span>
                            @endif
                            @if($payment->customer->phone)
                                <span class="text-xs text-slate-500 block font-numeric">هاتف: {{ $payment->customer->phone }}</span>
                            @endif
                        </div>
                        <div class="text-left">
                            <span class="text-slate-500 text-xs block">طريقة السداد:</span>
                            <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 rounded font-bold text-xs">
                                {{ $payment->payment_method_label }}
                            </span>
                            @if($payment->reference_number)
                                <div class="text-xs text-slate-600 mt-1">مرجع / شيك: <span class="font-mono font-bold">{{ $payment->reference_number }}</span></div>
                            @endif
                        </div>
                    </div>

                    <!-- Amount Highlight Box -->
                    <div class="my-4 p-4 bg-white border border-slate-300 rounded-md flex justify-between items-center shadow-sm">
                        <span class="font-bold text-slate-700">مبلغ وقدره:</span>
                        <div class="font-numeric font-black text-2xl text-emerald-700">
                            {{ number_format((float)$payment->amount, 2) }} <span class="text-sm font-bold text-slate-600">جنيه مصري</span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-700 leading-relaxed border-t border-slate-200 pt-3">
                        <span class="font-semibold text-slate-500">وذلك عن:</span>
                        @if($payment->salesInvoice)
                            <span>سداد دفعة / قسط للفاتورة رقم <strong class="font-mono text-slate-900 font-bold">#{{ $payment->salesInvoice->invoice_number }}</strong> المؤرخة بتاريخ {{ $payment->salesInvoice->invoice_date?->format('Y-m-d') }}</span>
                            <div class="mt-2 text-[11px] text-slate-500 flex gap-6">
                                <span>إجمالي الفاتورة: <strong class="font-numeric text-slate-800">{{ number_format((float)$payment->salesInvoice->total_amount, 2) }} ج.م</strong></span>
                                <span>المدفوع الإجمالي: <strong class="font-numeric text-emerald-700">{{ number_format((float)$payment->salesInvoice->paid_amount, 2) }} ج.م</strong></span>
                                <span>المتبقي حالياً: <strong class="font-numeric text-rose-700">{{ number_format((float)$payment->salesInvoice->remaining_amount, 2) }} ج.م</strong></span>
                            </div>
                        @else
                            <span>دفعة مسددة على الحساب الجاري للعميل طرفنا</span>
                        @endif

                        @if($payment->notes)
                            <div class="mt-2 text-slate-600">
                                <span class="font-semibold">ملاحظات:</span> {{ $payment->notes }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Customer Account Status Box -->
                <div class="p-3 bg-slate-100 rounded text-xs flex justify-between items-center text-slate-700 mb-8">
                    <span>الرصيد المتبقي على العميل بعد قيد هذا السند:</span>
                    <strong class="font-numeric font-black text-sm {{ (float)$payment->customer->current_balance > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                        {{ number_format((float)$payment->customer->current_balance, 2) }} ج.م
                        ({{ (float)$payment->customer->current_balance > 0 ? 'مستحق على العميل' : ((float)$payment->customer->current_balance < 0 ? 'رصيد دائن لصالح العميل' : 'حساب متزن') }})
                    </strong>
                </div>

                <!-- Signatures Grid -->
                <div class="grid grid-cols-3 gap-6 text-center text-xs text-slate-600 pt-6 border-t border-slate-300">
                    <div>
                        <span class="block text-slate-400 mb-8">المستلم / أمين الصندوق</span>
                        <div class="font-bold text-slate-800">{{ $payment->creator->name ?? 'أمين الصندوق' }}</div>
                    </div>
                    <div>
                        <span class="block text-slate-400 mb-8">توقيع العميل / المسدد</span>
                        <div class="border-b border-dashed border-slate-300 w-32 mx-auto"></div>
                    </div>
                    <div>
                        <span class="block text-slate-400 mb-8">ختم الشركة المعتمد</span>
                        <div class="w-20 h-20 border border-dashed border-slate-300 rounded-full mx-auto flex items-center justify-center text-[10px] text-slate-300">
                            الختم
                        </div>
                    </div>
                </div>

            @else
                <!-- Thermal 80mm Layout -->
                <div class="text-center pb-2 border-b border-dashed border-slate-400">
                    <h2 class="font-black text-base">{{ $company->company_name ?? 'كيان' }}</h2>
                    <p class="text-[11px] font-bold mt-1">سند قبض نقدية</p>
                    <p class="text-[10px] text-slate-600 font-numeric">{{ $payment->payment_number }}</p>
                    <p class="text-[10px] text-slate-600 font-numeric">{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d') }}</p>
                </div>

                <div class="py-2 text-[11px] leading-relaxed border-b border-dashed border-slate-400">
                    <div>العميل: <strong>{{ $payment->customer->name }}</strong></div>
                    <div>طريقة الدفع: <strong>{{ $payment->payment_method_label }}</strong></div>
                    @if($payment->reference_number)
                        <div>المرجع: <span class="font-mono">{{ $payment->reference_number }}</span></div>
                    @endif
                    @if($payment->salesInvoice)
                        <div>فاتورة رقم: <strong>#{{ $payment->salesInvoice->invoice_number }}</strong></div>
                    @endif
                </div>

                <div class="my-3 py-2 text-center bg-slate-100 rounded">
                    <span class="text-[10px] block text-slate-600">المبلغ المقبوض:</span>
                    <strong class="font-numeric font-black text-xl text-slate-900">
                        {{ number_format((float)$payment->amount, 2) }} ج.م
                    </strong>
                </div>

                @if($payment->salesInvoice)
                    <div class="text-[10px] leading-tight pb-2 border-b border-dashed border-slate-400">
                        <div class="flex justify-between"><span>إجمالي الفاتورة:</span> <span class="font-numeric font-bold">{{ number_format((float)$payment->salesInvoice->total_amount, 2) }}</span></div>
                        <div class="flex justify-between"><span>المدفوع حتى الآن:</span> <span class="font-numeric font-bold">{{ number_format((float)$payment->salesInvoice->paid_amount, 2) }}</span></div>
                        <div class="flex justify-between font-bold text-rose-700"><span>المتبقي بالفاتورة:</span> <span class="font-numeric">{{ number_format((float)$payment->salesInvoice->remaining_amount, 2) }}</span></div>
                    </div>
                @endif

                <div class="text-[10px] py-2 flex justify-between font-bold border-b border-dashed border-slate-400">
                    <span>رصيد العميل الحالي:</span>
                    <span class="font-numeric">{{ number_format((float)$payment->customer->current_balance, 2) }} ج.م</span>
                </div>

                <div class="pt-3 text-center text-[10px] text-slate-500">
                    <div>المحصل: {{ $payment->creator->name ?? 'الكاشير' }}</div>
                    <div class="mt-1">شكراً لتعاملكم معنا</div>
                </div>
            @endif
        </article>
    </main>

</body>
</html>
