@extends('layouts.portal', ['title' => 'بدء زيارة ميدانية'])

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <!-- Header Card -->
    <div class="erp-card mb-0 p-4 flex items-center justify-between">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="map-pin" size="18" class="text-rose-500" />
                <span>تسجيل الوصول لبدء زيارة ميدانية</span>
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">توثيق الحضور لدى العميل بالـ GPS واحتساب زمن المقابلة</p>
        </div>
        <a href="{{ route('portal.visits.index') }}" class="erp-btn erp-btn-secondary erp-btn-sm text-xs">
            <x-icon name="arrow-right" size="13" />
            <span>رجوع للزيارات</span>
        </a>
    </div>

    <!-- Form -->
    <form id="startVisitForm" method="POST" action="{{ route('portal.visits.store') }}" class="erp-card mb-0 p-4 sm:p-6 space-y-4">
        @csrf

        <!-- Customer Select -->
        <div>
            <label class="erp-form-label font-bold text-xs mb-1">
                العميل المراد زيارته <span class="required">*</span>
            </label>
            <select name="customer_id" id="customerSelect" required class="erp-input text-xs sm:text-sm">
                <option value="">-- اختر العميل من قائمتك المعتمدة --</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ (string)$selectedCustomerId === (string)$c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->phone }}) {{ $c->city ? ' - ' . $c->city : '' }}
                    </option>
                @endforeach
            </select>
            <span class="text-[10px] text-[var(--text-muted)]">يظهر في القائمة العملاء المعتمدين فقط من إدارة النظام.</span>
        </div>

        <!-- Purpose -->
        <div>
            <label class="erp-form-label font-bold text-xs mb-1">
                الغرض من الزيارة <span class="required">*</span>
            </label>
            <select name="purpose" required class="erp-input text-xs sm:text-sm">
                @foreach($purposes as $key => $label)
                    <option value="{{ $key }}" {{ old('purpose', 'order_collection') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- GPS Capture Box -->
        <div class="p-3.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-hover)] space-y-2">
            <div class="flex items-center justify-between">
                <span class="erp-form-label font-bold text-xs flex items-center gap-1.5 mb-0">
                    <x-icon name="navigation" size="14" class="text-[var(--primary)]" />
                    <span>موقع الوصول الجغرافي (GPS)</span>
                </span>
                <span id="visitGpsStatus" class="text-[10px] text-[var(--text-muted)] font-medium">سيتم التقاطه تلقائياً</span>
            </div>

            <button type="button" id="getVisitGpsBtn" class="erp-btn erp-btn-secondary w-full py-2.5 text-xs font-bold justify-center">
                <x-icon name="map-pin" size="14" class="text-rose-500" />
                <span>التقاط موقعي الحالي لدى العميل</span>
            </button>

            <input type="hidden" name="latitude" id="visitLat" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="visitLng" value="{{ old('longitude') }}">
        </div>

        <!-- Notes -->
        <div>
            <label class="erp-form-label font-bold text-xs mb-1">ملاحظات قبل الزيارة (اختياري)</label>
            <textarea name="notes" rows="2" placeholder="الهدف المطلوب تحقيقه أو تفاصيل الموعد..."
                class="erp-textarea text-xs sm:text-sm">{{ old('notes') }}</textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" id="submitVisitBtn" class="erp-btn erp-btn-primary w-full py-3.5 text-sm font-bold justify-center shadow-xs">
                <x-icon name="check-circle" size="16" />
                <span>تسجيل الوصول وبدء الزيارة</span>
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const getGpsBtn = document.getElementById('getVisitGpsBtn');
        const latInput = document.getElementById('visitLat');
        const lngInput = document.getElementById('visitLng');
        const statusText = document.getElementById('visitGpsStatus');
        const form = document.getElementById('startVisitForm');

        function fetchGps(callback) {
            if (!navigator.geolocation) {
                if (callback) callback();
                return;
            }

            statusText.textContent = 'جارٍ تحديد موقع الـ GPS...';
            statusText.className = 'text-[10px] text-[var(--primary)] font-bold';

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    latInput.value = pos.coords.latitude;
                    lngInput.value = pos.coords.longitude;
                    statusText.textContent = 'تم التقاط الإحداثيات بنجاح (' + pos.coords.latitude.toFixed(4) + ')';
                    statusText.className = 'text-[10px] text-emerald-700 font-bold';
                    if (callback) callback();
                },
                function(err) {
                    statusText.textContent = 'تعذر التقاط الموقع بدقة';
                    statusText.className = 'text-[10px] text-rose-600 font-medium';
                    if (callback) callback();
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
            );
        }

        if (getGpsBtn) {
            getGpsBtn.addEventListener('click', function() {
                fetchGps();
            });
        }

        // Auto attempt GPS on load if empty
        if (!latInput.value) {
            fetchGps();
        }
    });
</script>
@endpush
@endsection
