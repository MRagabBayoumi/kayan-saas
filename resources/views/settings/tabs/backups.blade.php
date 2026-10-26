<div class="space-y-6">
    <!-- 0. Custom Backup Folder Option Card -->
    <div class="erp-card border border-[var(--border-color)]">
        <div class="erp-card-header flex flex-wrap items-center justify-between gap-3">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="folder" size="18" class="text-[var(--primary)]" />
                <span>مجلد الحفظ الخارجي / الإضافي (Custom Backup Directory)</span>
            </div>
            @if(!empty($settings->custom_backup_path))
                @php
                    $isPathAccessible = \Illuminate\Support\Facades\File::isDirectory($settings->custom_backup_path) 
                        && is_writable($settings->custom_backup_path);
                @endphp
                @if($isPathAccessible)
                    <span class="erp-badge erp-badge-success text-xs flex items-center gap-1.5 py-1 px-2.5">
                        <x-icon name="check" size="14" />
                        <span>المجلد متصل وجاهز للمزامنة</span>
                    </span>
                @else
                    <span class="erp-badge erp-badge-danger text-xs flex items-center gap-1.5 py-1 px-2.5">
                        <x-icon name="alert-triangle" size="14" />
                        <span>المجلد غير متاح حالياً (تحقق من توصيل القرص)</span>
                    </span>
                @endif
            @endif
        </div>
        <div class="erp-card-body">
            <p class="text-xs text-[var(--text-secondary)] mb-4 leading-relaxed">
                يمكنك تحديد مسار مجلد اختياري على جهازك (مثل قرص آخر <code class="font-mono text-[var(--text-main)]">D:\ERP_Backups</code> أو فلاش ميموري <code class="font-mono text-[var(--text-main)]">E:\Backups</code> أو مجلد Google Drive / OneDrive). عند إنشاء أي نسخة احتياطية (يدوياً أو عبر الجدولة التلقائية)، سيتم تلقائياً نسخ ملف الـ ZIP إلى هذا المجلد فوراً.
            </p>

            <form action="{{ route('settings.backups.custom-path') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[280px]">
                        <input type="text" 
                               name="custom_backup_path" 
                               value="{{ old('custom_backup_path', $settings->custom_backup_path) }}" 
                               placeholder="مثال: D:\Kayan_Backups أو E:\FlashDrive\Backups" 
                               class="erp-input w-full font-mono text-xs">
                    </div>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="save" size="16" />
                        <span>حفظ وتفعيل المسار</span>
                    </button>
                    @if(!empty($settings->custom_backup_path))
                        <button type="button" 
                                onclick="document.querySelector('input[name=custom_backup_path]').value = ''; this.form.submit();" 
                                class="erp-btn erp-btn-secondary" 
                                title="إلغاء المسار الإضافي والاعتماد على المسار الافتراضي فقط">
                            <x-icon name="x" size="16" />
                            <span>إلغاء التحديد</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 1. Quick Backup Action Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- DB Backup Card -->
        <div class="erp-card flex flex-col justify-between border-t-4 border-t-[var(--primary)]">
            <div class="erp-card-body">
                <div class="flex items-start gap-3 mb-3">
                    <div class="p-2.5 rounded-lg bg-[var(--primary-subtle)] text-[var(--primary)]">
                        <x-icon name="database" size="24" />
                    </div>
                    <div>
                        <h4 class="font-bold text-base text-[var(--text-main)] mb-1">نسخة احتياطية لقاعدة البيانات (سريعة وموصى بها)</h4>
                        <p class="text-xs text-[var(--text-muted)] leading-relaxed">
                            يقوم بحفظ وتصدير كافة جداول MySQL (فواتير، أصناف، حسابات، حركات مخزنية) داخل ملف ZIP مضغوط بأعلى كفاءة وسرعة دون استهلاك لمساحة التخزين.
                        </p>
                    </div>
                </div>
            </div>
            <div class="erp-card-footer bg-[var(--bg-surface-alt)] p-4 flex justify-end border-t border-[var(--border-color)]">
                <form action="{{ route('settings.backups.create') }}" method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'جاري الإنشاء...';">
                    @csrf
                    <input type="hidden" name="type" value="db">
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="download-cloud" size="16" />
                        <span>إنشاء نسخة قاعدة البيانات الآن</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Full Backup Card -->
        <div class="erp-card flex flex-col justify-between border-t-4 border-t-emerald-600">
            <div class="erp-card-body">
                <div class="flex items-start gap-3 mb-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                        <x-icon name="archive" size="24" />
                    </div>
                    <div>
                        <h4 class="font-bold text-base text-[var(--text-main)] mb-1">نسخة احتياطية كاملة (قاعدة البيانات + المرفقات)</h4>
                        <p class="text-xs text-[var(--text-muted)] leading-relaxed">
                            يقوم بحفظ قاعدة البيانات بالإضافة إلى جميع الملفات والمرفقات والصور وشعارات الشركة المحفوظة في النظام داخل أرشيف ZIP شامل.
                        </p>
                    </div>
                </div>
            </div>
            <div class="erp-card-footer bg-[var(--bg-surface-alt)] p-4 flex justify-end border-t border-[var(--border-color)]">
                <form action="{{ route('settings.backups.create') }}" method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'جاري الإنشاء...';">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    <button type="submit" class="erp-btn erp-btn-success">
                        <x-icon name="archive" size="16" />
                        <span>إنشاء نسخة كاملة الآن</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Backups List Table -->
    <div class="erp-card">
        <div class="erp-card-header flex items-center justify-between">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="hard-drive" size="18" class="text-[var(--primary)]" />
                <span>النسخ الاحتياطية المتوفرة (Available Backups)</span>
                @if(isset($backupsList) && count($backupsList) > 0)
                    <span class="erp-badge erp-badge-primary text-xs px-2 py-0.5">{{ count($backupsList) }}</span>
                @endif
            </div>
            <a href="{{ route('settings.index', ['tab' => 'backups']) }}" class="erp-btn erp-btn-secondary erp-btn-sm" title="تحديث القائمة">
                <x-icon name="refresh-cw" size="14" />
                <span>تحديث</span>
            </a>
        </div>
        <div class="erp-card-body p-0">
            @if(isset($backupsList) && count($backupsList) > 0)
                <div class="overflow-x-auto">
                    <table class="erp-table w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-right">#</th>
                                <th class="text-right">اسم ملف النسخة</th>
                                <th class="text-right">الحجم</th>
                                <th class="text-right">تاريخ وتوقيت الإنشاء</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backupsList as $index => $backup)
                                <tr class="hover:bg-[var(--bg-surface-alt)] transition-colors">
                                    <td class="font-mono text-xs text-[var(--text-muted)]">{{ $index + 1 }}</td>
                                    <td class="font-mono font-medium text-[var(--text-main)] flex items-center gap-2">
                                        <x-icon name="file-text" size="16" class="text-[var(--primary)]" />
                                        <span>{{ $backup['filename'] }}</span>
                                    </td>
                                    <td>
                                        <span class="erp-badge erp-badge-neutral text-xs">
                                            {{ $backup['size_formatted'] }}
                                        </span>
                                    </td>
                                    <td class="text-xs text-[var(--text-muted)] font-mono">
                                        {{ $backup['created_at'] }}
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Download Button -->
                                            <a href="{{ route('settings.backups.download', $backup['filename']) }}" 
                                               class="erp-btn erp-btn-primary erp-btn-sm" 
                                               title="تنزيل النسخة الاحتياطية إلى جهازك">
                                                <x-icon name="download" size="14" />
                                                <span>تحميل</span>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('settings.backups.destroy', $backup['filename']) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  data-confirm="هل أنت متأكد من رغبتك في حذف هذا الملف الاحتياطي نهائياً؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-danger erp-btn-sm" title="حذف النسخة">
                                                    <x-icon name="trash-2" size="14" />
                                                    <span>حذف</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center text-[var(--text-muted)]">
                    <x-icon name="hard-drive" size="48" class="mx-auto mb-3 opacity-30" />
                    <p class="font-medium text-base text-[var(--text-main)] mb-1">لا توجد نسخ احتياطية مسجلة حتى الآن</p>
                    <p class="text-xs max-w-md mx-auto mb-4">
                        اضغط على أحد أزرار الإنشاء أعلاه لحفظ أول نسخة احتياطية من قاعدة البيانات فوراً.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Automated Backup Guide Card -->
    <div class="erp-card bg-[var(--bg-surface-alt)] border border-[var(--border-color)]">
        <div class="erp-card-header">
            <div class="erp-card-title flex items-center gap-2 text-sm">
                <x-icon name="clock" size="16" class="text-amber-500" />
                <span>إرشادات الجدولة والنسخ التلقائي اليومي (Automated Daily Backups)</span>
            </div>
        </div>
        <div class="erp-card-body text-xs text-[var(--text-secondary)] space-y-3">
            <p>
                يمكنك تفعيل النسخ الاحتياطي التلقائي ليعمل يومياً في موعد محدد (مثلاً الساعة 12:00 ليلاً) عبر أداة <strong>Windows Task Scheduler</strong> دون أي تدخل يدوي:
            </p>
            <div class="p-3 bg-[var(--bg-surface)] rounded border border-[var(--border-color)] font-mono text-[11px] select-all text-[var(--text-main)]">
                {{ PHP_BINARY }} {{ base_path('artisan') }} backup:run --only-db --disable-notifications
            </div>
            <ul class="list-disc list-inside space-y-1 text-[var(--text-muted)]">
                <li>المسار الافتراضي للنسخ المحفوظة على جهازك: <code class="font-mono text-[var(--text-main)]">storage\app\backups\kayan-erp\</code></li>
                <li>يُنصح دورياً بنسخ ملف الـ ZIP إلى وحدة تخزين خارجية (فلاش ميموري / هارد ديسك خارجي) أو رفعها على سحابة (Google Drive).</li>
            </ul>
        </div>
    </div>
</div>
