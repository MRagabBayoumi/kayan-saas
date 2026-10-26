<div class="space-y-6" id="printersSettingsContainer" data-discover-url="{{ route('settings.printers.discover') }}" data-test-url="{{ route('settings.printers.test') }}">
    <!-- 1. Discovery & Hardware Status Banner -->
    <div class="erp-card bg-gradient-to-l from-[var(--primary)]/5 to-transparent border-[var(--primary)]/20">
        <div class="erp-card-body flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-[var(--primary)]/10 text-[var(--primary)] flex items-center justify-center shrink-0">
                    <x-icon name="printer" size="22" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-[var(--text-main)] mb-1">مركز التحكم في الطابعات وأجهزة الكاشير</h3>
                    <p class="text-xs text-[var(--text-muted)] leading-relaxed">
                        يسمح لك النظام بربط وتعيين طابعات الإيصالات الحرارية، وطابعات الباركود، وطابعات الفواتير الرسمية A4 ديناميكياً بدون الحاجة لتعديل أي ملفات برمجية.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="erp-btn erp-btn-secondary" id="btnDiscoverPrinters" data-action="discover-printers">
                    <x-icon name="refresh-cw" size="15" id="discoverIcon" />
                    <span id="discoverBtnText">فحص وتحديث الطابعات المتاحة</span>
                </button>
            </div>
        </div>
        <div id="discoveryStatusAlert" class="hidden px-5 pb-4">
            <div class="p-3 rounded-lg text-xs font-semibold flex items-center justify-between gap-2 bg-emerald-50 text-emerald-800 border border-emerald-200" id="discoveryAlertBox">
                <div class="flex items-center gap-2">
                    <x-icon name="check-circle" size="16" class="text-emerald-600 shrink-0" />
                    <span id="discoveryAlertMsg">تم فحص النظام بنجاح.</span>
                </div>
                <span class="text-[11px] opacity-75" id="discoveryTimestamp"></span>
            </div>
        </div>
    </div>

    <!-- 2. Master Printers Assignment Form -->
    <form id="printersSettingsForm" action="{{ route('settings.printers.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <!-- Left Column: Receipt Thermal & Barcode Printers -->
            <div class="space-y-6">
                <!-- Card A: Thermal Cashier Receipt Printer -->
                <div class="erp-card">
                    <div class="erp-card-header flex items-center justify-between">
                        <div class="erp-card-title flex items-center gap-2">
                            <x-icon name="receipt" size="18" class="text-amber-500" />
                            <span>طابعة إيصالات الكاشير (Thermal 80mm)</span>
                        </div>
                        <button type="button" class="erp-btn erp-btn-secondary erp-btn-sm" data-action="test-print" data-type="thermal_80mm" data-target-input="#thermal_printer_name">
                            <x-icon name="printer" size="14" />
                            <span>تجربة الطباعة</span>
                        </button>
                    </div>
                    <div class="erp-card-body space-y-4">
                        <div class="erp-form-group">
                            <label for="thermal_printer_name" class="erp-form-label">
                                <span>اسم الطابعة أو مسار المشاركة</span>
                                <span class="required">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="thermal_printer_name"
                                    name="thermal_printer_name"
                                    list="systemPrintersList"
                                    value="{{ old('thermal_printer_name', $settings->thermal_printer_name ?? 'XP-80C') }}"
                                    class="erp-input w-full font-mono text-sm"
                                    placeholder="مثال: XP-80C أو \\192.168.0.41\XP-80C"
                                    required
                                >
                            </div>
                            <small class="text-[var(--text-muted)] block mt-1">
                                يمكنك الاختيار من القائمة أو كتابة اسم الطابعة المحلية أو مسار المشاركة عبر الشبكة (UNC Path).
                            </small>
                            @error('thermal_printer_name')
                                <span class="erp-error-msg">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Printing Mode Selection -->
                        <div class="erp-form-group">
                            <label class="erp-form-label mb-2">طريقة طباعة إيصالات الكاشير</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Option 1: Browser Kiosk (Recommended for zero-driver network cashier) -->
                                <label class="erp-radio-card cursor-pointer {{ old('receipt_print_mode', $settings->receipt_print_mode ?? 'browser_kiosk') === 'browser_kiosk' ? 'selected' : '' }}" id="cardModeBrowser">
                                    <input
                                        type="radio"
                                        name="receipt_print_mode"
                                        value="browser_kiosk"
                                        {{ old('receipt_print_mode', $settings->receipt_print_mode ?? 'browser_kiosk') === 'browser_kiosk' ? 'checked' : '' }}
                                    >
                                    <div class="erp-radio-card-body">
                                        <div class="erp-radio-card-title flex items-center justify-between">
                                            <span class="font-bold text-xs text-[var(--text-main)]">متصفح الكاشير المباشر</span>
                                            <span class="erp-badge erp-badge-success text-[10px]">مستحسن</span>
                                        </div>
                                        <div class="erp-radio-card-desc text-[11px] text-[var(--text-muted)] mt-1">
                                            طباعة فورية عبر نافذة المتصفح بدون الحاجة لكلمات سر للشبكة أو صلاحيات خاصة لكاشير المحل.
                                        </div>
                                    </div>
                                </label>

                                <!-- Option 2: Server Python Spooler -->
                                <label class="erp-radio-card cursor-pointer {{ old('receipt_print_mode', $settings->receipt_print_mode ?? 'browser_kiosk') === 'server_python' ? 'selected' : '' }}" id="cardModeServer">
                                    <input
                                        type="radio"
                                        name="receipt_print_mode"
                                        value="server_python"
                                        {{ old('receipt_print_mode', $settings->receipt_print_mode ?? 'browser_kiosk') === 'server_python' ? 'checked' : '' }}
                                    >
                                    <div class="erp-radio-card-body">
                                        <div class="erp-radio-card-title flex items-center justify-between">
                                            <span class="font-bold text-xs text-[var(--text-main)]">طباعة صامتة من السيرفر</span>
                                            <span class="erp-badge erp-badge-info text-[10px]">Windows Spooler</span>
                                        </div>
                                        <div class="erp-radio-card-desc text-[11px] text-[var(--text-muted)] mt-1">
                                            إرسال أمر الطباعة الخلفية مباشرة عبر محرك بايثون إلى طابعات السيرفر المباشرة.
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @error('receipt_print_mode')
                                <span class="erp-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Card B: Barcode Sticker Printer -->
                <div class="erp-card">
                    <div class="erp-card-header flex items-center justify-between">
                        <div class="erp-card-title flex items-center gap-2">
                            <x-icon name="barcode" size="18" class="text-emerald-500" />
                            <span>طابعة استيكرات وملصقات الباركود</span>
                        </div>
                        <button type="button" class="erp-btn erp-btn-secondary erp-btn-sm" data-action="test-print" data-type="barcode" data-target-input="#barcode_printer_name">
                            <x-icon name="printer" size="14" />
                            <span>تجربة الباركود</span>
                        </button>
                    </div>
                    <div class="erp-card-body space-y-4">
                        <div class="erp-form-group">
                            <label for="barcode_printer_name" class="erp-form-label">
                                <span>اسم طابعة الباركود</span>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="barcode_printer_name"
                                name="barcode_printer_name"
                                list="systemPrintersList"
                                value="{{ old('barcode_printer_name', $barcodeSettings->printer_name ?? 'Xprinter XP-233B') }}"
                                class="erp-input w-full font-mono text-sm"
                                placeholder="مثال: Xprinter XP-233B"
                            >
                            <small class="text-[var(--text-muted)] block mt-1">
                                هذه القيمة متزامنة تلقائياً مع تبويب "إعدادات الباركود" لتجنب أي تعارض في أسماء الطابعات.
                            </small>
                            @error('barcode_printer_name')
                                <span class="erp-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Document A4 Printer & Network Credentials -->
            <div class="space-y-6">
                <!-- Card C: Document & Official Invoices (A4 / A5) -->
                <div class="erp-card">
                    <div class="erp-card-header flex items-center justify-between">
                        <div class="erp-card-title flex items-center gap-2">
                            <x-icon name="file-text" size="18" class="text-blue-500" />
                            <span>طابعة الفواتير والمستندات الرسمية (A4 / A5)</span>
                        </div>
                        <button type="button" class="erp-btn erp-btn-secondary erp-btn-sm" data-action="test-print" data-type="document_a4" data-target-input="#document_printer_name">
                            <x-icon name="printer" size="14" />
                            <span>تجربة صفحة A4</span>
                        </button>
                    </div>
                    <div class="erp-card-body space-y-4">
                        <div class="erp-form-group">
                            <label for="document_printer_name" class="erp-form-label">
                                <span>اسم طابعة المستندات</span>
                                <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="document_printer_name"
                                name="document_printer_name"
                                list="systemPrintersList"
                                value="{{ old('document_printer_name', $settings->document_printer_name ?? 'TOSHIBA Generic Printer XL') }}"
                                class="erp-input w-full font-mono text-sm"
                                placeholder="مثال: TOSHIBA Generic Printer XL"
                            >
                            <small class="text-[var(--text-muted)] block mt-1">
                                تستخدم لطباعة فواتير المبيعات والمشتريات بصيغة A4، وكشوفات حركة الأصناف وتقارير المخزن.
                            </small>
                            @error('document_printer_name')
                                <span class="erp-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Card D: Network Share Credentials (Optional SMB) -->
                <div class="erp-card">
                    <div class="erp-card-header">
                        <div class="erp-card-title flex items-center gap-2">
                            <x-icon name="shield" size="18" class="text-purple-500" />
                            <span>بيانات الدخول لمشاركة الطابعات عبر الشبكة (اختياري)</span>
                        </div>
                    </div>
                    <div class="erp-card-body space-y-4">
                        <div class="p-3 rounded-lg bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-xs text-[var(--text-muted)] leading-relaxed">
                            مطلوب فقط في حالة مشاركة طابعة متصلة بجهاز كمبيوتر آخر عبر الشبكة المحلية (مثل <code>\\192.168.0.41\XP-80C</code>) وكان نظام ويندوز يطلب اسم مستخدم وكلمة مرور للوصول إليها.
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="erp-form-group">
                                <label for="printer_network_user" class="erp-form-label">اسم مستخدم الجهاز البعيد</label>
                                <input
                                    type="text"
                                    id="printer_network_user"
                                    name="printer_network_user"
                                    value="{{ old('printer_network_user', $settings->printer_network_user) }}"
                                    class="erp-input w-full font-mono text-sm"
                                    placeholder="مثال: hp أو Cashier"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="erp-form-group">
                                <label for="printer_network_password" class="erp-form-label">
                                    <span>كلمة مرور الجهاز البعيد</span>
                                    @if(!empty($settings->printer_network_password))
                                        <span class="text-[10px] text-emerald-600 font-semibold">(محفوظة حالياً)</span>
                                    @endif
                                </label>
                                <input
                                    type="password"
                                    id="printer_network_password"
                                    name="printer_network_password"
                                    class="erp-input w-full font-mono text-sm"
                                    placeholder="{{ !empty($settings->printer_network_password) ? '••••••••' : 'اتركه فارغاً إن لم توجد' }}"
                                    autocomplete="new-password"
                                >
                            </div>
                        </div>

                        @if(!empty($settings->printer_network_password))
                            <div class="flex items-center gap-2 pt-1">
                                <label class="inline-flex items-center gap-2 text-xs text-rose-600 cursor-pointer">
                                    <input type="checkbox" name="clear_network_password" value="1" class="erp-checkbox">
                                    <span>مسح كلمة المرور المحفوظة والرجوع للوضع الافتراضي</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Card E: Automation Options -->
                <div class="erp-card">
                    <div class="erp-card-header">
                        <div class="erp-card-title flex items-center gap-2">
                            <x-icon name="settings" size="18" class="text-slate-500" />
                            <span>سلوك الطباعة التلقائية (Automation)</span>
                        </div>
                    </div>
                    <div class="erp-card-body">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="auto_print_on_save"
                                value="1"
                                {{ old('auto_print_on_save', $settings->auto_print_on_save ?? true) ? 'checked' : '' }}
                                class="erp-checkbox mt-1 shrink-0"
                            >
                            <div>
                                <span class="text-sm font-semibold text-[var(--text-main)] block">
                                    طباعة إيصال الكاشير تلقائياً فور حفظ الفاتورة الجديدة
                                </span>
                                <small class="text-xs text-[var(--text-muted)] block mt-0.5">
                                    عند تفعيل هذا الخيار، سيتم تشغيل أمر الطباعة المباشر بمجرد إتمام معاملة البيع دون انتظار ضغط زر الطباعة يدوياً.
                                </small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Submit Bar -->
        <div class="flex items-center justify-between gap-3 mt-6 pt-4 border-t border-[var(--border-color)]">
            <div class="flex items-center gap-3">
                <button type="submit" class="erp-btn erp-btn-primary py-2.5 px-6 text-sm">
                    <x-icon name="save" size="16" />
                    <span>حفظ تخصيصات الطابعات</span>
                </button>
                <a href="{{ route('settings.index', ['tab' => 'printers']) }}" class="erp-btn erp-btn-secondary py-2.5 px-5">
                    <span>إلغاء</span>
                </a>
            </div>
            <div class="text-xs text-[var(--text-muted)] hidden sm:block">
                تطبق هذه الإعدادات على الفور في جميع شاشات البيع والكاشير.
            </div>
        </div>
    </form>

    <!-- Datalist for Auto-discovered System Printers -->
    <datalist id="systemPrintersList">
        @if(!empty($systemPrinters) && is_array($systemPrinters))
            @foreach($systemPrinters as $pName)
                <option value="{{ $pName }}">{{ $pName }}</option>
            @endforeach
        @endif
    </datalist>
</div>
