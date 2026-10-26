@extends('layouts.portal', ['title' => 'كشف العمولات'])

@section('content')
<div class="space-y-4">

    <!-- Header & Rep Rate Info (Kayan ERP Card) -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-light)]">
            <div>
                <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                    <x-icon name="dollar-sign" size="18" class="text-[var(--primary)]" />
                    <span>كشف حساب عمولات المندوب</span>
                </h1>
                <p class="text-xs text-[var(--text-muted)] mt-0.5">تفاصيل العمولات المستحقة والمدفوعة عن فواتيرك المعتمدة</p>
            </div>

            <span class="erp-badge erp-badge-primary font-numeric text-xs font-bold">
                نسبتك: {{ (float) ($user->commission_rate ?? 0) }}{{ $user->commission_type === 'percentage' ? '%' : ' ج.م' }}
            </span>
        </div>
    </div>

    <!-- Financial KPIs (Modern High-Contrast Polish) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Total Earned -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between shadow-xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between gap-1.5 mb-1.5">
                <span class="text-xs sm:text-sm font-bold text-slate-500 dark:text-slate-400 truncate">إجمالي الأرباح المستحقة</span>
                <span class="w-8 h-8 rounded-xl bg-purple-100 text-[#714B67] dark:bg-purple-950/60 dark:text-purple-300 flex items-center justify-center shrink-0">
                    <x-icon name="dollar-sign" size="18" strokeWidth="2.4" />
                </span>
            </div>
            <div class="font-numeric text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate my-1">
                {{ number_format($totalEarned, 2) }} <span class="text-xs font-bold text-slate-400">ج.م</span>
            </div>
            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate mt-1">
                عن كافة فواتير البيع المعتمدة
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between shadow-xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between gap-1.5 mb-1.5">
                <span class="text-xs sm:text-sm font-bold text-slate-500 dark:text-slate-400 truncate">عمولات معلقة (قيد الانتظار)</span>
                <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 flex items-center justify-center shrink-0">
                    <x-icon name="clock" size="18" strokeWidth="2.4" />
                </span>
            </div>
            <div class="font-numeric text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate my-1">
                {{ number_format($pendingAmount, 2) }} <span class="text-xs font-bold text-slate-400">ج.م</span>
            </div>
            <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400 truncate mt-1 flex items-center gap-1">
                <x-icon name="clock" size="12" strokeWidth="2.3" />
                <span>بانتظار الصرف من الإدارة</span>
            </div>
        </div>

        <!-- Paid -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between shadow-xs hover:shadow-sm transition-all">
            <div class="flex items-center justify-between gap-1.5 mb-1.5">
                <span class="text-xs sm:text-sm font-bold text-slate-500 dark:text-slate-400 truncate">عمولات تم صرفها</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center justify-center shrink-0">
                    <x-icon name="check-circle" size="18" strokeWidth="2.4" />
                </span>
            </div>
            <div class="font-numeric text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate my-1">
                {{ number_format($paidAmount, 2) }} <span class="text-xs font-bold text-slate-400">ج.م</span>
            </div>
            <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate mt-1 flex items-center gap-1">
                <x-icon name="check-circle" size="12" strokeWidth="2.3" />
                <span>تم استلامها وتحويلها</span>
            </div>
        </div>
    </div>

    <!-- Commissions Responsive Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($commissions as $comm)
            <div class="erp-card mb-0 p-4 space-y-2 hover:border-[var(--primary)] hover:shadow-xs transition-all">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-[var(--text-main)] truncate">{{ $comm->customer?->name ?? 'عميل نقدي' }}</span>
                            @php
                                $badgeClass = match($comm->status) {
                                    'paid' => 'erp-badge-success',
                                    'approved' => 'erp-badge-info',
                                    'canceled' => 'erp-badge-danger',
                                    default => 'erp-badge-warning',
                                };
                            @endphp
                            <span class="erp-badge {{ $badgeClass }} text-[10px] shrink-0">
                                {{ $comm->status_label }}
                            </span>
                        </div>
                        <div class="text-[11px] text-[var(--text-muted)] font-numeric mt-0.5 flex items-center gap-2">
                            @if($comm->salesInvoice)
                                <a href="{{ route('portal.orders.show', $comm->salesInvoice) }}" class="text-[var(--primary)] hover:underline font-bold">
                                    فاتورة #{{ $comm->salesInvoice->invoice_number }}
                                </a>
                                <span>·</span>
                            @endif
                            <span>{{ $comm->created_at->format('Y/m/d') }}</span>
                        </div>
                    </div>

                    <div class="text-left font-numeric shrink-0">
                        <div class="text-xs sm:text-sm font-extrabold text-emerald-700">
                            +{{ number_format($comm->commission_amount, 2) }} <span class="text-[10px] font-normal">ج.م</span>
                        </div>
                        <span class="text-[10px] text-[var(--text-muted)]">
                            (نسبة {{ $comm->commission_rate }}%)
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-[var(--text-muted)] pt-2.5 border-t border-[var(--border-light)] font-numeric text-[11px]">
                    <span>قيمة الفاتورة: <strong>{{ number_format($comm->invoice_total, 2) }} ج.م</strong></span>
                    @if($comm->paid_at)
                        <span class="text-emerald-700 font-medium">صُرفت: {{ $comm->paid_at->format('Y/m/d') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full erp-card p-12 text-center">
                <x-icon name="dollar-sign" size="36" class="text-slate-300 mx-auto mb-2" />
                <div class="text-sm font-bold text-[var(--text-main)]">لا توجد عمولات مسجلة بعد</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">عند ترحيل فواتير عملائك ستظهر عمولاتك تلقائياً هنا</div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($commissions->hasPages())
        <div class="erp-card p-3 mb-0">
            {{ $commissions->links() }}
        </div>
    @endif

</div>
@endsection
