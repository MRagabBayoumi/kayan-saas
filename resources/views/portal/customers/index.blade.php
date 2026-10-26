@extends('layouts.portal', ['title' => 'دليل العملاء'])

@section('content')
<div class="space-y-4">

    <!-- Top Action & Search Header (Kayan ERP Card) -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-light)]">
            <div>
                <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                    <x-icon name="users" size="18" class="text-[var(--primary)]" />
                    <span>دليل وقائمة عملاء المندوب</span>
                </h1>
                <div class="text-xs text-[var(--text-muted)] mt-0.5 font-numeric">إجمالي عملائك: {{ $totalMyCustomers }} عميل</div>
            </div>

            <a href="{{ route('portal.customers.create') }}" class="erp-btn erp-btn-primary text-xs">
                <x-icon name="user-plus" size="14" />
                <span>عميل جديد</span>
            </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center justify-between">
            <form method="GET" action="{{ route('portal.customers.index') }}" class="flex-1">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="ابحث باسم العميل أو الشركة أو الهاتف..."
                        class="erp-input pr-9 text-xs sm:text-sm">
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                        <x-icon name="search" size="15" />
                    </div>
                    @if($search)
                        <a href="{{ route('portal.customers.index') }}" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--text-muted)] hover:text-[var(--text-main)]">إلغاء</a>
                    @endif
                    @if(request('approval_status'))
                        <input type="hidden" name="approval_status" value="{{ request('approval_status') }}">
                    @endif
                </div>
            </form>

            <!-- Filter Status Badges -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
                <a href="{{ route('portal.customers.index', array_filter(['search' => $search])) }}"
                    class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors shrink-0 {{ !request('approval_status') ? 'bg-[var(--primary)] text-white border-[var(--primary)]' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)] border-[var(--border-color)]' }}">
                    الكل ({{ $totalMyCustomers }})
                </a>
                <a href="{{ route('portal.customers.index', array_filter(['search' => $search, 'approval_status' => 'approved'])) }}"
                    class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors shrink-0 {{ request('approval_status') === 'approved' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-[var(--bg-hover)] text-emerald-700 border-[var(--border-color)]' }}">
                    معتمد ({{ $approvedCount }})
                </a>
                <a href="{{ route('portal.customers.index', array_filter(['search' => $search, 'approval_status' => 'pending'])) }}"
                    class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors shrink-0 {{ request('approval_status') === 'pending' ? 'bg-amber-600 text-white border-amber-600' : 'bg-[var(--bg-hover)] text-amber-700 border-[var(--border-color)]' }}">
                    قيد المراجعة ({{ $pendingCount }})
                </a>
            </div>
        </div>
    </div>

    <!-- Customers Responsive Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($customers as $customer)
            <div class="erp-card mb-0 p-4 hover:border-[var(--primary)] hover:shadow-xs transition-all flex flex-col justify-between">
                <div>
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-bold text-[var(--text-main)] leading-snug truncate">{{ $customer->name }}</h2>
                            @if($customer->company_name)
                                <div class="text-xs text-[var(--text-muted)] font-medium truncate">{{ $customer->company_name }}</div>
                            @endif
                        </div>

                        <!-- Approval & Pricing Badges -->
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            @if($customer->isApproved())
                                <span class="erp-badge erp-badge-success text-[10px]">
                                    <x-icon name="check" size="10" />
                                    <span>معتمد</span>
                                </span>
                            @elseif($customer->isPending())
                                <span class="erp-badge erp-badge-warning text-[10px]">
                                    <x-icon name="clock" size="10" />
                                    <span>قيد المراجعة</span>
                                </span>
                            @else
                                <span class="erp-badge erp-badge-danger text-[10px]">
                                    <x-icon name="x" size="10" />
                                    <span>مرفوض</span>
                                </span>
                            @endif

                            @php
                                $tierLabel = match($customer->pricing_tier) {
                                    'distributor' => 'موزع',
                                    'wholesale' => 'جملة',
                                    default => 'قطاعي',
                                };
                            @endphp
                            <span class="text-[10px] text-[var(--text-muted)] font-medium">فئة: {{ $tierLabel }}</span>
                        </div>
                    </div>

                    <!-- Meta details: Phone, City, Balance, Credit Limit -->
                    <div class="grid grid-cols-2 gap-2 text-xs text-[var(--text-muted)] my-2.5">
                        <div class="flex items-center gap-1.5 font-numeric">
                            <x-icon name="phone" size="13" class="text-slate-400 shrink-0" />
                            <span class="text-[var(--text-secondary)]">{{ $customer->phone }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 justify-end">
                            <span class="text-[var(--text-muted)] text-[11px]">الرصيد:</span>
                            <span class="font-bold font-numeric {{ (float)$customer->current_balance > 0 ? 'text-rose-600' : 'text-[var(--text-main)]' }}">
                                {{ number_format($customer->current_balance, 2) }} ج.م
                            </span>
                        </div>

                        @if($customer->credit_limit > 0)
                            <div class="flex items-center gap-1.5 font-numeric">
                                <x-icon name="credit-card" size="13" class="text-slate-400 shrink-0" />
                                <span class="text-[11px]">الحد الائتماني: {{ number_format($customer->credit_limit, 2) }} ج.م</span>
                            </div>
                        @endif

                        @if($customer->city || $customer->address)
                            <div class="col-span-2 flex items-center gap-1.5 text-[var(--text-muted)] text-[11px] truncate">
                                <x-icon name="map-pin" size="13" class="text-slate-400 shrink-0" />
                                <span>{{ implode(' - ', array_filter([$customer->city, $customer->address])) }}</span>
                            </div>
                        @endif

                        @if($customer->isRejected() && $customer->rejection_reason)
                            <div class="col-span-2 p-2 rounded bg-rose-50 border border-rose-200 text-rose-700 text-[11px] leading-tight">
                                <span class="font-bold">سبب الرفض:</span> {{ $customer->rejection_reason }}
                            </div>
                        @endif
                    @if(!empty($customer->store_image_urls))
                        <div class="mt-2 pt-2 border-t border-[var(--border-light)]">
                            <div class="text-[10px] text-[var(--text-muted)] font-bold mb-1.5 flex items-center gap-1">
                                <x-icon name="camera" size="12" class="text-[var(--primary)]" strokeWidth="2.2" />
                                <span>صور واجهة ومكان المحل ({{ count($customer->store_image_urls) }})</span>
                            </div>
                            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                @foreach($customer->store_image_urls as $imgUrl)
                                    <a href="{{ $imgUrl }}" target="_blank" class="block w-14 h-14 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 hover:opacity-90 shadow-xs transition-opacity" title="عرض الصورة بالحجم الكامل">
                                        <img src="{{ $imgUrl }}" alt="صورة المحل" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Action Toolbar -->
                <div class="flex items-center justify-between pt-2.5 border-t border-[var(--border-light)] gap-1.5 mt-1">
                    <div class="flex items-center gap-1">
                        <!-- Direct Phone Call -->
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}" title="اتصال هاتفي" 
                                class="p-1.5 rounded border border-[var(--border-color)] text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors">
                                <x-icon name="phone-call" size="14" />
                            </a>
                        @endif

                        <!-- WhatsApp Direct -->
                        @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $customer->phone);
                            if (str_starts_with($cleanPhone, '0')) {
                                $cleanPhone = '2' . $cleanPhone;
                            }
                        @endphp
                        @if($cleanPhone)
                            <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" title="محادثة واتساب" 
                                class="p-1.5 rounded border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                <x-icon name="message-circle" size="14" />
                            </a>
                        @endif

                        <!-- Google Maps GPS Link -->
                        @if($customer->latitude && $customer->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" title="عرض الموقع على خرائط جوجل" 
                                class="p-1.5 rounded border border-[var(--border-color)] text-[var(--primary)] hover:bg-[var(--primary-soft)] transition-colors">
                                <x-icon name="map" size="14" />
                            </a>
                        @endif
                    </div>

                    <!-- Direct Order & Visit Actions -->
                    <div class="flex items-center gap-1.5">
                        @if($customer->isApproved())
                            <a href="{{ route('portal.visits.create', ['customer_id' => $customer->id]) }}" title="تسجيل زيارة ميدانية" class="erp-btn erp-btn-sm erp-btn-secondary text-[11px] py-1 px-2">
                                <x-icon name="map-pin" size="12" class="text-rose-500" />
                                <span>زيارة</span>
                            </a>
                            <a href="{{ route('portal.orders.create', ['customer_id' => $customer->id]) }}" class="erp-btn erp-btn-sm erp-btn-primary text-[11px] py-1 px-2.5">
                                <x-icon name="plus" size="12" />
                                <span>طلب</span>
                            </a>
                        @elseif($customer->isPending())
                            <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded">
                                بانتظار الاعتماد
                            </span>
                        @else
                            <span class="text-[10px] text-rose-600 bg-rose-50 border border-rose-200 px-2 py-1 rounded">
                                حساب مرفوض
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full erp-card p-12 text-center">
                <x-icon name="users" size="36" class="text-slate-300 mx-auto mb-2" />
                <div class="text-sm font-bold text-[var(--text-main)]">لم يتم العثور على عملاء</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">اضغط على «عميل جديد» لتسجيل عميل جديد من الميدان</div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($customers->hasPages())
        <div class="erp-card p-3 mb-0">
            {{ $customers->links() }}
        </div>
    @endif

</div>
@endsection
