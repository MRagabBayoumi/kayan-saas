@extends('layouts.portal', ['title' => 'الزيارات الميدانية'])

@section('content')
<div class="space-y-4">

    <!-- Header Card -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-[var(--border-light)]">
            <div>
                <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                    <x-icon name="map-pin" size="18" class="text-rose-500" />
                    <span>سجل الزيارات الميدانية للعملاء</span>
                </h1>
                <div class="text-xs text-[var(--text-muted)] mt-0.5 font-numeric">
                    زيارات اليوم: <strong>{{ $todayVisitsCount }}</strong> | تم استلام طلبات: <strong class="text-emerald-600">{{ $todayOrdersCount }}</strong>
                </div>
            </div>

            <a href="{{ route('portal.visits.create') }}" class="erp-btn erp-btn-primary text-xs">
                <x-icon name="plus-circle" size="14" />
                <span>بدء زيارة ميدانية</span>
            </a>
        </div>
    </div>

    @if(session('message'))
        <div class="erp-alert erp-alert-{{ session('status') ?? 'info' }}" role="alert">
            <x-icon name="info" size="18" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Active Ongoing Visit Alert Card (if any) -->
    @if($activeVisit)
        <div class="erp-card mb-0 p-4 border-2 border-amber-400 bg-amber-50/70 dark:bg-amber-950/20 space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                    <span class="font-bold text-xs sm:text-sm text-amber-900 dark:text-amber-200">
                        زيارة جارية حالياً لدى: {{ $activeVisit->customer->name }}
                    </span>
                </div>
                <span class="erp-badge erp-badge-warning text-[10px]">قيد التنفيذ</span>
            </div>

            <div class="text-xs text-amber-800 dark:text-amber-300 font-numeric flex items-center gap-3">
                <span>وقت الوصول: {{ $activeVisit->check_in_at->format('h:i A') }}</span>
                <span>الغرض: {{ $activeVisit->purpose_label }}</span>
            </div>

            <div class="pt-1 flex items-center gap-2">
                <a href="{{ route('portal.visits.show', $activeVisit) }}" class="erp-btn erp-btn-warning w-full py-2 text-xs font-bold justify-center">
                    <x-icon name="check-circle" size="14" />
                    <span>متابعة وإنهاء الزيارة وتسجيل النتيجة</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Visits List -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        @forelse($visits as $visit)
            <div class="erp-card mb-0 p-4 hover:border-[var(--primary)] hover:shadow-xs transition-all flex flex-col justify-between">
                <div>
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-bold text-[var(--text-main)] truncate">{{ $visit->customer->name }}</h2>
                            <div class="text-xs text-[var(--text-muted)] font-numeric">{{ $visit->customer->phone }}</div>
                        </div>

                        <!-- Outcome / Status Badge -->
                        @if(! $visit->check_out_at)
                            <span class="erp-badge erp-badge-warning text-[10px]">جارية</span>
                        @elseif($visit->outcome === 'order_placed')
                            <span class="erp-badge erp-badge-success text-[10px]">تم إنشاء طلب</span>
                        @elseif($visit->outcome === 'payment_collected')
                            <span class="erp-badge erp-badge-primary text-[10px]">تم التحصيل</span>
                        @else
                            <span class="erp-badge erp-badge-neutral text-[10px]">{{ $visit->outcome_label }}</span>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="space-y-1 text-xs text-[var(--text-muted)] my-2">
                        <div class="flex items-center justify-between">
                            <span>الغرض:</span>
                            <span class="font-medium text-[var(--text-secondary)]">{{ $visit->purpose_label }}</span>
                        </div>

                        <div class="flex items-center justify-between font-numeric">
                            <span>وقت الوصول:</span>
                            <span class="text-[var(--text-secondary)]">{{ $visit->check_in_at->format('Y-m-d h:i A') }}</span>
                        </div>

                        @if($visit->duration_minutes)
                            <div class="flex items-center justify-between font-numeric">
                                <span>مدة الزيارة:</span>
                                <span class="font-bold text-emerald-700">{{ $visit->duration_minutes }} دقيقة</span>
                            </div>
                        @endif

                        @if($visit->notes)
                            <div class="p-1.5 rounded bg-[var(--bg-hover)] text-[11px] text-[var(--text-secondary)] mt-1 border border-[var(--border-color)] line-clamp-2">
                                {{ $visit->notes }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between pt-2.5 border-t border-[var(--border-light)] gap-1 mt-2">
                    <div class="flex items-center gap-1">
                        @if($visit->check_in_latitude && $visit->check_in_longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $visit->check_in_latitude }},{{ $visit->check_in_longitude }}" target="_blank"
                                class="p-1.5 rounded border border-[var(--border-color)] text-[var(--primary)] hover:bg-[var(--primary-soft)]" title="موقع الزيارة بالـ GPS">
                                <x-icon name="map-pin" size="13" />
                            </a>
                        @endif

                        @if($visit->visit_image_path)
                            <a href="{{ $visit->image_url }}" target="_blank"
                                class="p-1.5 rounded border border-[var(--border-color)] text-slate-600 hover:text-[var(--primary)]" title="صورة الزيارة">
                                <x-icon name="image" size="13" />
                            </a>
                        @endif
                    </div>

                    <a href="{{ route('portal.visits.show', $visit) }}" class="erp-btn erp-btn-sm erp-btn-secondary text-[11px] py-1 px-2.5">
                        <span>{{ ! $visit->check_out_at ? 'استكمال وإنهاء' : 'عرض التفاصيل' }}</span>
                        <x-icon name="chevron-left" size="12" />
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full erp-card p-12 text-center">
                <x-icon name="map-pin" size="36" class="text-slate-300 mx-auto mb-2" />
                <div class="text-sm font-bold text-[var(--text-main)]">لم تسجل أي زيارات بعد</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">اضغط على «بدء زيارة ميدانية» لتوثيق زيارتك الحالية للعميل</div>
            </div>
        @endforelse
    </div>

    @if($visits->hasPages())
        <div class="erp-card p-3 mb-0">
            {{ $visits->links() }}
        </div>
    @endif

</div>
@endsection
