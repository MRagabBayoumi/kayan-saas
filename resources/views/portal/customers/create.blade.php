@extends('layouts.portal', ['title' => 'تسجيل عميل جديد'])

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <!-- Header Card (Kayan ERP Odoo Card) -->
    <div class="erp-card mb-0 p-4 flex items-center justify-between">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="user-plus" size="18" class="text-[var(--primary)]" />
                <span>تسجيل عميل جديد من الميدان</span>
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">سيتم ربط العميل بحسابك الميداني لاحتساب عمولاتك تلقائياً</p>
        </div>
        <a href="{{ route('portal.customers.index') }}" class="erp-btn erp-btn-secondary erp-btn-sm text-xs">
            <x-icon name="arrow-right" size="13" />
            <span>رجوع للعملاء</span>
        </a>
    </div>

    <!-- Registration Form -->
    <form method="POST" action="{{ route('portal.customers.store') }}" enctype="multipart/form-data" class="erp-card mb-0 p-4 sm:p-6 space-y-4">
        @csrf

        <!-- Name & Company -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">
                    اسم العميل / المحل <span class="required">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: مؤسسة النور للتجارة أو الأستاذ محمد علي"
                    class="erp-input text-xs sm:text-sm">
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">الاسم التجاري / الشركة (اختياري)</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="اسم المعرض أو المتجر"
                    class="erp-input text-xs sm:text-sm">
            </div>
        </div>

        <!-- Phone & Phone Alt -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">
                    رقم الهاتف الأساسي <span class="required">*</span>
                </label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="مثال: 01012345678"
                    class="erp-input text-xs sm:text-sm font-numeric">
            </div>
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">هاتف إضافي / واتساب</label>
                <input type="tel" name="phone_alt" value="{{ old('phone_alt') }}" placeholder="مثال: 01223456789"
                    class="erp-input text-xs sm:text-sm font-numeric">
            </div>
        </div>

        <!-- Pricing Tier & Manager Approval Notice -->
        <div class="p-3.5 rounded-lg border border-blue-200 bg-blue-50/80 text-xs text-blue-950 space-y-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 font-bold text-blue-900">
                    <x-icon name="tag" size="14" class="text-blue-700 shrink-0" />
                    <span>فئة التسعير التلقائية:</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white shadow-xs shrink-0">سعر الجملة</span>
            </div>
            <p class="text-[11px] text-blue-800/90 leading-relaxed">يتم إرسال الطلب لإدارة النظام لاعتماده وتحديد الحد الائتماني أو تعديل فئة التسعير إن رغب المدير في ذلك.</p>
        </div>

        <!-- City & Address -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">المدينة / المنطقة</label>
                <input type="text" name="city" value="{{ old('city') }}" placeholder="مثال: القاهرة / الجيزة / طنطا"
                    class="erp-input text-xs sm:text-sm">
            </div>
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">العنوان التفصيلي / معالم مميزة</label>
                <input type="text" name="address" value="{{ old('address') }}" placeholder="الشارع، رقم المبنى، علامة مميزة"
                    class="erp-input text-xs sm:text-sm">
            </div>
        </div>

        <!-- Store Images Upload (Camera & Gallery with Instant Multi-Photo Preview) -->
        <div class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="font-bold text-xs flex items-center gap-1.5 text-slate-900 dark:text-white">
                    <x-icon name="camera" size="16" class="text-[#714B67] dark:text-purple-300" strokeWidth="2.2" />
                    <span>صور مكان / واجهة المحل للمراجعة</span>
                </span>
                <span id="photoCountBadge" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-numeric">0 من 5 صور</span>
            </div>

            <!-- Two Big Action Buttons for Field Reps: Camera & Gallery -->
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" id="triggerCameraBtn" class="py-3 px-3 rounded-xl bg-[#714B67] hover:bg-[#5b3b53] active:scale-98 text-white text-xs font-bold shadow-sm shadow-[#714B67]/25 flex items-center justify-center gap-2 transition-all">
                    <x-icon name="camera" size="18" class="text-white" strokeWidth="2.4" />
                    <span>التقاط بالكاميرا</span>
                </button>

                <button type="button" id="triggerGalleryBtn" class="py-3 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 active:scale-98 text-slate-800 dark:bg-slate-800 dark:text-slate-100 text-xs font-bold border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-center gap-2 transition-all">
                    <x-icon name="upload" size="18" strokeWidth="2.3" />
                    <span>اختيار من المعرض</span>
                </button>
            </div>

            <!-- Hidden Inputs -->
            <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">
            <input type="file" id="galleryInput" accept="image/*" multiple class="hidden">
            <input type="file" name="store_images[]" id="storeImagesInput" multiple class="hidden">

            <!-- Instant Multi-Photo Preview Grid -->
            <div id="imagePreviewContainer" class="hidden grid grid-cols-3 sm:grid-cols-5 gap-2.5 pt-1"></div>

            <div id="uploadEmptyHint" class="p-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-slate-400 text-xs">
                التقط صورة واجهة المحل أو لافتة الاسم أو موقع النشاط لتوثيق اعتماد العميل.
            </div>
        </div>

        <!-- Geolocation GPS (Optional & Resilient) -->
        <div class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="font-bold text-xs flex items-center gap-1.5 text-slate-900 dark:text-white">
                    <x-icon name="map-pin" size="16" class="text-rose-500" strokeWidth="2.2" />
                    <span>الموقع الجغرافي للمحل (GPS)</span>
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">اختياري</span>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                التقاط إحداثيات المحل يسهل وصولك للمحل وتوثيق الزيارات الميدانية على الخريطة.
            </p>

            <!-- GPS Status Banner -->
            <div id="gpsStatusBox" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300">
                <span id="gpsStatusText">الموقع الجغرافي غير محدد حالياً.</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <button type="button" id="gpsBtn" class="flex-1 py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-98 text-white text-xs font-bold shadow-xs flex items-center justify-center gap-2 transition-all">
                    <x-icon name="navigation" size="15" class="text-white" strokeWidth="2.3" />
                    <span id="gpsBtnText">التقاط موقع المحل الحالي (GPS)</span>
                </button>

                @if(!empty($todayAttendance?->latitude) && !empty($todayAttendance?->longitude))
                    <button type="button" id="useAttendanceGpsBtn" 
                            data-lat="{{ $todayAttendance->latitude }}" 
                            data-lng="{{ $todayAttendance->longitude }}" 
                            class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs flex items-center justify-center gap-1.5 transition-all shrink-0">
                        <x-icon name="check-circle" size="14" class="text-white" strokeWidth="2.4" />
                        <span>استخدام موقع دوام اليوم</span>
                    </button>
                @endif
            </div>

            <!-- Permission Denied Help Tip (Collapsible / Dynamic) -->
            <div id="gpsHelpTip" class="hidden p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-900 dark:text-amber-200 space-y-1">
                <div class="font-bold flex items-center gap-1">
                    <x-icon name="info" size="13" class="text-amber-600" />
                    <span>لتفعيل الـ GPS في متصفح كروم على الموبايل:</span>
                </div>
                <p class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                    اضغط على أيقونة الإعدادات أو القفل بجوار رابط الموقع في شريط العنوان، ثم اضغط على «أذونات الموقع (Location)» واختر «سماح».
                </p>
                <div class="text-[11px] text-amber-900 dark:text-amber-200 font-semibold pt-1">
                    ⚠️ يمكنك أيضاً المتابعة وإرسال العميل بدون GPS بشكل طبيعي.
                </div>
            </div>

            <input type="hidden" name="latitude" id="latitudeInput" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitudeInput" value="{{ old('longitude') }}">
        </div>

        <div>
            <label class="erp-form-label font-bold text-xs mb-1">ملاحظات إضافية عن العميل والمكان</label>
            <textarea name="notes" rows="2" placeholder="مواعيد التواجد، تفضيلات البضاعة، طريقة السداد، ملاحظات المندوب..."
                class="erp-textarea text-xs sm:text-sm">{{ old('notes') }}</textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-[#714B67] hover:bg-[#5b3b53] active:scale-98 text-white text-sm font-black justify-center shadow-md shadow-[#714B67]/30 flex items-center gap-2 transition-all">
                <x-icon name="send" size="18" strokeWidth="2.4" class="text-white" />
                <span>إرسال طلب تسجيل العميل للاعتماد</span>
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // 1. GPS Location Handling with Robust Fallbacks
        // ==========================================
        const gpsBtn = document.getElementById('gpsBtn');
        const gpsBtnText = document.getElementById('gpsBtnText');
        const latInput = document.getElementById('latitudeInput');
        const lngInput = document.getElementById('longitudeInput');
        const gpsStatusBox = document.getElementById('gpsStatusBox');
        const gpsStatusText = document.getElementById('gpsStatusText');
        const gpsHelpTip = document.getElementById('gpsHelpTip');
        const useAttendanceGpsBtn = document.getElementById('useAttendanceGpsBtn');

        function setGpsSuccess(lat, lng, label) {
            latInput.value = lat;
            lngInput.value = lng;
            gpsStatusBox.className = 'p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200 font-bold';
            gpsStatusText.innerHTML = `
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-1.5">
                        <span class="text-emerald-600">✅</span>
                        <span>${label || 'تم التقاط إحداثيات المحل بنجاح'}:</span>
                        <span class="font-numeric dir-ltr">(${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)})</span>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 underline">
                        معاينة على الخريطة ↗
                    </a>
                </div>
            `;
            if (gpsHelpTip) gpsHelpTip.classList.add('hidden');
        }

        if (latInput && latInput.value && lngInput && lngInput.value) {
            setGpsSuccess(latInput.value, lngInput.value, 'تم تحديد الموقع مسبقاً');
        }

        if (useAttendanceGpsBtn) {
            useAttendanceGpsBtn.addEventListener('click', function() {
                const lat = this.getAttribute('data-lat');
                const lng = this.getAttribute('data-lng');
                if (lat && lng) {
                    setGpsSuccess(lat, lng, 'تم استخدام موقع تسجيل الحضور الحالي');
                }
            });
        }

        if (gpsBtn) {
            gpsBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('عذراً، متصفح هاتفك لا يدعم تحديد الموقع الجغرافي.');
                    return;
                }

                gpsStatusBox.className = 'p-3 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-300 dark:border-sky-800 text-xs text-sky-900 dark:text-sky-200 font-bold';
                gpsStatusText.innerHTML = '⏳ جارٍ الاتصال بالأقمار الصناعية لتحديد موقع المحل...';
                gpsBtn.disabled = true;
                if (gpsBtnText) gpsBtnText.textContent = 'جارٍ تحديد الموقع...';

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setGpsSuccess(lat, lng, 'تم التقاط موقع المحل الحالي بنجاح');
                        gpsBtn.disabled = false;
                        if (gpsBtnText) gpsBtnText.textContent = 'إعادة التقاط موقع المحل';
                    },
                    function(error) {
                        gpsBtn.disabled = false;
                        if (gpsBtnText) gpsBtnText.textContent = 'إعادة المحاولة (GPS)';
                        
                        let message = 'تعذر التقاط الموقع الجغرافي.';
                        if (error.code === 1) { // PERMISSION_DENIED
                            message = 'تم رفض إذن الوصول للموقع في المتصفح. يمكنك إتمام التسجيل بدون GPS أو تفعيل الإذن.';
                            if (gpsHelpTip) gpsHelpTip.classList.remove('hidden');
                        } else if (error.code === 2) { // POSITION_UNAVAILABLE
                            message = 'إشارة الـ GPS غير متاحة حالياً. تأكد من تفعيل خدمة الموقع بالهاتف.';
                        } else if (error.code === 3) { // TIMEOUT
                            message = 'استغرق تحديد الموقع وقتاً طويلاً. يرجى إعادة المحاولة في مكان مفتوح.';
                        }

                        gpsStatusBox.className = 'p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 font-bold';
                        gpsStatusText.innerHTML = `⚠️ ${message}`;
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            });
        }

        // ==========================================
        // 2. Resilient Multi-Photo Upload & Instant Previews
        // ==========================================
        const triggerCameraBtn = document.getElementById('triggerCameraBtn');
        const triggerGalleryBtn = document.getElementById('triggerGalleryBtn');
        const cameraInput = document.getElementById('cameraInput');
        const galleryInput = document.getElementById('galleryInput');
        const storeImagesInput = document.getElementById('storeImagesInput');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const uploadEmptyHint = document.getElementById('uploadEmptyHint');
        const photoCountBadge = document.getElementById('photoCountBadge');

        // Main accumulated file collection using DataTransfer
        let accumulatedFiles = [];
        const MAX_PHOTOS = 5;
        const MAX_SIZE_MB = 10;

        if (triggerCameraBtn && cameraInput) {
            triggerCameraBtn.addEventListener('click', () => cameraInput.click());
        }

        if (triggerGalleryBtn && galleryInput) {
            triggerGalleryBtn.addEventListener('click', () => galleryInput.click());
        }

        function handleIncomingFiles(fileList) {
            if (!fileList || fileList.length === 0) return;

            Array.from(fileList).forEach(file => {
                if (accumulatedFiles.length >= MAX_PHOTOS) {
                    alert(`الحد الأقصى هو ${MAX_PHOTOS} صور للمحل.`);
                    return;
                }

                // Check size
                if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                    alert(`الصورة "${file.name}" أكبر من ${MAX_SIZE_MB} ميجابايت.`);
                    return;
                }

                accumulatedFiles.push(file);
            });

            syncAndRender();
        }

        if (cameraInput) {
            cameraInput.addEventListener('change', function(e) {
                handleIncomingFiles(e.target.files);
                cameraInput.value = ''; // Reset so same photo can be re-shot if needed
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', function(e) {
                handleIncomingFiles(e.target.files);
                galleryInput.value = '';
            });
        }

        function removeFile(index) {
            accumulatedFiles.splice(index, 1);
            syncAndRender();
        }

        function syncAndRender() {
            // 1. Sync to main form input via DataTransfer
            const dt = new DataTransfer();
            accumulatedFiles.forEach(file => dt.items.add(file));
            if (storeImagesInput) {
                storeImagesInput.files = dt.files;
            }

            // 2. Update count badge
            if (photoCountBadge) {
                photoCountBadge.textContent = `${accumulatedFiles.length} من ${MAX_PHOTOS} صور`;
                photoCountBadge.className = accumulatedFiles.length > 0 
                    ? 'text-[11px] font-bold text-emerald-600 dark:text-emerald-400 font-numeric' 
                    : 'text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-numeric';
            }

            // 3. Render previews
            if (previewContainer) {
                previewContainer.innerHTML = '';

                if (accumulatedFiles.length === 0) {
                    previewContainer.classList.add('hidden');
                    if (uploadEmptyHint) uploadEmptyHint.classList.remove('hidden');
                    return;
                }

                previewContainer.classList.remove('hidden');
                if (uploadEmptyHint) uploadEmptyHint.classList.add('hidden');

                accumulatedFiles.forEach((file, index) => {
                    const objectUrl = URL.createObjectURL(file);
                    const div = document.createElement('div');
                    div.className = 'relative aspect-square rounded-xl overflow-hidden border-2 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shadow-sm group';

                    const sizeInMb = (file.size / (1024 * 1024)).toFixed(1);

                    div.innerHTML = `
                        <img src="${objectUrl}" alt="صورة ${index + 1}" class="w-full h-full object-cover">
                        <button type="button" class="btn-remove-photo absolute top-1 left-1 w-6 h-6 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center font-bold text-xs shadow-md active:scale-90 transition-transform" data-index="${index}" title="حذف الصورة">
                            ✕
                        </button>
                        <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md text-[10px] font-black bg-black/75 text-white backdrop-blur-xs font-numeric">
                            ${index + 1}
                        </span>
                        <span class="absolute bottom-1 left-1 px-1 py-0.2 rounded text-[8px] font-semibold bg-white/80 text-slate-800 dark:bg-slate-900/80 dark:text-slate-200 font-numeric">
                            ${sizeInMb}MB
                        </span>
                    `;

                    previewContainer.appendChild(div);
                });

                // Attach delete handlers
                previewContainer.querySelectorAll('.btn-remove-photo').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const idx = parseInt(this.getAttribute('data-index'), 10);
                        removeFile(idx);
                    });
                });
            }
        }
    });
</script>
@endpush
@endsection
