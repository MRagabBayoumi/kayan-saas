<div class="space-y-6">
    <!-- 1. Download Template & Guidelines Card -->
    <div class="erp-card">
        <div class="erp-card-header flex items-center justify-between">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="file-text" size="18" class="text-[var(--primary)]" />
                <span>نموذج استيراد الأقسام والتصنيفات (Excel / CSV Template)</span>
            </div>
            <a href="{{ route('settings.departments.template') }}" class="erp-btn erp-btn-success erp-btn-sm">
                <x-icon name="download" size="16" />
                <span>تحميل نموذج الأقسام المعتمد (.CSV)</span>
            </a>
        </div>
        <div class="erp-card-body">
            <p class="text-sm text-[var(--text-secondary)] mb-4">
                يمكنك تجهيز الأقسام والتصنيفات جماعياً عبر ملف Excel أو CSV واستيرادها مباشرة إلى النظام مع الحفاظ على الأقسام الحالية والمنتجات المرتبطة بها دون أي تعديل على المعرفات الحالية.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-[var(--bg-surface-alt)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">الأعمدة الإلزامية:</strong>
                    <span class="text-[var(--text-muted)]">اسم القسم / التصنيف (إلزامي لكل سطر).</span>
                </div>
                <div class="p-3 bg-[var(--bg-surface-alt)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">الأكواد الفريدة والتبعيات:</strong>
                    <span class="text-[var(--text-muted)]">كود القسم (يجب أن يكون فريداً). يمكن تحديد كود أو اسم القسم الرئيسي لربط الأقسام الفرعية.</span>
                </div>
                <div class="p-3 bg-[var(--bg-surface-alt)] rounded border border-[var(--border-color)]">
                    <strong class="text-[var(--text-main)] block mb-1">الحالة والوصف:</strong>
                    <span class="text-[var(--text-muted)]">الحالة (نشط / معطل - الافتراضي نشط)، والوصف اختياري.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. File Upload Form Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="upload" size="18" class="text-[var(--primary)]" />
                <span>رفع ملف الأقسام للفحص والتدقيق (Preview & Validate)</span>
            </div>
        </div>
        <div class="erp-card-body">
            <form action="{{ route('settings.departments.preview') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                @csrf
                <div class="flex-1 min-w-[280px]">
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">اختر ملف Excel (.xlsx) أو CSV (.csv)</label>
                    <input type="file" name="import_file" accept=".xlsx,.csv,.txt" required class="erp-input w-full">
                </div>
                <div>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>فحص وتدقيق البيانات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Validation Preview Section (If Available) -->
    @if(isset($importPreview) && is_array($importPreview))
        <div class="erp-card border-2 border-[var(--primary)]">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="clipboard" size="18" class="text-[var(--primary)]" />
                    <span>نتائج الفحص والتدقيق المسبق قبل الحفظ</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs">إجمالي الأسطر: {{ $importPreview['summary']['total'] }}</span>
                    <span class="erp-badge erp-badge-success text-xs">صالحة: {{ $importPreview['summary']['valid'] }}</span>
                    @if($importPreview['summary']['invalid'] > 0)
                        <span class="erp-badge erp-badge-danger text-xs">أخطاء: {{ $importPreview['summary']['invalid'] }}</span>
                    @endif
                </div>
            </div>

            <div class="erp-card-body space-y-4">
                <!-- Errors Alert Table -->
                @if(!empty($importPreview['invalid_rows']))
                    <div class="p-4 bg-[var(--danger)]/10 border border-[var(--danger)] rounded">
                        <div class="font-bold text-[var(--danger)] text-sm mb-2 flex items-center gap-1">
                            <x-icon name="alert-triangle" size="16" />
                            <span>تنبيه: تم اكتشاف {{ count($importPreview['invalid_rows']) }} أسطر بها أخطاء أو تكرار:</span>
                        </div>
                        <div class="erp-table-responsive">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th class="w-16 text-center">رقم السطر</th>
                                        <th>اسم القسم المكتشف</th>
                                        <th>الكود</th>
                                        <th>أسباب الخطأ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importPreview['invalid_rows'] as $invRow)
                                        <tr>
                                            <td class="text-center font-numeric font-bold">{{ $invRow['row_number'] }}</td>
                                            <td>{{ $invRow['name'] }}</td>
                                            <td class="font-numeric">{{ $invRow['code'] }}</td>
                                            <td class="text-[var(--danger)]">
                                                <ul class="list-disc list-inside m-0 p-0">
                                                    @foreach($invRow['errors'] as $err)
                                                        <li>{{ $err }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Valid Rows Preview -->
                @if(!empty($importPreview['valid_rows']))
                    <div>
                        <div class="font-bold text-[var(--success)] text-sm mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <x-icon name="check" size="16" />
                                <span>جاهز للاستيراد: {{ count($importPreview['valid_rows']) }} قسماً وتصنيفاً صالحاً ومطابقاً للشروط</span>
                            </div>

                            <form action="{{ route('settings.departments.import') }}" method="POST">
                                @csrf
                                <input type="hidden" name="valid_rows" value="{{ json_encode($importPreview['valid_rows'], JSON_UNESCAPED_UNICODE) }}">
                                <button type="submit" class="erp-btn erp-btn-success">
                                    <x-icon name="check-circle" size="16" />
                                    <span>تأكيد واعتماد استيراد ({{ count($importPreview['valid_rows']) }}) قسم</span>
                                </button>
                            </form>
                        </div>

                        <div class="erp-table-responsive max-h-80 overflow-y-auto">
                            <table class="erp-table text-xs">
                                <thead>
                                    <tr>
                                        <th class="w-12 text-center">#</th>
                                        <th>كود القسم</th>
                                        <th>اسم القسم / التصنيف</th>
                                        <th>القسم الرئيسي</th>
                                        <th class="text-center">الحالة</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importPreview['valid_rows'] as $idx => $vRow)
                                        <tr>
                                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                                            <td class="font-numeric font-bold">{{ $vRow['code'] }}</td>
                                            <td class="font-semibold text-[var(--text-main)]">{{ $vRow['name'] }}</td>
                                            <td>{{ $vRow['parent_ref'] ?: '— (قسم رئيسي)' }}</td>
                                            <td class="text-center">
                                                <span class="erp-badge {{ $vRow['is_active'] ? 'erp-badge-success' : 'erp-badge-neutral' }} text-[10px]">
                                                    {{ $vRow['is_active'] ? 'نشط' : 'معطل' }}
                                                </span>
                                            </td>
                                            <td class="text-[var(--text-muted)]">{{ $vRow['description'] ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- 4. Existing Categories & Departments Table -->
    <div class="erp-card">
        <div class="erp-card-header flex items-center justify-between">
            <div class="erp-card-title flex items-center gap-2">
                <x-icon name="folder" size="18" class="text-[var(--primary)]" />
                <span>دليل الأقسام والتصنيفات الحالية بالنظام</span>
            </div>
            <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                إجمالي الأقسام: {{ count($categoriesList ?? []) }}
            </span>
        </div>
        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table text-xs">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>كود القسم</th>
                            <th>اسم القسم / التصنيف</th>
                            <th>القسم الأب / التبعية</th>
                            <th class="text-center">عدد المنتجات المرتبطة</th>
                            <th class="text-center">الحالة</th>
                            <th>المعرف الدائم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoriesList ?? [] as $index => $cat)
                            <tr>
                                <td class="text-center font-numeric text-[var(--text-muted)]">{{ $index + 1 }}</td>
                                <td class="font-numeric font-bold">{{ $cat->code ?: '—' }}</td>
                                <td class="font-semibold text-[var(--text-main)]">{{ $cat->name }}</td>
                                <td>{{ $cat->parent?->name ?: 'قسم رئيسي' }}</td>
                                <td class="text-center font-numeric font-bold">
                                    <span class="erp-badge erp-badge-info text-xs">{{ $cat->products_count }} منتج</span>
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $cat->is_active ? 'erp-badge-success' : 'erp-badge-danger' }} text-[10px]">
                                        {{ $cat->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="font-numeric text-[var(--text-muted)]">ID: #{{ $cat->id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
                                    لا توجد أقسام مسجلة بالنظام حتى الآن
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
