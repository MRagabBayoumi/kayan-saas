@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إذن مرتجع مبيعات: {{ $return->return_number }}" :breadcrumbs="['المبيعات والعملاء' => route('sales.index'), 'مرتجعات المبيعات' => route('sales-returns.index'), $return->return_number => '#']">
        <x-slot:actions>
            @if($return->status === 'draft')
                @can('manage-sales')
                    <form action="{{ route('sales-returns.post', $return) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من ترحيل المرتجع؟ سيتم استرجاع الكميات للمخزون وتسوية حساب العميل فوراً.');">
                        @csrf
                        <button type="submit" class="erp-btn erp-btn-primary bg-emerald-600 hover:bg-emerald-700 text-white">
                            <x-icon name="check-circle" size="16" />
                            <span>ترحيل المرتجع للمخزن</span>
                        </button>
                    </form>
                @endcan
            @endif

            <a href="{{ route('sales-returns.print', $return) }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة إذن المرتجع</span>
            </a>

            <a href="{{ route('sales-returns.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمرتجعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $return->status_badge_class }}">
                {{ $return->status_label }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="sales-returns-show">
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Return Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Basic Info Card -->
            <div class="erp-card p-4 border border-slate-200/80 bg-white shadow-xs">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-slate-100">
                    <x-icon name="info" size="14" class="text-[var(--primary)]" />
                    <span>بيانات المرتجع</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">رقم المرتجع:</span>
                        <span class="font-numeric font-bold text-slate-800">{{ $return->return_number }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">تاريخ الإرجاع:</span>
                        <span class="font-numeric font-semibold">{{ $return->return_date ? $return->return_date->format('Y-m-d') : '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">مستودع الاسترجاع:</span>
                        <span class="font-semibold text-slate-800">{{ $return->warehouse?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">حالة المرتجع:</span>
                        <span class="erp-badge {{ $return->status_badge_class }} text-[11px]">{{ $return->status_label }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Card -->
            <div class="erp-card p-4 border border-slate-200/80 bg-white shadow-xs">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-slate-100">
                    <x-icon name="user" size="14" class="text-[var(--primary)]" />
                    <span>بيانات العميل</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">اسم العميل:</span>
                        <span class="font-bold text-slate-800">{{ $return->customer?->name ?? 'عميل نقدي' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">رقم الهاتف:</span>
                        <span class="font-numeric">{{ $return->customer?->phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">رصيد العميل الحالي:</span>
                        <span class="font-numeric font-bold {{ ($return->customer?->current_balance ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format((float)($return->customer?->current_balance ?? 0), 2) }} ج.م
                        </span>
                    </div>
                </div>
            </div>

            <!-- Invoice & Audit Card -->
            <div class="erp-card p-4 border border-slate-200/80 bg-white shadow-xs">
                <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-slate-100">
                    <x-icon name="file-text" size="14" class="text-[var(--primary)]" />
                    <span>الفاتورة الأصلية والتوثيق</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">فاتورة البيع:</span>
                        @if($return->salesInvoice)
                            <a href="{{ route('sales.show', $return->salesInvoice) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline flex items-center gap-1">
                                <x-icon name="external-link" size="12" />
                                <span>{{ $return->salesInvoice->invoice_number }}</span>
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">تاريخ الترحيل:</span>
                        <span class="font-numeric">{{ $return->posted_at ? $return->posted_at->format('Y-m-d H:i') : 'مسودة غير مرحلة' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">حرر بواسطة:</span>
                        <span>{{ $return->creator?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($return->notes)
            <div class="erp-card p-3 mb-4 text-xs bg-slate-50 border border-slate-200 text-slate-700">
                <strong>ملاحظات المرتجع:</strong> {{ $return->notes }}
            </div>
        @endif

        <!-- Items Table Card -->
        <div class="erp-card overflow-hidden shadow-xs border border-slate-200/80 mb-4 bg-white">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <x-icon name="package" size="15" class="text-[var(--primary)]" />
                    <span>بنود الأصناف المسترجعة</span>
                </h4>
                <span class="erp-badge erp-badge-neutral font-numeric">{{ $return->items->count() }} صنف</span>
            </div>

            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>الصنف</th>
                            <th>الوحدة</th>
                            <th class="text-center">الكمية المسترجعة</th>
                            <th class="text-center">سعر الاسترداد</th>
                            <th class="text-center">إجمالي الاسترداد</th>
                            <th>رقم الدفعة المسترجع إليها</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($return->items as $idx => $item)
                            <tr>
                                <td class="text-center font-numeric text-xs text-slate-400">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="font-bold text-xs text-slate-800">{{ $item->product->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-numeric">كود: {{ $item->product->sku ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-[10px]">{{ $item->unit->name ?? $item->product->unit ?? 'قطعة' }}</span>
                                </td>
                                <td class="text-center font-numeric font-bold text-xs">{{ $item->quantity }}</td>
                                <td class="text-center font-numeric text-xs">{{ number_format((float)$item->unit_refund_price, 2) }} ج.م</td>
                                <td class="text-center font-numeric font-bold text-rose-700 text-xs">{{ number_format((float)$item->total_refund, 2) }} ج.م</td>
                                <td>
                                    @if($item->batch)
                                        <span class="font-numeric text-[11px] text-slate-600 font-semibold">{{ $item->batch->batch_number }}</span>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Financial Summary -->
            <div class="p-4 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    تم التوثيق بحركات وارد (IN) مخزنية موثقة لحماية أرصدة FIFO.
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500">إجمالي قيمة المرتجع المستردة:</span>
                    <span class="text-xl font-black font-numeric text-rose-700 mr-2">{{ number_format((float)$return->total_amount, 2) }}</span>
                    <span class="text-xs font-bold text-slate-400">ج.م</span>
                </div>
            </div>
        </div>
    </div>
@endsection
