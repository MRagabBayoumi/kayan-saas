@extends('layouts.portal', ['title' => 'تفاصيل الطلب #' . $invoice->invoice_number])

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Header & Status Bar (Kayan ERP Card) -->
    <div class="erp-card mb-0 p-4 sm:p-5 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs sm:text-sm font-bold text-[var(--text-muted)] font-numeric">#{{ $invoice->invoice_number }}</span>
                @if($invoice->status === 'posted')
                    <span class="erp-badge erp-badge-success text-[10px]">
                        مرحّلة ومعتمدة
                    </span>
                @elseif($invoice->status === 'draft')
                    <span class="erp-badge erp-badge-warning text-[10px]">
                        مسودة طلب معلقة
                    </span>
                @else
                    <span class="erp-badge erp-badge-danger text-[10px]">
                        ملغاة
                    </span>
                @endif
            </div>
            <h1 class="text-base sm:text-lg font-bold text-[var(--text-main)]">{{ $invoice->customer?->name ?? 'عميل نقدي' }}</h1>
        </div>

        <a href="{{ route('portal.orders.index') }}" class="erp-btn erp-btn-secondary erp-btn-sm text-xs">
            <x-icon name="arrow-right" size="13" />
            <span>رجوع للسجل</span>
        </a>
    </div>

    <!-- Order Metadata (4 Columns on Desktop) -->
    <div class="erp-card mb-0 p-4 sm:p-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <span class="text-[var(--text-muted)] block text-[11px]">تاريخ الفاتورة</span>
            <span class="font-bold text-[var(--text-main)] font-numeric">{{ $invoice->invoice_date->format('Y/m/d') }}</span>
        </div>
        <div>
            <span class="text-[var(--text-muted)] block text-[11px]">المستودع</span>
            <span class="font-bold text-[var(--text-main)]">{{ $invoice->warehouse?->name ?? 'المستودع الرئيسي' }}</span>
        </div>
        <div>
            <span class="text-[var(--text-muted)] block text-[11px]">طريقة السداد</span>
            <span class="font-bold text-[var(--text-main)]">
                {{ match($invoice->payment_type) {
                    'credit' => 'آجل بالكامل',
                    'partial' => 'دفع جزئي',
                    default => 'نقدي فوري'
                } }}
            </span>
        </div>
        <div>
            <span class="text-[var(--text-muted)] block text-[11px]">فئة السعر</span>
            <span class="font-bold text-[var(--text-main)]">
                {{ match($invoice->price_tier) {
                    'wholesale' => 'جملة',
                    'distributor' => 'موزع',
                    default => 'تجزئة'
                } }}
            </span>
        </div>
    </div>

    <!-- Items Breakdown -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <h2 class="erp-card-title text-xs sm:text-sm pb-2 border-b border-[var(--border-light)]">
            <x-icon name="shopping-bag" size="16" class="text-[var(--primary)]" />
            <span>الأصناف والبنود المسجلة</span>
        </h2>

        <div class="divide-y divide-[var(--border-light)]">
            @foreach($invoice->items as $item)
                <div class="py-2.5 flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs sm:text-sm font-bold text-[var(--text-main)] truncate">{{ $item->product?->name ?? 'صنف غير محدد' }}</div>
                        <div class="text-[11px] text-[var(--text-muted)] font-numeric mt-0.5">
                            {{ (float)$item->quantity }} {{ $item->unit?->name ?? ($item->product?->unit ?? 'قطعة') }} &times; {{ number_format($item->unit_price, 2) }} ج.م
                            @if((float)$item->line_discount > 0)
                                <span class="text-rose-500 font-medium"> (خصم: {{ number_format($item->line_discount, 2) }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-left font-numeric font-extrabold text-xs sm:text-sm text-[var(--text-main)] shrink-0">
                        {{ number_format($item->subtotal, 2) }} ج.م
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Financial Summary Box -->
        <div class="p-3.5 rounded bg-[var(--bg-hover)] border border-[var(--border-light)] space-y-2 pt-3">
            <div class="flex items-center justify-between text-xs text-[var(--text-secondary)]">
                <span>إجمالي الطلب:</span>
                <span class="font-extrabold text-[var(--text-main)] text-sm font-numeric">{{ number_format($invoice->total_amount, 2) }} ج.م</span>
            </div>
            <div class="flex items-center justify-between text-xs text-[var(--text-muted)]">
                <span>المبلغ المدفوع:</span>
                <span class="font-bold text-emerald-700 font-numeric">{{ number_format($invoice->paid_amount, 2) }} ج.م</span>
            </div>
            <div class="flex items-center justify-between text-xs text-[var(--text-muted)]">
                <span>المتبقي (آجل):</span>
                <span class="font-bold text-rose-600 font-numeric">{{ number_format($invoice->remaining_amount, 2) }} ج.م</span>
            </div>
        </div>
    </div>

    <!-- Sales Rep Commission Card -->
    @if($invoice->commission)
        <div class="erp-card mb-0 p-4 border-emerald-200 bg-emerald-50/50 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-emerald-800 block">عمولة المندوب عن هذا الطلب</span>
                <div class="text-base sm:text-lg font-extrabold text-emerald-800 font-numeric mt-0.5">
                    +{{ number_format($invoice->commission->commission_amount, 2) }} <span class="text-xs font-semibold">ج.م</span>
                </div>
                <span class="text-[10px] text-emerald-700 font-medium">
                    نسبة: {{ $invoice->commission->commission_rate }}%
                </span>
            </div>

            <div>
                @php
                    $commStatusBadge = match($invoice->commission->status) {
                        'paid' => 'erp-badge-success',
                        'approved' => 'erp-badge-info',
                        default => 'erp-badge-warning',
                    };
                    $commStatusLabel = match($invoice->commission->status) {
                        'paid' => 'تم الصرف',
                        'approved' => 'معتمدة',
                        default => 'قيد الانتظار',
                    };
                @endphp
                <span class="erp-badge {{ $commStatusBadge }} text-xs font-bold">
                    {{ $commStatusLabel }}
                </span>
            </div>
        </div>
    @endif

    <!-- Action Toolbar (WhatsApp share, Print invoice) -->
    <div class="grid grid-cols-2 gap-2.5 pt-1">
        @php
            $cleanPhone = preg_replace('/[^0-9]/', '', $invoice->customer?->phone ?? '');
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '2' . $cleanPhone;
            }
            $waText = urlencode("مرحباً بك عميلنا العزيز {$invoice->customer?->name}،\nتفاصيل طلبك رقم {$invoice->invoice_number}:\nالإجمالي: {$invoice->total_amount} ج.م\nالمدفوع: {$invoice->paid_amount} ج.م\nالمتبقي: {$invoice->remaining_amount} ج.م\nشكراً لتعاملك معنا!");
        @endphp
        @if($cleanPhone)
            <a href="https://wa.me/{{ $cleanPhone }}?text={{ $waText }}" target="_blank"
                class="erp-btn bg-emerald-700 hover:bg-emerald-800 text-white w-full py-2.5 text-xs font-bold justify-center">
                <x-icon name="send" size="14" />
                <span>مشاركة بالواتساب</span>
            </a>
        @endif

        <a href="{{ route('sales.print', ['sale' => $invoice->id, 'type' => 'thermal']) }}" target="_blank"
            class="erp-btn erp-btn-secondary w-full py-2.5 text-xs font-bold justify-center {{ empty($cleanPhone) ? 'col-span-2' : '' }}">
            <x-icon name="printer" size="14" />
            <span>طباعة الإيصال الحراري</span>
        </a>
    </div>

</div>
@endsection
