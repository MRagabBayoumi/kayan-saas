@extends('layouts.portal', ['title' => 'سجل طلباتي'])

@section('content')
<div class="space-y-4">

    <!-- Top Action & Filter Header (Kayan ERP Card) -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-light)]">
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="file-text" size="18" class="text-[var(--primary)]" />
                <span>سجل طلبات وفواتير البيع</span>
            </h1>

            <a href="{{ route('portal.orders.create') }}" class="erp-btn erp-btn-primary text-xs">
                <x-icon name="plus" size="14" />
                <span>طلب جديد</span>
            </a>
        </div>

        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-[var(--bg-hover)] border border-[var(--border-color)] rounded text-xs">
            <a href="{{ route('portal.orders.index') }}" 
               class="flex-1 py-1.5 text-center rounded transition-all {{ empty($currentStatus) ? 'bg-white text-[var(--primary)] font-bold shadow-xs border border-[var(--border-color)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                الكل ({{ $orders->total() }})
            </a>
            <a href="{{ route('portal.orders.index', ['status' => 'posted']) }}" 
               class="flex-1 py-1.5 text-center rounded transition-all {{ $currentStatus === 'posted' ? 'bg-white text-emerald-700 font-bold shadow-xs border border-[var(--border-color)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                معتمدة ومرحلة
            </a>
            <a href="{{ route('portal.orders.index', ['status' => 'draft']) }}" 
               class="flex-1 py-1.5 text-center rounded transition-all {{ $currentStatus === 'draft' ? 'bg-white text-amber-700 font-bold shadow-xs border border-[var(--border-color)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                مسودات معلقة
            </a>
        </div>
    </div>

    <!-- Orders Cards Responsive Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($orders as $order)
            <a href="{{ route('portal.orders.show', $order) }}" class="erp-card mb-0 p-4 hover:border-[var(--primary)] hover:shadow-xs transition-all block">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-[var(--text-muted)] font-numeric">#{{ $order->invoice_number }}</span>
                            @if($order->status === 'posted')
                                <span class="erp-badge erp-badge-success text-[10px]">
                                    معتمدة ومرحّلة
                                </span>
                            @elseif($order->status === 'draft')
                                <span class="erp-badge erp-badge-warning text-[10px]">
                                    مسودة قيد الاعتماد
                                </span>
                            @else
                                <span class="erp-badge erp-badge-danger text-[10px]">
                                    ملغاة
                                </span>
                            @endif
                        </div>
                        <h2 class="text-sm font-bold text-[var(--text-main)] truncate">{{ $order->customer?->name ?? 'عميل نقدي' }}</h2>
                    </div>

                    <div class="text-left shrink-0">
                        <div class="text-sm sm:text-base font-extrabold text-[var(--text-main)] font-numeric">
                            {{ number_format($order->total_amount, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                        </div>
                        @if($order->commission)
                            <div class="text-[11px] text-emerald-700 font-bold font-numeric mt-0.5">
                                عمولة: +{{ number_format($order->commission->commission_amount, 2) }} ج.م
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-[var(--text-muted)] pt-2.5 border-t border-[var(--border-light)] mt-2">
                    <div class="flex items-center gap-2 font-numeric text-[11px]">
                        <span>{{ $order->invoice_date->format('Y/m/d') }}</span>
                        @if($order->warehouse)
                            <span>· {{ $order->warehouse->name }}</span>
                        @endif
                    </div>

                    <span class="text-[var(--primary)] font-bold text-xs flex items-center gap-0.5">
                        <span>التفاصيل</span>
                        <x-icon name="chevron-left" size="14" />
                    </span>
                </div>
            </a>
        @empty
            <div class="col-span-full erp-card p-12 text-center">
                <x-icon name="file-text" size="36" class="text-slate-300 mx-auto mb-2" />
                <div class="text-sm font-bold text-[var(--text-main)]">لا توجد طلبات مسجلة</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">اضغط على «طلب جديد» لإنشاء أول فاتورة أو مسودة طلب</div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
        <div class="erp-card p-3 mb-0">
            {{ $orders->links() }}
        </div>
    @endif

</div>
@endsection
