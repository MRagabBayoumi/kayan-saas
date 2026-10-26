@extends('layouts.portal', ['title' => 'تفاصيل الزيارة الميدانية'])

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <!-- Header Card -->
    <div class="erp-card mb-0 p-4 flex items-center justify-between">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="map-pin" size="18" class="text-rose-500" />
                <span>زيارة ميدانية: {{ $visit->customer->name }}</span>
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">
                {{ $visit->check_in_at->format('Y-m-d') }} | الغرض: {{ $visit->purpose_label }}
            </p>
        </div>
        <a href="{{ route('portal.visits.index') }}" class="erp-btn erp-btn-secondary erp-btn-sm text-xs">
            <x-icon name="arrow-right" size="13" />
            <span>رجوع للزيارات</span>
        </a>
    </div>

    @if(session('message'))
        <div class="erp-alert erp-alert-{{ session('status') ?? 'info' }}" role="alert">
            <x-icon name="info" size="18" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Customer Card -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs text-[var(--text-muted)]">العميل المراد مقابلته:</div>
                <div class="text-sm font-bold text-[var(--text-main)]">{{ $visit->customer->name }}</div>
                @if($visit->customer->company_name)
                    <div class="text-xs text-[var(--text-muted)]">{{ $visit->customer->company_name }}</div>
                @endif
            </div>

            <!-- Quick Contacts -->
            <div class="flex items-center gap-1.5">
                @if($visit->customer->phone)
                    <a href="tel:{{ $visit->customer->phone }}" class="p-2 rounded border border-[var(--border-color)] text-slate-700 hover:text-emerald-700 hover:bg-emerald-50" title="اتصال">
                        <x-icon name="phone-call" size="15" />
                    </a>
                @endif
                @if($visit->customer->latitude && $visit->customer->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $visit->customer->latitude }},{{ $visit->customer->longitude }}" target="_blank"
                        class="p-2 rounded border border-[var(--border-color)] text-[var(--primary)] hover:bg-[var(--primary-soft)]" title="موقع العميل">
                        <x-icon name="map" size="15" />
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs border-t border-[var(--border-light)] pt-2.5 font-numeric">
            <div>
                <span class="text-[var(--text-muted)]">وقت الوصول المسجل:</span>
                <span class="font-bold text-[var(--text-main)] mr-1">{{ $visit->check_in_at->format('h:i A') }}</span>
            </div>
            <div>
                <span class="text-[var(--text-muted)]">رصيد العميل الحالي:</span>
                <span class="font-bold mr-1 {{ (float)$visit->customer->current_balance > 0 ? 'text-rose-600' : 'text-slate-700' }}">
                    {{ number_format($visit->customer->current_balance, 2) }} ج.م
                </span>
            </div>
        </div>
    </div>

    @if(! $visit->check_out_at)
        <!-- Case 1: Ongoing Visit - Form to Complete and Check Out -->
        <div class="erp-card mb-0 p-4 sm:p-6 space-y-4 border-2 border-emerald-400">
            <div class="flex items-center justify-between border-b pb-3 border-[var(--border-light)]">
                <div>
                    <h3 class="font-bold text-sm text-[var(--text-main)] flex items-center gap-1.5">
                        <x-icon name="check-circle" size="16" class="text-emerald-600" />
                        <span>إنهاء المقابلة وتوثيق تقرير الزيارة</span>
                    </h3>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">سجل نتيجة الزيارة لرفعها للإدارة وحساب زمن المقابلة</p>
                </div>

                <!-- Live Visit Duration Timer -->
                <div class="text-left font-numeric">
                    <span class="text-[10px] text-[var(--text-muted)] block">مدة المقابلة:</span>
                    <span id="visitTimer" class="text-lg font-black text-emerald-600">00:00:00</span>
                </div>
            </div>

            <form id="completeVisitForm" method="POST" action="{{ route('portal.visits.complete', $visit) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="latitude" id="completeLat">
                <input type="hidden" name="longitude" id="completeLng">

                <!-- Outcome -->
                <div>
                    <label class="erp-form-label font-bold text-xs mb-1.5">
                        نتيجة الزيارة الميدانية <span class="required">*</span>
                    </label>
                    <select name="outcome" id="outcomeSelect" required class="erp-input text-xs sm:text-sm font-bold">
                        <option value="">-- حدد نتيجة المقابلة مع العميل --</option>
                        @foreach($outcomes as $key => $label)
                            <option value="{{ $key }}" {{ old('outcome') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div id="orderNotice" class="hidden p-2 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs mt-1.5 flex items-center gap-1.5">
                        <x-icon name="shopping-cart" size="14" class="shrink-0" />
                        <span>عند اختيار «تم طلب بضاعة»، سيتم توجيهك فوراً لشاشة تحرير طلب البيع للعميل.</span>
                    </div>
                </div>

                <!-- Visit / Store Photo (Optional) -->
                <div class="p-3.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-hover)] space-y-2">
                    <label class="erp-form-label font-bold text-xs flex items-center justify-between mb-0">
                        <span class="flex items-center gap-1.5">
                            <x-icon name="camera" size="14" class="text-[var(--primary)]" />
                            <span>صورة من الزيارة / المحل (اختياري)</span>
                        </span>
                        <span class="text-[10px] text-[var(--text-muted)]">كاميرا الهاتف</span>
                    </label>

                    <label class="flex items-center justify-center gap-2 p-3 border border-dashed border-[var(--border-color)] rounded-lg bg-white dark:bg-gray-900 cursor-pointer hover:border-[var(--primary)] transition-all text-xs font-bold text-[var(--text-secondary)]">
                        <x-icon name="upload" size="14" class="text-[var(--primary)]" />
                        <span id="photoFileName">اضغط لالتقاط أو اختيار صورة</span>
                        <input type="file" name="visit_photo" id="visitPhotoInput" accept="image/*" class="sr-only">
                    </label>
                </div>

                <!-- Notes / Feedback -->
                <div>
                    <label class="erp-form-label font-bold text-xs mb-1">تقرير وملخص الزيارة</label>
                    <textarea name="notes" rows="3" placeholder="اكتب ما تم الاتفاق عليه، موعد المقابلة القادمة، أو أسباب عدم الطلب..."
                        class="erp-textarea text-xs sm:text-sm">{{ old('notes', $visit->notes) }}</textarea>
                </div>

                <!-- Submit Button -->
                <div class="pt-1">
                    <button type="submit" id="completeBtn" class="erp-btn erp-btn-primary w-full py-3.5 text-sm font-bold justify-center shadow-xs">
                        <x-icon name="check" size="16" />
                        <span>إنهاء وتوثيق الزيارة</span>
                    </button>
                </div>
            </form>
        </div>

    @else
        <!-- Case 2: Completed Visit Summary -->
        <div class="erp-card mb-0 p-5 space-y-4">
            <div class="flex items-center justify-between border-b pb-3 border-[var(--border-light)]">
                <div>
                    <span class="text-xs text-[var(--text-muted)]">حالة الزيارة:</span>
                    <div class="text-base font-bold text-emerald-600 flex items-center gap-1.5 mt-0.5">
                        <x-icon name="check-circle" size="18" />
                        <span>مكتملة وموثقة</span>
                    </div>
                </div>

                <!-- Outcome Badge -->
                <span class="erp-badge erp-badge-primary text-xs px-3 py-1 font-bold">
                    {{ $visit->outcome_label }}
                </span>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs font-numeric">
                <div class="p-2.5 rounded bg-[var(--bg-hover)] border border-[var(--border-color)]">
                    <span class="text-[10px] text-[var(--text-muted)] block">وقت الوصول:</span>
                    <span class="font-bold text-[var(--text-main)]">{{ $visit->check_in_at->format('h:i A') }}</span>
                </div>
                <div class="p-2.5 rounded bg-[var(--bg-hover)] border border-[var(--border-color)]">
                    <span class="text-[10px] text-[var(--text-muted)] block">وقت المغادرة:</span>
                    <span class="font-bold text-[var(--text-main)]">{{ $visit->check_out_at->format('h:i A') }}</span>
                </div>
                <div class="p-2.5 rounded bg-[var(--bg-hover)] border border-[var(--border-color)] col-span-2 sm:col-span-1">
                    <span class="text-[10px] text-[var(--text-muted)] block">مدة المقابلة:</span>
                    <span class="font-bold text-emerald-600">{{ $visit->duration_minutes }} دقيقة</span>
                </div>
            </div>

            @if($visit->notes)
                <div class="p-3 rounded-lg bg-[var(--bg-hover)] border border-[var(--border-color)] text-xs text-[var(--text-secondary)] space-y-1">
                    <div class="font-bold text-[var(--text-main)]">تقرير الزيارة:</div>
                    <p class="leading-relaxed">{{ $visit->notes }}</p>
                </div>
            @endif

            @if($visit->visit_image_path)
                <div class="space-y-1.5">
                    <div class="text-xs font-bold text-[var(--text-main)]">صورة توثيق الزيارة:</div>
                    <a href="{{ $visit->image_url }}" target="_blank" class="block max-w-xs rounded-lg overflow-hidden border border-[var(--border-color)] hover:opacity-90">
                        <img src="{{ $visit->image_url }}" alt="توثيق الزيارة" class="w-full h-48 object-cover">
                    </a>
                </div>
            @endif

            <!-- Invoice Link if Order Placed -->
            @if($visit->invoice)
                <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs flex items-center justify-between">
                    <div>
                        <span class="text-emerald-800 font-bold block">تم إنشاء طلب بيع مرتبط بالزيارة:</span>
                        <span class="font-numeric text-emerald-700">فاتورة رقم: {{ $visit->invoice->invoice_number }}</span>
                    </div>
                    <a href="{{ route('portal.orders.show', $visit->invoice) }}" class="erp-btn erp-btn-sm erp-btn-primary text-xs">
                        <span>عرض الفاتورة</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(! $visit->check_out_at)
            // Live Visit Duration Timer
            const checkInTime = new Date("{{ $visit->check_in_at->toIso8601String() }}").getTime();
            const timerEl = document.getElementById('visitTimer');

            function updateTimer() {
                const now = new Date().getTime();
                let diff = Math.floor((now - checkInTime) / 1000);
                if (diff < 0) diff = 0;

                const hrs = String(Math.floor(diff / 3600)).padStart(2, '0');
                const mins = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                const secs = String(diff % 60).padStart(2, '0');

                if (timerEl) timerEl.textContent = `${hrs}:${mins}:${secs}`;
            }
            setInterval(updateTimer, 1000);
            updateTimer();

            // Outcome Change Watcher
            const outcomeSelect = document.getElementById('outcomeSelect');
            const orderNotice = document.getElementById('orderNotice');
            if (outcomeSelect && orderNotice) {
                outcomeSelect.addEventListener('change', function() {
                    if (this.value === 'order_placed') {
                        orderNotice.classList.remove('hidden');
                    } else {
                        orderNotice.classList.add('hidden');
                    }
                });
            }

            // Photo Input Name display
            const photoInput = document.getElementById('visitPhotoInput');
            const photoName = document.getElementById('photoFileName');
            if (photoInput && photoName) {
                photoInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        photoName.textContent = 'تم اختيار: ' + e.target.files[0].name;
                        photoName.classList.add('text-emerald-600');
                    }
                });
            }

            // Capture GPS on complete submit
            const completeForm = document.getElementById('completeVisitForm');
            const completeBtn = document.getElementById('completeBtn');
            const completeLat = document.getElementById('completeLat');
            const completeLng = document.getElementById('completeLng');

            if (completeForm) {
                completeForm.addEventListener('submit', function(e) {
                    if (navigator.geolocation && !completeLat.value) {
                        e.preventDefault();
                        completeBtn.disabled = true;
                        completeBtn.innerHTML = 'جارٍ تسجيل المغادرة والـ GPS...';

                        navigator.geolocation.getCurrentPosition(
                            function(pos) {
                                completeLat.value = pos.coords.latitude;
                                completeLng.value = pos.coords.longitude;
                                completeForm.submit();
                            },
                            function(err) {
                                completeForm.submit();
                            },
                            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                        );
                    }
                });
            }
        @endif
    });
</script>
@endpush
@endsection
