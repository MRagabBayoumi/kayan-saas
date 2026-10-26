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

    <!-- Screen-Only Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>{{ $title }}</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
            </div>

            <span class="toolbar-badge success font-numeric">
                إجمالي القيد: {{ number_format((float)$entry->total_debit, 2) }} ج.م
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة سند القيد</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <main class="invoice-page-container">
        <article class="invoice-sheet" data-format="{{ $format }}">
            
            <!-- Document Header -->
            <div class="doc-header flex justify-between items-start pb-4 border-b-2 border-slate-300">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">{{ $company->company_name ?? 'شركة كيان للحلول والأنظمة' }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">الإدارة المالية والحسابات العامة · دفتر اليومية المركزية</p>
                    @if(!empty($company?->company_phone))
                        <p class="text-[11px] text-slate-500 font-numeric">هاتف: {{ $company->company_phone }}</p>
                    @endif
                </div>

                <div class="text-left">
                    <div class="inline-block px-4 py-1.5 bg-slate-100 border-2 border-slate-700 rounded-lg">
                        <span class="text-lg font-black text-slate-800">سند قيد يومية عامة</span>
                    </div>
                    <div class="mt-1 font-mono text-sm font-black text-slate-700">{{ $entry->entry_number }}</div>
                </div>
            </div>

            <!-- Meta Details Grid -->
            <div class="grid grid-cols-2 gap-4 py-3 border-b border-slate-200 text-xs">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 w-28">تاريخ القيد:</span>
                        <span class="font-numeric font-bold text-slate-900">{{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : date('Y-m-d') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 w-28">المستند المصدري:</span>
                        <span class="font-semibold text-slate-900">{{ $entry->reference_type_label }} @if($entry->reference_id) #{{ $entry->reference_id }} @endif</span>
                    </div>
                </div>

                <div class="space-y-1.5 text-left">
                    <div class="flex items-center justify-end gap-2">
                        <span class="font-bold text-slate-700">المحاسب المنشئ:</span>
                        <span class="text-slate-900 font-semibold">{{ $entry->creator?->name ?? 'النظام الآلي' }}</span>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <span class="font-bold text-slate-700">حالة القيد:</span>
                        <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">مُرحل ومعتمد</span>
                    </div>
                </div>
            </div>

            <!-- Memo / Description -->
            <div class="my-3 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                <span class="font-bold text-slate-700 block mb-0.5">البيان والشرح العام:</span>
                <p class="text-slate-900 font-medium leading-relaxed">{{ $entry->description ?: 'قيد محاسبي آلي بالنظام.' }}</p>
            </div>

            <!-- Journal Lines Table -->
            <div class="my-4">
                <table class="w-full text-right text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-y-2 border-slate-400 text-slate-700 font-bold">
                            <th class="p-2 w-8 text-center border-r border-slate-300">#</th>
                            <th class="p-2 w-24 border-r border-slate-300">كود الحساب</th>
                            <th class="p-2 border-r border-slate-300">اسم الحساب المالي</th>
                            <th class="p-2 text-left font-numeric w-28 border-r border-slate-300">مدين (Dr)</th>
                            <th class="p-2 text-left font-numeric w-28 border-r border-slate-300">دائن (Cr)</th>
                            <th class="p-2 border-r border-slate-300">البيان وملاحظات السطر</th>
                            <th class="p-2 w-36">الطرف المسجل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 border-b-2 border-slate-400">
                        @foreach($entry->lines as $idx => $line)
                            @php
                                $debit = (float) $line->debit;
                                $credit = (float) $line->credit;
                                $partyName = $line->customer ? ('عميل: ' . $line->customer->name) : ($line->supplier ? ('مورد: ' . $line->supplier->name) : '—');
                            @endphp
                            <tr class="text-slate-800">
                                <td class="p-2 text-center font-numeric border-r border-slate-200">{{ $idx + 1 }}</td>
                                <td class="p-2 font-mono font-bold border-r border-slate-200">{{ $line->account?->code ?? '—' }}</td>
                                <td class="p-2 font-bold border-r border-slate-200">{{ $line->account?->name ?? '—' }}</td>
                                <td class="p-2 text-left font-numeric font-bold border-r border-slate-200">
                                    {{ $debit > 0 ? number_format($debit, 2) : '—' }}
                                </td>
                                <td class="p-2 text-left font-numeric font-bold border-r border-slate-200">
                                    {{ $credit > 0 ? number_format($credit, 2) : '—' }}
                                </td>
                                <td class="p-2 text-slate-600 border-r border-slate-200">{{ $line->description ?: '—' }}</td>
                                <td class="p-2 text-[11px] font-medium">{{ $partyName }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-black text-slate-900 border-b-2 border-slate-500">
                            <td colspan="3" class="p-2.5 text-left border-r border-slate-300">الإجمالي العام للقيد:</td>
                            <td class="p-2.5 text-left font-numeric border-r border-slate-300 text-slate-900 text-sm">
                                {{ number_format((float) $entry->total_debit, 2) }}
                            </td>
                            <td class="p-2.5 text-left font-numeric border-r border-slate-300 text-slate-900 text-sm">
                                {{ number_format((float) $entry->total_credit, 2) }}
                            </td>
                            <td colspan="2" class="p-2.5 text-center text-xs">
                                @if($entry->isBalanced())
                                    <span class="text-emerald-700 font-bold">✓ القيد متزن محاسبياً 100%</span>
                                @else
                                    <span class="text-rose-700 font-bold">⚠ غير متزن</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures Strip -->
            <div class="grid grid-cols-3 gap-6 mt-8 pt-6 border-t-2 border-slate-300 text-center text-xs">
                <div>
                    <div class="font-bold text-slate-700 mb-8">إعداد (المحاسب المسؤول)</div>
                    <div class="border-t border-slate-400 pt-1 text-slate-600 font-semibold">
                        {{ $entry->creator?->name ?? 'المحاسب' }}
                    </div>
                </div>

                <div>
                    <div class="font-bold text-slate-700 mb-8">المراجعة والتدقيق الداخلي</div>
                    <div class="border-t border-slate-400 pt-1 text-slate-500">التوقيع والتاريخ</div>
                </div>

                <div>
                    <div class="font-bold text-slate-700 mb-8">اعتماد الإدارة المالية</div>
                    <div class="border-t border-slate-400 pt-1 text-slate-500">الختم / التوقيع</div>
                </div>
            </div>

        </article>
    </main>

</body>
</html>
