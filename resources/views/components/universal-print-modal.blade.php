<!-- Universal Print Options Modal (A4, A5, Thermal 80mm & Python Direct Print) -->
<div id="universalPrintModal" class="erp-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="erp-modal-container max-w-xl">
        <!-- Header -->
        <div class="erp-modal-header flex items-center justify-between border-b border-[var(--border-color)] pb-3">
            <div class="erp-modal-title flex items-center gap-2 text-base font-bold text-[var(--text-main)]">
                <x-icon name="printer" size="20" class="text-[var(--primary)]" />
                <span>خيارات ونماذج الطباعة</span>
            </div>
            <button type="button" class="erp-modal-close text-[var(--text-muted)] hover:text-[var(--text-main)]" data-action="close-print-modal" title="إغلاق">✕</button>
        </div>

        <!-- Body -->
        <div class="erp-modal-body p-4 sm:p-5 space-y-4">
            <!-- Active Document Title Banner -->
            <div class="bg-[var(--primary-soft)] border border-[var(--primary-border)] rounded-lg p-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icon name="file-text" size="16" class="text-[var(--primary)]" />
                    <span id="printModalDocTitle" class="text-xs sm:text-sm font-bold text-[var(--text-main)]">مستند للطباعة</span>
                </div>
                <span class="erp-badge erp-badge-primary text-[10px] font-bold">جاهز للطباعة</span>
            </div>

            <!-- Print Format Cards Grid (Thermal, A4, A5) -->
            <div class="space-y-3">
                <!-- 1. Thermal 80mm Receipt (Xprinter XP-80C) - Highlighted for POS/Speed -->
                <div class="border-2 border-emerald-500/40 bg-emerald-50/20 dark:bg-emerald-950/10 rounded-xl p-3.5 transition-all hover:border-emerald-500">
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                                <x-icon name="printer" size="18" />
                            </div>
                            <div>
                                <div class="font-bold text-xs sm:text-sm text-[var(--text-main)] flex items-center gap-1.5">
                                    <span>إيصال حراري (80 مم)</span>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded">طابعة الكاشير السريعة</span>
                                </div>
                                <div class="text-[11px] text-[var(--text-muted)]">شريط رول فوري بدون هوامش · متوافق مع Xprinter (XP-80C)</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-emerald-500/20">
                        <button type="button" id="printModalDirectThermal" data-action="python-direct-print" data-url="" class="erp-btn erp-btn-sm erp-btn-success text-xs flex-1 justify-center py-2 font-bold shadow-xs">
                            <x-icon name="zap" size="14" />
                            <span>طباعة فورية صامتة (Xprinter) ⚡</span>
                        </button>
                        <a id="printModalLinkThermal" href="#" target="_blank" class="erp-btn erp-btn-sm erp-btn-secondary text-xs px-3 py-2">
                            <x-icon name="external-link" size="13" />
                            <span>معاينة المتصفح</span>
                        </a>
                    </div>
                </div>

                <!-- 2. Standard A4 Document -->
                <div class="border border-[var(--border-color)] bg-[var(--bg-surface)] hover:border-[var(--primary)] rounded-xl p-3.5 transition-all">
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] flex items-center justify-center border border-[var(--primary-border)]">
                                <x-icon name="file-text" size="18" />
                            </div>
                            <div>
                                <div class="font-bold text-xs sm:text-sm text-[var(--text-main)] flex items-center gap-1.5">
                                    <span>فاتورة قياسية كاملة (A4)</span>
                                    <span class="text-[10px] bg-sky-100 text-sky-800 font-bold px-1.5 py-0.5 rounded">210 × 297 مم</span>
                                </div>
                                <div class="text-[11px] text-[var(--text-muted)]">نموذج رسمي كامل للشركات والموردين والعملاء (طابعات الليزر والمكتب)</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-[var(--border-light)]">
                        <a id="printModalLinkA4" href="#" target="_blank" class="erp-btn erp-btn-sm erp-btn-primary text-xs flex-1 justify-center py-2">
                            <x-icon name="printer" size="14" />
                            <span>معاينة وطباعة A4</span>
                        </a>
                        <button type="button" id="printModalDirectA4" data-action="python-direct-print" data-url="" class="erp-btn erp-btn-sm erp-btn-secondary text-xs px-3 py-2" title="طباعة فورية عبر بايثون على طابعة المستندات">
                            <x-icon name="zap" size="13" />
                            <span>طباعة فورية بايثون</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Compact A5 Document -->
                <div class="border border-[var(--border-color)] bg-[var(--bg-surface)] hover:border-[var(--primary)] rounded-xl p-3.5 transition-all">
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center border border-indigo-200">
                                <x-icon name="file" size="18" />
                            </div>
                            <div>
                                <div class="font-bold text-xs sm:text-sm text-[var(--text-main)] flex items-center gap-1.5">
                                    <span>فاتورة مدمجة توفيرية (A5)</span>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.5 rounded">148 × 210 مم</span>
                                </div>
                                <div class="text-[11px] text-[var(--text-muted)]">نصف ورقة قياسية لتوفير الورق والأحبار</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-[var(--border-light)]">
                        <a id="printModalLinkA5" href="#" target="_blank" class="erp-btn erp-btn-sm erp-btn-secondary text-xs flex-1 justify-center py-2">
                            <x-icon name="printer" size="14" />
                            <span>معاينة وطباعة A5</span>
                        </a>
                        <button type="button" id="printModalDirectA5" data-action="python-direct-print" data-url="" class="erp-btn erp-btn-sm erp-btn-secondary text-xs px-3 py-2" title="طباعة فورية عبر بايثون على طابعة المستندات">
                            <x-icon name="zap" size="13" />
                            <span>طباعة فورية بايثون</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="erp-modal-footer flex items-center justify-between border-t border-[var(--border-color)] p-3.5 bg-[var(--bg-hover)]">
            <span class="text-[11px] text-[var(--text-muted)] flex items-center gap-1">
                <x-icon name="check-circle" size="13" class="text-emerald-600" />
                <span>الطباعة الفورية تتم تلقائياً عبر محرك بايثون المركزي المتصل بالطابعات</span>
            </span>
            <button type="button" data-action="close-print-modal" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
        </div>
    </div>
</div>
