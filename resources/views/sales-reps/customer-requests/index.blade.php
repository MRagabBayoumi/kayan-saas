@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="طلبات اعتماد العملاء الجدد من الميدان" :breadcrumbs="[
        'المبيعات' => route('sales.index'),
        'مناديب المبيعات' => route('sales-reps.index'),
        'طلبات اعتماد العملاء' => '#'
    ]">
        <x-slot:actions>
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للمناديب</span>
            </a>
            <a href="{{ route('sales-reps.attendance.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clock" size="16" />
                <span>سجل الحضور والغياب</span>
            </a>
            <a href="{{ route('sales-reps.visits.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="map-pin" size="16" />
                <span>سجل الزيارات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning font-numeric px-2.5 py-1 text-xs">
                <span>طلبات قيد المراجعة:</span>
                <strong>{{ $pendingCount }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-4">

    <!-- Enterprise Sub-Navigation Tabs -->
    <x-sales-reps-nav-tabs active="customer-requests" :pending-requests-count="$pendingCount" />

    @if(session('message'))
        <div class="erp-alert erp-alert-{{ session('status') ?? 'info' }}" role="alert">
            <x-icon name="info" size="18" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Status Tabs & Search Filters Card -->
    <div class="erp-card mb-0 p-4 sm:p-5 space-y-4">
        <!-- Row 1: Status Filter Pills -->
        <div class="flex items-center justify-between gap-2 flex-wrap border-b border-[var(--border-light)] pb-3">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                <!-- Pending -->
                <a href="{{ route('sales-reps.customer-requests.index', array_filter(['status' => 'pending', 'sales_rep_id' => $currentRep, 'search' => $currentSearch])) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded font-bold text-xs border transition-all shrink-0 {{ $currentStatus === 'pending' ? 'bg-amber-600 text-white border-amber-700 shadow-xs' : 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100' }}">
                    <x-icon name="clock" size="14" />
                    <span>قيد المراجعة</span>
                    <span class="px-1.5 py-0.5 rounded text-[11px] font-numeric font-black {{ $currentStatus === 'pending' ? 'bg-amber-700 text-white' : 'bg-amber-200 text-amber-900' }}">{{ $pendingCount }}</span>
                </a>

                <!-- Approved -->
                <a href="{{ route('sales-reps.customer-requests.index', array_filter(['status' => 'approved', 'sales_rep_id' => $currentRep, 'search' => $currentSearch])) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded font-bold text-xs border transition-all shrink-0 {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs' : 'bg-emerald-50 text-emerald-900 border-emerald-300 hover:bg-emerald-100' }}">
                    <x-icon name="check-circle" size="14" />
                    <span>المعتمدة</span>
                    <span class="px-1.5 py-0.5 rounded text-[11px] font-numeric font-black {{ $currentStatus === 'approved' ? 'bg-emerald-700 text-white' : 'bg-emerald-200 text-emerald-900' }}">{{ $approvedCount }}</span>
                </a>

                <!-- Rejected -->
                <a href="{{ route('sales-reps.customer-requests.index', array_filter(['status' => 'rejected', 'sales_rep_id' => $currentRep, 'search' => $currentSearch])) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded font-bold text-xs border transition-all shrink-0 {{ $currentStatus === 'rejected' ? 'bg-rose-600 text-white border-rose-700 shadow-xs' : 'bg-rose-50 text-rose-900 border-rose-300 hover:bg-rose-100' }}">
                    <x-icon name="x-circle" size="14" />
                    <span>المرفوضة</span>
                    <span class="px-1.5 py-0.5 rounded text-[11px] font-numeric font-black {{ $currentStatus === 'rejected' ? 'bg-rose-700 text-white' : 'bg-rose-200 text-rose-900' }}">{{ $rejectedCount }}</span>
                </a>

                <!-- All -->
                <a href="{{ route('sales-reps.customer-requests.index', array_filter(['status' => 'all', 'sales_rep_id' => $currentRep, 'search' => $currentSearch])) }}"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded font-bold text-xs border transition-all shrink-0 {{ $currentStatus === 'all' ? 'bg-[var(--primary)] text-white border-[var(--primary)] shadow-xs' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)] border-[var(--border-color)] hover:text-[var(--text-main)]' }}">
                    <span>الكل</span>
                    <span class="px-1.5 py-0.5 rounded text-[11px] font-numeric font-black {{ $currentStatus === 'all' ? 'bg-[#5e3c55] text-white' : 'bg-slate-200 text-slate-700' }}">{{ $pendingCount + $approvedCount + $rejectedCount }}</span>
                </a>
            </div>

            <div class="text-xs text-[var(--text-muted)] font-medium">
                إجمالي النتائج: <strong class="font-numeric text-[var(--text-main)]">{{ $customers->total() }}</strong> طلب
            </div>
        </div>

        <!-- Row 2: Search Input and Sales Rep Dropdown -->
        <form method="GET" action="{{ route('sales-reps.customer-requests.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <input type="hidden" name="status" value="{{ $currentStatus }}">

            <!-- Search Input -->
            <div class="sm:col-span-6 relative">
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="بحث باسم العميل، المتجر، رقم الهاتف أو العنوان..."
                    class="erp-input h-10 pr-9 pl-3 text-xs sm:text-sm w-full">
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                    <x-icon name="search" size="16" />
                </div>
            </div>

            <!-- Sales Rep Filter -->
            <div class="sm:col-span-4">
                <select name="sales_rep_id" onchange="this.form.submit()" class="erp-input h-10 text-xs sm:text-sm w-full font-medium">
                    <option value="">كل المناديب المسجلين</option>
                    @foreach($salesReps as $rep)
                        <option value="{{ $rep->id }}" {{ (string)$currentRep === (string)$rep->id ? 'selected' : '' }}>
                            المندوب: {{ $rep->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="erp-btn erp-btn-primary h-10 px-4 text-xs sm:text-sm flex-1 justify-center">
                    <x-icon name="filter" size="14" />
                    <span>تصفية</span>
                </button>
                @if($currentSearch || $currentRep)
                    <a href="{{ route('sales-reps.customer-requests.index', ['status' => $currentStatus]) }}" class="erp-btn erp-btn-secondary h-10 px-3 text-xs sm:text-sm text-rose-600 hover:bg-rose-50" title="إعادة تعيين الفلاتر">
                        <x-icon name="x" size="14" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Requests Table Card -->
    <div class="erp-card mb-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table w-full">
                <thead>
                    <tr class="bg-[var(--bg-hover)] border-b border-[var(--border-color)]">
                        <th class="w-12 text-center py-3.5 px-3 text-xs font-bold text-[var(--text-muted)]">#</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">بيانات العميل والمتجر</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">المندوب المسجل</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">تاريخ الطلب</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">صور المتجر</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">الموقع (GPS)</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">الحالة</th>
                        <th class="py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">الحد والنوع</th>
                        <th class="text-center w-40 py-3.5 px-4 text-xs font-bold text-[var(--text-muted)]">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-light)]">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                            <td class="text-center font-numeric font-bold text-xs text-[var(--text-muted)] py-4 px-3">{{ $customer->id }}</td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-[var(--text-main)] text-sm sm:text-base">{{ $customer->name }}</div>
                                @if($customer->company_name)
                                    <div class="text-xs text-[var(--text-muted)] font-medium">{{ $customer->company_name }}</div>
                                @endif
                                <div class="text-xs text-[var(--text-secondary)] font-numeric flex items-center gap-1.5 mt-1 font-bold">
                                    <x-icon name="phone" size="13" class="text-slate-400" />
                                    <span>{{ $customer->phone }}</span>
                                    @if($customer->phone_alt)
                                        <span class="text-[var(--text-muted)] font-normal">/ {{ $customer->phone_alt }}</span>
                                    @endif
                                </div>
                                @if($customer->city || $customer->address)
                                    <div class="text-xs text-[var(--text-muted)] truncate max-w-sm mt-0.5">
                                        {{ implode(' - ', array_filter([$customer->city, $customer->address])) }}
                                    </div>
                                @endif
                                @if($customer->notes)
                                    <div class="text-xs text-amber-800 bg-amber-50 rounded-md px-2 py-1 mt-1.5 max-w-sm border border-amber-200">
                                        <span class="font-bold">ملاحظة:</span> {{ $customer->notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($customer->salesRep)
                                    <div class="font-bold text-[var(--text-main)] text-sm">{{ $customer->salesRep->name }}</div>
                                    <div class="text-xs text-[var(--text-muted)] font-numeric font-bold">{{ $customer->salesRep->phone }}</div>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 font-numeric text-[var(--text-secondary)] text-xs">
                                <span class="font-bold">{{ $customer->created_at->format('Y-m-d') }}</span>
                                <div class="text-xs text-[var(--text-muted)] mt-0.5">{{ $customer->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="py-4 px-4">
                                @php $photos = $customer->store_image_urls; @endphp
                                @if(count($photos) > 0)
                                    <div class="flex items-center gap-2">
                                        @foreach($photos as $photo)
                                            <a href="{{ $photo }}" target="_blank" title="اضغط لفتح الصورة مكبرة"
                                                class="w-12 h-12 rounded-lg border border-[var(--border-color)] overflow-hidden shrink-0 bg-slate-100 hover:opacity-80 transition-opacity shadow-2xs">
                                                <img src="{{ $photo }}" alt="صورة المتجر" class="w-full h-full object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-[var(--text-muted)]">لا توجد صور</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($customer->latitude && $customer->longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs text-sky-700 hover:text-sky-900 font-bold bg-sky-50 px-3 py-1.5 rounded-lg border border-sky-300 transition-colors shadow-2xs">
                                        <x-icon name="map-pin" size="14" class="text-rose-500" />
                                        <span>عرض على الخريطة ↗</span>
                                    </a>
                                @else
                                    <span class="text-xs text-[var(--text-muted)]">غير محدد</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @if($customer->isApproved())
                                    <span class="erp-badge erp-badge-success text-xs px-2.5 py-1 font-bold">
                                        <x-icon name="check" size="12" />
                                        <span>معتمد</span>
                                    </span>
                                    @if($customer->approver)
                                        <div class="text-xs text-[var(--text-muted)] mt-1">بواسطة: {{ $customer->approver->name }}</div>
                                    @endif
                                @elseif($customer->isPending())
                                    <span class="erp-badge erp-badge-warning text-xs px-2.5 py-1 font-bold animate-pulse">
                                        <x-icon name="clock" size="12" />
                                        <span>قيد المراجعة</span>
                                    </span>
                                @else
                                    <span class="erp-badge erp-badge-danger text-xs px-2.5 py-1 font-bold">
                                        <x-icon name="x" size="12" />
                                        <span>مرفوض</span>
                                    </span>
                                    @if($customer->rejection_reason)
                                        <div class="text-xs text-rose-600 mt-1 max-w-xs truncate" title="{{ $customer->rejection_reason }}">
                                            {{ $customer->rejection_reason }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $tierLabel = match($customer->pricing_tier) {
                                        'distributor' => 'موزع',
                                        'wholesale' => 'جملة',
                                        default => 'قطاعي',
                                    };
                                @endphp
                                <div class="font-bold text-[var(--text-main)] text-xs sm:text-sm">فئة: {{ $tierLabel }}</div>
                                <div class="font-numeric text-xs font-semibold text-[var(--text-muted)] mt-0.5">
                                    الحد: {{ number_format($customer->credit_limit, 2) }} ج.م
                                </div>
                            </td>
                            <td class="text-center py-4 px-4">
                                @if($customer->isPending())
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" onclick="openApproveModal({{ $customer->id }}, '{{ addslashes($customer->name) }}', '{{ $customer->pricing_tier }}', {{ (float)$customer->credit_limit }})"
                                            class="erp-btn erp-btn-sm erp-btn-primary text-xs font-bold py-1.5 px-3" title="اعتماد وتحديد الحد الائتماني">
                                            <x-icon name="check" size="14" />
                                            <span>اعتماد</span>
                                        </button>
                                        <button type="button" onclick="openRejectModal({{ $customer->id }}, '{{ addslashes($customer->name) }}')"
                                            class="erp-btn erp-btn-sm erp-btn-danger text-xs font-bold py-1.5 px-2.5" title="رفض الطلب مع ذكر السبب">
                                            <x-icon name="x" size="14" />
                                            <span>رفض</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" onclick="openApproveModal({{ $customer->id }}, '{{ addslashes($customer->name) }}', '{{ $customer->pricing_tier }}', {{ (float)$customer->credit_limit }})"
                                            class="erp-btn erp-btn-secondary erp-btn-sm text-xs font-bold px-2.5 py-1.5 text-slate-700 hover:text-[var(--primary)]" title="تعديل الفئة والحد الائتماني">
                                            <x-icon name="edit" size="13" />
                                            <span>تعديل</span>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-12 text-center text-[var(--text-muted)]">
                                <x-icon name="inbox" size="40" class="text-slate-300 mx-auto mb-3" />
                                <div class="font-bold text-base text-[var(--text-main)]">لا توجد طلبات عملاء مطابقة للبحث الحالي</div>
                                <p class="text-xs text-[var(--text-muted)] mt-1">تأكد من الفلاتر المحددة أو قم بإلغاء التصفية لعرض جميع الطلبات</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-3 border-t border-[var(--border-light)]">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="erp-card w-full max-w-md p-5 bg-white dark:bg-gray-800 shadow-xl rounded-lg space-y-4">
        <div class="flex items-center justify-between border-b pb-3 border-[var(--border-light)]">
            <h3 class="font-bold text-sm text-[var(--text-main)] flex items-center gap-1.5">
                <x-icon name="check-circle" size="16" class="text-emerald-600" />
                <span>اعتماد وتفعيل حساب العميل</span>
            </h3>
            <button type="button" onclick="closeApproveModal()" class="text-slate-400 hover:text-slate-600">
                <x-icon name="x" size="16" />
            </button>
        </div>

        <form id="approveForm" method="POST" action="" class="space-y-3">
            @csrf
            <div>
                <span class="text-xs text-[var(--text-muted)]">العميل المراد اعتماده:</span>
                <div id="approveCustomerName" class="font-bold text-sm text-[var(--text-main)] mt-0.5"></div>
            </div>

            <!-- Pricing Tier -->
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">فئة التسعير المعتمدة <span class="required">*</span></label>
                <select name="pricing_tier" id="approvePricingTier" required class="erp-input text-xs sm:text-sm">
                    <option value="wholesale">سعر الجملة (الافتراضي للمناديب)</option>
                    <option value="distributor">سعر الموزع (كبار الموزعين)</option>
                    <option value="retail">سعر التجزئة (قطاعي)</option>
                </select>
                <span class="text-[10px] text-[var(--text-muted)]">المندوب مسجل بسعر الجملة، ويمكنك تغييره هنا لموزع أو قطاعي.</span>
            </div>

            <!-- Credit Limit -->
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">الحد الائتماني للعميل (ج.م) <span class="required">*</span></label>
                <input type="number" step="100" min="0" name="credit_limit" id="approveCreditLimit" value="0" required class="erp-input text-xs sm:text-sm font-numeric">
                <span class="text-[10px] text-[var(--text-muted)]">أدخل أقصى رصيد آجل مسموح به لهذا العميل (0 للتعامل النقدي فقط).</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--border-light)]">
                <button type="button" onclick="closeApproveModal()" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                <button type="submit" class="erp-btn erp-btn-primary text-xs">
                    <x-icon name="check" size="14" />
                    <span>تأكيد الاعتماد وتفعيل الحساب</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="erp-card w-full max-w-md p-5 bg-white dark:bg-gray-800 shadow-xl rounded-lg space-y-4">
        <div class="flex items-center justify-between border-b pb-3 border-[var(--border-light)]">
            <h3 class="font-bold text-sm text-rose-600 flex items-center gap-1.5">
                <x-icon name="x-circle" size="16" class="text-rose-600" />
                <span>رفض اعتماد طلب العميل</span>
            </h3>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600">
                <x-icon name="x" size="16" />
            </button>
        </div>

        <form id="rejectForm" method="POST" action="" class="space-y-3">
            @csrf
            <div>
                <span class="text-xs text-[var(--text-muted)]">العميل:</span>
                <div id="rejectCustomerName" class="font-bold text-sm text-[var(--text-main)] mt-0.5"></div>
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1 text-rose-700">سبب الرفض <span class="required">*</span></label>
                <textarea name="rejection_reason" rows="3" required placeholder="اكتب سبب الرفض هنا ليظهر للمندوب (مثل: الصور غير واضحة، المكان مغلق، الموقع غير مطابق...)"
                    class="erp-textarea text-xs sm:text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--border-light)]">
                <button type="button" onclick="closeRejectModal()" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                <button type="submit" class="erp-btn erp-btn-danger text-xs">
                    <x-icon name="x" size="14" />
                    <span>تأكيد الرفض</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openApproveModal(customerId, name, pricingTier, creditLimit) {
        const modal = document.getElementById('approveModal');
        const form = document.getElementById('approveForm');
        const nameEl = document.getElementById('approveCustomerName');
        const tierSelect = document.getElementById('approvePricingTier');
        const creditInput = document.getElementById('approveCreditLimit');

        form.action = `/sales-reps/customer-requests/${customerId}/approve`;
        nameEl.textContent = name;
        tierSelect.value = pricingTier || 'wholesale';
        creditInput.value = creditLimit || 0;

        modal.classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function openRejectModal(customerId, name) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        const nameEl = document.getElementById('rejectCustomerName');

        form.action = `/sales-reps/customer-requests/${customerId}/reject`;
        nameEl.textContent = name;

        modal.classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
