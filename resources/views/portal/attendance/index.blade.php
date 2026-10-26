@extends('layouts.portal', ['title' => 'الحضور والانصراف الميداني'])

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <!-- Header Card -->
    <div class="erp-card mb-0 p-4 flex items-center justify-between">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="clock" size="18" class="text-[var(--primary)]" />
                <span>حضور وانصراف المندوب الميداني (GPS)</span>
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">تسجيل دوام العمل الميداني وحساب ساعات العمل بدقة</p>
        </div>
        <div class="text-left font-numeric">
            <div class="text-xs font-bold text-[var(--text-main)]">{{ now()->translatedFormat('l d F Y') }}</div>
            <div id="liveClock" class="text-xs text-[var(--text-muted)]">--:--:--</div>
        </div>
    </div>

    @if(session('message'))
        <div class="erp-alert erp-alert-{{ session('status') ?? 'info' }}" role="alert">
            <x-icon name="info" size="18" />
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Attendance Status & Actions Card -->
    <div class="erp-card mb-0 p-5 sm:p-6 text-center space-y-5">
        @if(! $todayAttendance)
            <!-- Case 1: Not checked in yet -->
            <div class="space-y-3">
                <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 mx-auto flex items-center justify-center">
                    <x-icon name="log-in" size="32" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-[var(--text-main)]">لم تسجل حضورك الميداني اليوم</h2>
                    <p class="text-xs text-[var(--text-muted)] mt-1">اضغط على الزر أدناه لالتقاط موقعك الجغرافي وبدء احتساب ساعات العمل الميدانية</p>
                </div>

                <div id="gpsStatusBox" class="p-2.5 rounded bg-[var(--bg-hover)] text-xs text-[var(--text-muted)] max-w-sm mx-auto">
                    <span id="gpsStatusText">سيتم التقاط موقعك تلقائياً عند الضغط على الزر</span>
                </div>

                <form id="checkInForm" method="POST" action="{{ route('portal.attendance.check-in') }}" class="pt-2">
                    @csrf
                    <input type="hidden" name="latitude" id="checkInLat">
                    <input type="hidden" name="longitude" id="checkInLng">
                    <input type="hidden" name="address" id="checkInAddress">

                    <button type="button" id="checkInBtn" class="erp-btn erp-btn-primary py-3.5 px-8 text-sm font-bold justify-center mx-auto shadow-md hover:scale-102 transition-transform">
                        <x-icon name="map-pin" size="18" />
                        <span>تسجيل الحضور وبدء يوم العمل</span>
                    </button>
                </form>
            </div>

        @elseif($todayAttendance->status === 'present' && ! $todayAttendance->check_out_time)
            <!-- Case 2: Currently Checked In (Working) -->
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>أنت في وضع العمل الميداني الآن</span>
                </div>

                <!-- Timer Display -->
                <div>
                    <div class="text-xs text-[var(--text-muted)] mb-1">مدة العمل المنقضية اليوم</div>
                    <div id="workingTimer" class="text-3xl sm:text-4xl font-black font-numeric text-emerald-600 tracking-wider">
                        00:00:00
                    </div>
                </div>

                <div class="p-3.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-hover)] max-w-md mx-auto grid grid-cols-2 gap-3 text-xs text-right">
                    <div>
                        <span class="text-[var(--text-muted)] block text-[11px]">وقت الحضور:</span>
                        <span class="font-bold text-[var(--text-main)] font-numeric">{{ $todayAttendance->check_in_time->format('h:i A') }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block text-[11px]">موقع تسجيل الحضور:</span>
                        @if($todayAttendance->check_in_latitude && $todayAttendance->check_in_longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $todayAttendance->check_in_latitude }},{{ $todayAttendance->check_in_longitude }}" target="_blank"
                                class="text-sky-600 hover:underline font-bold inline-flex items-center gap-1">
                                <x-icon name="map-pin" size="12" class="text-rose-500" />
                                <span>عرض على الخريطة</span>
                            </a>
                        @else
                            <span class="text-[var(--text-muted)]">غير متوفر</span>
                        @endif
                    </div>
                </div>

                <div id="checkOutGpsBox" class="p-2 rounded bg-[var(--bg-hover)] text-xs text-[var(--text-muted)] max-w-sm mx-auto">
                    <span id="checkOutGpsText">جاهز لتسجيل انصرافك والتقاط موقع المغادرة</span>
                </div>

                <!-- Check Out Form -->
                <form id="checkOutForm" method="POST" action="{{ route('portal.attendance.check-out') }}" class="pt-2">
                    @csrf
                    <input type="hidden" name="latitude" id="checkOutLat">
                    <input type="hidden" name="longitude" id="checkOutLng">
                    <input type="hidden" name="address" id="checkOutAddress">

                    <button type="button" id="checkOutBtn" class="erp-btn erp-btn-danger py-3 px-8 text-xs sm:text-sm font-bold justify-center mx-auto shadow-sm">
                        <x-icon name="log-out" size="16" />
                        <span>تسجيل الانصراف وإنهاء يوم العمل</span>
                    </button>
                </form>
            </div>

        @else
            <!-- Case 3: Checked Out (Finished) -->
            <div class="space-y-3">
                <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-950/40 text-blue-600 mx-auto flex items-center justify-center">
                    <x-icon name="check-circle" size="32" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-[var(--text-main)]">تم إنهاء يوم العمل بنجاح</h2>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">شكراً لجهودك اليوم!</p>
                </div>

                <div class="p-4 rounded-lg border border-blue-200 bg-blue-50/60 dark:border-blue-900/40 dark:bg-blue-950/20 max-w-md mx-auto grid grid-cols-3 gap-2 text-center text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block text-[10px]">وقت الحضور</span>
                        <span class="font-bold text-[var(--text-main)] font-numeric">{{ $todayAttendance->check_in_time->format('h:i A') }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block text-[10px]">وقت الانصراف</span>
                        <span class="font-bold text-[var(--text-main)] font-numeric">{{ $todayAttendance->check_out_time ? $todayAttendance->check_out_time->format('h:i A') : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block text-[10px]">إجمالي الساعات</span>
                        <span class="font-bold text-emerald-700 dark:text-emerald-400 font-numeric">{{ $todayAttendance->formatted_work_duration }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Attendance History List -->
    <div class="erp-card mb-0 p-4 space-y-3">
        <h3 class="text-xs sm:text-sm font-bold text-[var(--text-main)] flex items-center gap-1.5 border-b border-[var(--border-light)] pb-2.5">
            <x-icon name="calendar" size="15" class="text-[var(--primary)]" />
            <span>سجل الحضور والانصراف خلال الأيام السابقة</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="erp-table w-full text-xs">
                <thead>
                    <tr>
                        <th>اليوم والتاريخ</th>
                        <th>وقت الحضور</th>
                        <th>موقع الحضور</th>
                        <th>وقت الانصراف</th>
                        <th>مدة العمل</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-light)]">
                    @forelse($history as $item)
                        <tr>
                            <td class="font-numeric font-bold text-[var(--text-main)]">
                                {{ $item->date->format('Y-m-d') }}
                                <div class="text-[10px] text-[var(--text-muted)] font-normal">{{ $item->date->translatedFormat('l') }}</div>
                            </td>
                            <td class="font-numeric text-[var(--text-secondary)]">
                                {{ $item->check_in_time ? $item->check_in_time->format('h:i A') : '-' }}
                            </td>
                            <td>
                                @if($item->check_in_latitude && $item->check_in_longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $item->check_in_latitude }},{{ $item->check_in_longitude }}" target="_blank"
                                        class="text-sky-600 hover:text-sky-800 text-[11px] inline-flex items-center gap-1">
                                        <x-icon name="map-pin" size="12" class="text-rose-500" />
                                        <span>الخريطة</span>
                                    </a>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="font-numeric text-[var(--text-secondary)]">
                                {{ $item->check_out_time ? $item->check_out_time->format('h:i A') : '-' }}
                            </td>
                            <td class="font-numeric font-bold text-emerald-700">
                                {{ $item->formatted_work_duration }}
                            </td>
                            <td>
                                @if($item->status === 'checked_out')
                                    <span class="erp-badge erp-badge-success text-[10px]">مكتمل</span>
                                @elseif($item->status === 'present')
                                    <span class="erp-badge erp-badge-warning text-[10px]">قيد العمل</span>
                                @else
                                    <span class="erp-badge erp-badge-neutral text-[10px]">{{ $item->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-[var(--text-muted)]">
                                لا يوجد سجل حضور سابق مسجل لك حتى الآن
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
            <div class="pt-2">
                {{ $history->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
(function() {
    function initAttendanceScreen() {
        const liveClockEl = document.getElementById('liveClock');
        function updateClock() {
            const now = new Date();
            if (liveClockEl) {
                liveClockEl.textContent = now.toLocaleTimeString('ar-EG');
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Check In Handling with STRICT Mandatory GPS
        const checkInBtn = document.getElementById('checkInBtn');
        const checkInForm = document.getElementById('checkInForm');
        const checkInLat = document.getElementById('checkInLat');
        const checkInLng = document.getElementById('checkInLng');
        const gpsStatusText = document.getElementById('gpsStatusText');
        const gpsStatusBox = document.getElementById('gpsStatusBox');

        let currentCoordinates = null;

        function acquireGps(onSuccess, onError) {
            if (!navigator.geolocation) {
                if (onError) onError('عذراً، متصفح هاتفك لا يدعم خدمة تحديد الموقع GPS.');
                return;
            }

            if (gpsStatusText) {
                gpsStatusText.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> جارٍ الاتصال بالأقمار الصناعية لتحديد موقعك الميداني بدقة...';
                gpsStatusText.className = 'text-amber-600 font-bold';
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentCoordinates = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: Math.round(position.coords.accuracy || 0)
                    };

                    if (checkInLat) checkInLat.value = currentCoordinates.lat;
                    if (checkInLng) checkInLng.value = currentCoordinates.lng;

                    if (gpsStatusText) {
                        gpsStatusText.innerHTML = `
                            <div class="flex items-center justify-center gap-2 text-emerald-700 font-bold">
                                <span>✅ تم رصد موقعك الجغرافي الميداني بنجاح:</span>
                                <span class="font-numeric dir-ltr text-xs">(${currentCoordinates.lat.toFixed(5)}, ${currentCoordinates.lng.toFixed(5)})</span>
                                <span class="text-[11px] font-normal text-emerald-600">(دقة: ±${currentCoordinates.accuracy}م)</span>
                            </div>
                        `;
                        gpsStatusText.className = 'text-emerald-700 font-bold';
                    }

                    if (gpsStatusBox) {
                        gpsStatusBox.className = 'p-3 rounded-lg bg-emerald-50 border border-emerald-300 text-xs max-w-md mx-auto';
                    }

                    if (onSuccess) onSuccess(currentCoordinates);
                },
                function(error) {
                    currentCoordinates = null;
                    let errorMsg = 'تعذر التقاط الموقع بدقة.';
                    if (error.code === 1) {
                        errorMsg = 'تم رفض إذن الوصول للموقع (GPS). يجب السماح بالوصول لموقعك في إعدادات المتصفح/الهاتف لتتمكن من تسجيل الحضور.';
                    } else if (error.code === 2) {
                        errorMsg = 'موقع الـ GPS غير متاح حالياً. يرجى التأكد من تشغيل خاصية الموقع في الهاتف.';
                    } else if (error.code === 3) {
                        errorMsg = 'استغرق تحديد الموقع وقتاً طويلاً. يرجى الخروج لمكان مفتوح والمحاولة مجدداً.';
                    }

                    if (gpsStatusText) {
                        gpsStatusText.innerHTML = `<span class="text-rose-600 font-bold">⚠️ ${errorMsg}</span>`;
                        gpsStatusText.className = 'text-rose-600 font-bold';
                    }

                    if (gpsStatusBox) {
                        gpsStatusBox.className = 'p-3 rounded-lg bg-rose-50 border border-rose-300 text-xs max-w-md mx-auto';
                    }

                    if (onError) onError(errorMsg);
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        }

        // Auto warm-up GPS immediately on page load
        if (checkInBtn) {
            acquireGps();

            checkInBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (currentCoordinates && checkInLat.value && checkInLng.value) {
                    checkInBtn.disabled = true;
                    checkInBtn.innerHTML = '<span>جارٍ حفظ الحضور...</span>';
                    checkInForm.submit();
                    return;
                }

                // If not yet acquired, force acquire and then submit
                checkInBtn.disabled = true;
                acquireGps(
                    function(coords) {
                        checkInBtn.innerHTML = '<span>جارٍ حفظ الحضور...</span>';
                        checkInForm.submit();
                    },
                    function(err) {
                        checkInBtn.disabled = false;
                        alert('لا يمكن تسجيل الحضور بدون تفعيل موقع الـ GPS الميداني: ' + err);
                    }
                );
            });
        }

        // Live Working Timer and Check-out
        @if($todayAttendance && $todayAttendance->status === 'present' && ! $todayAttendance->check_out_time)
            const checkInTimestamp = new Date("{{ $todayAttendance->check_in_time->toIso8601String() }}").getTime();
            const timerEl = document.getElementById('workingTimer');

            function updateWorkTimer() {
                const now = new Date().getTime();
                let diffSec = Math.floor((now - checkInTimestamp) / 1000);
                if (diffSec < 0) diffSec = 0;

                const hrs = String(Math.floor(diffSec / 3600)).padStart(2, '0');
                const mins = String(Math.floor((diffSec % 3600) / 60)).padStart(2, '0');
                const secs = String(diffSec % 60).padStart(2, '0');

                if (timerEl) {
                    timerEl.textContent = `${hrs}:${mins}:${secs}`;
                }
            }
            setInterval(updateWorkTimer, 1000);
            updateWorkTimer();

            // Check Out with Mandatory GPS
            const checkOutBtn = document.getElementById('checkOutBtn');
            const checkOutForm = document.getElementById('checkOutForm');
            const checkOutLat = document.getElementById('checkOutLat');
            const checkOutLng = document.getElementById('checkOutLng');
            const checkOutGpsText = document.getElementById('checkOutGpsText');

            if (checkOutBtn) {
                checkOutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('هل أنت متأكد من رغبتك في تسجيل الانصراف وإنهاء يوم العمل؟ سيتم توثيق موقع مغادرتك عبر GPS.')) {
                        return;
                    }

                    checkOutBtn.disabled = true;
                    if (checkOutGpsText) {
                        checkOutGpsText.innerHTML = '<span class="text-amber-600 font-bold">جارٍ توثيق موقع الانصراف عبر الأقمار الصناعية (GPS)...</span>';
                    }

                    if (!navigator.geolocation) {
                        alert('خدمة الـ GPS غير مدعومة بالمتصفح.');
                        checkOutBtn.disabled = false;
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            checkOutLat.value = position.coords.latitude;
                            checkOutLng.value = position.coords.longitude;
                            checkOutBtn.innerHTML = '<span>جارٍ إنهاء الوردية...</span>';
                            checkOutForm.submit();
                        },
                        function(error) {
                            checkOutBtn.disabled = false;
                            alert('يجب تشغيل الموقع (GPS) وتأكيد الصلاحية لتسجيل الانصراف بنجاح.');
                            if (checkOutGpsText) {
                                checkOutGpsText.innerHTML = '<span class="text-rose-600 font-bold">تعذر التقاط موقع الانصراف. يرجى تفعيل الموقع والمحاولة مجدداً.</span>';
                            }
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                    );
                });
            }
        @endif
    }

    document.addEventListener('turbo:load', initAttendanceScreen);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAttendanceScreen);
    } else {
        initAttendanceScreen();
    }
})();
</script>
@endpush
@endsection
