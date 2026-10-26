@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سند قيد يومية: {{ $entry->entry_number }}" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), 'قيود اليومية' => route('accounting.journal-entries'), $entry->entry_number => route('accounting.journal-entries.show', $entry)]">
        <x-slot:actions>
            <a href="{{ route('accounting.journal-entries.print', $entry) }}" target="_blank" class="erp-btn erp-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة سند القيد</span>
            </a>
            @if($entry->source_document_url)
                <a href="{{ $entry->source_document_url }}" class="erp-btn erp-btn-secondary" title="الانتقال للمستند المصدر المنشئ للقيد">
                    <x-icon name="file-text" size="16" />
                    <span>عرض {{ $entry->reference_type_label }} الأصلية</span>
                </a>
            @endif
            <a href="{{ route('accounting.journal-entries') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لدفتر القيود</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $entry->status_badge_class }} font-numeric px-3 py-1 text-xs">
                <span>الحالة:</span>
                <strong class="mr-1">{{ $entry->status_label }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="journal-entry-show" class="max-w-5xl mx-auto space-y-5">
        
        <!-- Voucher Document Sheet Card -->
        <div class="erp-card p-6 md:p-8">
            
            <!-- Document Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-5 border-b border-[var(--border-color)]">
                <div>
                    <h2 class="text-xl font-black text-[var(--text-main)]">{{ $company->company_name ?? 'شركة كيان' }}</h2>
                    <p class="text-xs text-[var(--text-muted)] mt-1">الإدارة المالية والحسابات العامة · دفتر اليومية المركزية</p>
                    @if(!empty($company?->company_phone))
                        <p class="text-[11px] text-[var(--text-muted)] font-numeric">هاتف: {{ $company->company_phone }}</p>
                    @endif
                </div>

                <div class="text-left flex flex-col items-start sm:items-end">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-[var(--primary-soft)] border border-[var(--primary-border)] rounded-lg text-[var(--primary)] font-bold text-xs">
                        <x-icon name="file-text" size="16" />
                        <span>سند قيد يومية عامة</span>
                    </div>
                    <div class="mt-2 font-mono text-base font-black text-[var(--text-main)] tracking-wider bg-[var(--bg-surface-alt)] px-3 py-1 rounded border border-[var(--border-color)]">
                        {{ $entry->entry_number }}
                    </div>
                </div>
            </div>

            <!-- Metadata Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 border-b border-[var(--border-color)] text-xs">
                <div>
                    <span class="text-[var(--text-muted)] block mb-1">تاريخ استحقاق القيد:</span>
                    <strong class="font-numeric text-sm text-[var(--text-main)]">
                        {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : '—' }}
                    </strong>
                </div>

                <div>
                    <span class="text-[var(--text-muted)] block mb-1">المستند المصدري:</span>
                    <span class="erp-badge erp-badge-neutral text-xs font-semibold">
                        {{ $entry->reference_type_label }}
                    </span>
                    @if($entry->reference_id)
                        <span class="font-mono text-xs text-[var(--text-muted)] mr-1">#{{ $entry->reference_id }}</span>
                    @endif
                </div>

                <div>
                    <span class="text-[var(--text-muted)] block mb-1">تاريخ ووقت الترحيل:</span>
                    <span class="font-numeric text-xs text-[var(--text-main)] font-medium">
                        {{ $entry->posted_at ? $entry->posted_at->format('Y-m-d H:i') : 'مسودة' }}
                    </span>
                </div>

                <div>
                    <span class="text-[var(--text-muted)] block mb-1">المحاسب المنشئ:</span>
                    <strong class="text-xs text-[var(--text-main)] font-bold">
                        {{ $entry->creator?->name ?? 'النظام الآلي' }}
                    </strong>
                </div>
            </div>

            <!-- General Description / Memo -->
            <div class="my-4 p-3.5 bg-[var(--bg-surface-alt)] rounded-lg border border-[var(--border-color)]">
                <span class="text-xs text-[var(--text-muted)] font-bold block mb-1">البيان والشرح العام للقيد:</span>
                <p class="text-sm font-semibold text-[var(--text-main)] leading-relaxed">
                    {{ $entry->description ?: 'لا يوجد بيان إضافي مسجل.' }}
                </p>
            </div>

            <!-- Journal Lines Table -->
            <div class="erp-table-responsive my-6 border border-[var(--border-color)] rounded-lg overflow-hidden">
                <table class="erp-table text-xs">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th class="w-28">كود الحساب</th>
                            <th>اسم الحساب</th>
                            <th class="text-left font-numeric w-32">مدين (Dr)</th>
                            <th class="text-left font-numeric w-32">دائن (Cr)</th>
                            <th>البيان / ملاحظات السطر</th>
                            <th class="w-40">الطرف المسجل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entry->lines as $idx => $line)
                            @php
                                $debit = (float) $line->debit;
                                $credit = (float) $line->credit;
                                $partyName = $line->customer ? ('عميل: ' . $line->customer->name) : ($line->supplier ? ('مورد: ' . $line->supplier->name) : '—');
                            @endphp
                            <tr class="erp-table-row">
                                <td class="text-center text-[var(--text-muted)] font-numeric">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="font-mono font-bold text-[var(--text-main)] bg-[var(--bg-surface-alt)] px-2 py-0.5 rounded border border-[var(--border-color)]">
                                        {{ $line->account?->code ?? '—' }}
                                    </span>
                                </td>
                                <td class="font-semibold text-[var(--text-main)]">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $line->account?->name ?? 'حساب غير محدد' }}</span>
                                        @if($line->account?->nature)
                                            <span class="text-[10px] text-[var(--text-muted)] font-normal">({{ $line->account->nature === 'debit' ? 'مدين' : 'دائن' }})</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-left font-numeric {{ $debit > 0 ? 'font-bold text-[var(--success-text)] text-sm' : 'text-[var(--text-muted)]' }}">
                                    {{ $debit > 0 ? number_format($debit, 2) : '—' }}
                                </td>
                                <td class="text-left font-numeric {{ $credit > 0 ? 'font-bold text-[var(--info-text)] text-sm' : 'text-[var(--text-muted)]' }}">
                                    {{ $credit > 0 ? number_format($credit, 2) : '—' }}
                                </td>
                                <td class="text-[var(--text-muted)]">
                                    {{ $line->description ?: '—' }}
                                </td>
                                <td class="text-xs text-[var(--text-secondary)] font-medium">
                                    {{ $partyName }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-[var(--bg-surface-alt)] font-black border-t-2 border-[var(--border-color)] text-sm">
                        <tr>
                            <td colspan="3" class="text-left p-3">إجمالي القيد:</td>
                            <td class="text-left font-numeric text-[var(--success-text)] p-3">
                                {{ number_format((float) $entry->total_debit, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                            </td>
                            <td class="text-left font-numeric text-[var(--info-text)] p-3">
                                {{ number_format((float) $entry->total_credit, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                            </td>
                            <td colspan="2" class="text-center p-3">
                                @if($entry->isBalanced())
                                    <span class="erp-badge erp-badge-success text-xs font-bold px-3 py-1">
                                        متزن محاسبياً بنسبة 100%
                                    </span>
                                @else
                                    <span class="erp-badge erp-badge-danger text-xs font-bold px-3 py-1">
                                        غير متزن (فارق: {{ number_format(abs((float)$entry->total_debit - (float)$entry->total_credit), 2) }} ج.م)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-3 gap-6 pt-6 mt-6 border-t border-[var(--border-color)] text-center text-xs">
                <div class="space-y-10">
                    <div class="font-bold text-[var(--text-muted)]">إعداد (المحاسب المسؤول)</div>
                    <div class="border-t border-[var(--border-color)] pt-1 font-semibold text-[var(--text-main)]">
                        {{ $entry->creator?->name ?? 'أمين الحسابات' }}
                    </div>
                </div>

                <div class="space-y-10">
                    <div class="font-bold text-[var(--text-muted)]">المراجعة والتدقيق الداخلي</div>
                    <div class="border-t border-[var(--border-color)] pt-1 text-[var(--text-muted)]">التوقيع والتاريخ</div>
                </div>

                <div class="space-y-10">
                    <div class="font-bold text-[var(--text-muted)]">اعتماد الإدارة المالية</div>
                    <div class="border-t border-[var(--border-color)] pt-1 text-[var(--text-muted)]">الختم / التوقيع</div>
                </div>
            </div>

        </div>
    </div>
@endsection
