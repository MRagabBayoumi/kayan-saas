@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="{{ !empty($reviewData) ? 'مراجعة وتدقيق بيانات الاستيراد' : 'استيراد المنتجات من إكسيل / CSV' }}" :breadcrumbs="['المنتجات والأصناف' => route('products.index'), 'استيراد' => '#']">
        <x-slot:actions>
            @if(!empty($reviewData))
                <button type="submit" form="confirmImportForm" class="erp-btn erp-btn-primary">
                    <x-icon name="check" size="16" />
                    <span>تأكيد الاستيراد الفعلي وحفظ البيانات</span>
                </button>
                <a href="{{ route('products.import.cancel') }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="x" size="16" />
                    <span>إلغاء ورفع ملف آخر</span>
                </a>
            @else
                <a href="{{ route('products.import.template') }}" class="erp-btn erp-btn-secondary border-[var(--primary)] text-[var(--primary)]">
                    <x-icon name="download" size="16" />
                    <span>تحميل قالب إكسيل الجاهز (اختياري)</span>
                </a>
                <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="arrow-left" size="16" />
                    <span>العودة لقائمة المنتجات</span>
                </a>
            @endif
        </x-slot:actions>

        <x-slot:extra>
            @if(!empty($reviewData))
                <span class="erp-badge erp-badge-info font-numeric px-2.5 py-1 text-xs">
                    <span>إجمالي السطور المفحوصة:</span>
                    <strong>{{ $reviewData['stats']['total'] }}</strong>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <!-- Flash Messages -->
    @if(session('status') === 'error')
        <div class="erp-alert erp-alert-danger mb-5" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>{{ session('message', 'حدث خطأ أثناء معالجة الملف.') }}</div>
        </div>
    @endif

    @if(empty($reviewData))
        <!-- STEP 1 & STEP 2 WORKFLOW GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch mb-6">
            <!-- Step 1: Template & Guidelines (6 cols) -->
            <div class="lg:col-span-6 flex flex-col">
                <div class="erp-card h-full flex flex-col m-0" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: #ffffff;">
                    <div class="erp-card-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; border-radius: 12px 12px 0 0;">
                        <div class="erp-card-title" style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #059669; color: #ffffff; font-size: 12px; font-weight: 800;">1</span>
                            <span>الخطوة 1: تنزيل قالب المنتجات والإرشادات</span>
                        </div>
                    </div>

                    <div class="erp-card-body" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 18px;">
                        <div>
                            <p style="font-size: 13px; color: #475569; margin: 0 0 14px 0; line-height: 1.6;">
                                يمكنك استيراد آلاف الأصناف والمنتجات دفعة واحدة مع تكويد الباركود وتحديث الأسعار والأرصدة الافتتاحية للمخازن بدقة وسرعة.
                            </p>

                            <!-- Download Guidelines Box -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center;">
                                            <x-icon name="file-text" size="20" />
                                        </div>
                                        <div>
                                            <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">قالب استيراد المنتجات المعتمد</h4>
                                            <span style="font-size: 12px; color: #059669; font-weight: 700;">ملف إكسيل رسمي (.xlsx) بحماية الباركود</span>
                                        </div>
                                    </div>
                                    <span class="erp-badge erp-badge-success" style="font-size: 11px; padding: 4px 10px; font-weight: 700;">Excel .xlsx</span>
                                </div>

                                <ul style="font-size: 12px; color: #334155; margin: 0 0 16px 0; padding-right: 20px; line-height: 1.9;">
                                    <li><strong>الباركود (Barcode):</strong> الأساس في التكويد والمطابقة لتمييز الأصناف ومنع التكرار.</li>
                                    <li><strong>كود الصنف (SKU):</strong> كود تمييز الصنف (يُنشأ أو يُضبط آلياً لمنع التضارب).</li>
                                    <li><strong>اسم المنتج والتصنيف:</strong> الاسم إلزامي بالعربية، والتصنيف يُنشأ تلقائياً إن لم يوجد.</li>
                                    <li><strong>الأسعار والوحدات:</strong> التكلفة، الجملة، القطاعي، الموزع، ووحدة القياس.</li>
                                    <li><strong>الرصيد الافتتاحي:</strong> كمية أول المدة وتكلفة الرصيد والمستودع المستلم (لتسجيل FIFO آلياً).</li>
                                </ul>

                                <div style="display: flex; gap: 10px;">
                                    <a href="{{ route('products.import.template', ['format' => 'xlsx']) }}" 
                                       class="erp-btn erp-btn-success" 
                                       style="flex: 1; height: 44px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                                        <x-icon name="download" size="18" />
                                        <span>تحميل القالب المنسق (Excel .xlsx)</span>
                                    </a>
                                    <a href="{{ route('products.import.template', ['format' => 'csv']) }}" 
                                       class="erp-btn erp-btn-secondary" 
                                       title="تحميل بصيغة CSV"
                                       style="height: 44px; padding: 0 14px; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                        <span>CSV</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Upload File & Review (6 cols) -->
            <div class="lg:col-span-6 flex flex-col">
                <div class="erp-card h-full flex flex-col m-0" style="border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); background: #ffffff;">
                    <div class="erp-card-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; border-radius: 12px 12px 0 0;">
                        <div class="erp-card-title" style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #ffffff; font-size: 12px; font-weight: 800;">2</span>
                            <span>الخطوة 2: رفع ملف البيانات للمراجعة والتدقيق</span>
                        </div>
                    </div>

                    <div class="erp-card-body" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; flex: 1; gap: 16px;">
                        <form id="uploadForm" action="{{ route('products.import.review') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; height: 100%; justify-content: space-between; gap: 16px;">
                            @csrf
                            <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                                ارفع ملف Excel أو CSV المعبأ بالأصناف. سيقوم النظام بفحص الملف سطر بسطر ومقارنته بالمخزون الحالي قبل أي عملية حفظ.
                            </div>

                            <!-- Interactive Styled Upload Dropzone -->
                            <div 
                                id="productDropzone"
                                style="border: 2px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 22px 16px; text-align: center; cursor: pointer; transition: all 0.2s ease; min-height: 140px; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                                onclick="document.getElementById('products_import_file').click()"
                                onmouseover="this.style.borderColor='var(--primary)'; this.style.backgroundColor='#fcfbfe';"
                                onmouseout="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc';"
                                ondragover="event.preventDefault(); this.style.borderColor='var(--primary)'; this.style.backgroundColor='#fcfbfe';"
                                ondragleave="this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc';"
                                ondrop="event.preventDefault(); this.style.borderColor='#cbd5e1'; this.style.backgroundColor='#f8fafc'; handleProductDropFile(event)"
                            >
                                <input 
                                    type="file" 
                                    id="products_import_file" 
                                    name="import_file" 
                                    accept=".csv,.xlsx,.xls,text/csv" 
                                    class="hidden" 
                                    required 
                                    onchange="handleProductFileSelect(this)"
                                />

                                <div id="productDropzonePrompt" style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 46px; height: 46px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                        <x-icon name="upload-cloud" size="26" />
                                    </div>
                                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                        اضغط لاختيار ملف المنتجات أو اسحبه وأفلته هنا
                                    </div>
                                    <div style="font-size: 12px; color: #64748b;">
                                        يدعم ملفات <strong>Excel (.xlsx)</strong> أو ملفات <strong>CSV (.csv)</strong> بحد أقصى 15MB
                                    </div>
                                </div>

                                <div id="productFileSelectedBadge" style="display: none; align-items: center; gap: 12px; background: #ffffff; padding: 12px 18px; border-radius: 10px; border: 1.5px solid #10b981; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; shrink-0;">
                                        <x-icon name="check" size="22" />
                                    </div>
                                    <div style="text-align: right;">
                                        <div id="productSelectedFileName" style="font-size: 13px; font-weight: 700; color: #0f172a; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                                        <div id="productSelectedFileSize" style="font-size: 11px; color: #64748b;"></div>
                                    </div>
                                    <button type="button" onclick="event.stopPropagation(); clearProductSelectedFile();" style="margin-right: 12px; font-size: 12px; color: #ef4444; font-weight: 700; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                        تغيير الملف
                                    </button>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div style="display: flex; flex-direction: column; gap: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                                <button type="submit" id="uploadSubmitBtn" class="erp-btn erp-btn-primary" style="width: 100%; height: 44px; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(113, 75, 103, 0.2);">
                                    <x-icon name="search" size="18" />
                                    <span>فحص ومراجعة محتويات الملف</span>
                                </button>

                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #64748b;">
                                    <x-icon name="shield" size="14" class="text-emerald-600" />
                                    <span>فحص استرشادي آمن؛ لا يتم حفظ أو تعديل أي صنف إلا بعد مراجعة شاشة التدقيق وتأكيدك.</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function handleProductFileSelect(input) {
                if (input.files && input.files[0]) {
                    displayProductFileInfo(input.files[0]);
                }
            }

            function handleProductDropFile(event) {
                if (event.dataTransfer.files && event.dataTransfer.files[0]) {
                    const input = document.getElementById('products_import_file');
                    input.files = event.dataTransfer.files;
                    displayProductFileInfo(event.dataTransfer.files[0]);
                }
            }

            function displayProductFileInfo(file) {
                const prompt = document.getElementById('productDropzonePrompt');
                const badge = document.getElementById('productFileSelectedBadge');
                const nameEl = document.getElementById('productSelectedFileName');
                const sizeEl = document.getElementById('productSelectedFileSize');

                if (prompt && badge && nameEl && sizeEl) {
                    prompt.style.display = 'none';
                    badge.style.display = 'flex';
                    nameEl.textContent = file.name;
                    const sizeKb = (file.size / 1024).toFixed(1);
                    sizeEl.textContent = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';
                }
            }

            function clearProductSelectedFile() {
                const input = document.getElementById('products_import_file');
                const prompt = document.getElementById('productDropzonePrompt');
                const badge = document.getElementById('productFileSelectedBadge');

                if (input) input.value = '';
                if (prompt) prompt.style.display = 'flex';
                if (badge) badge.style.display = 'none';
            }
        </script>
    @else
        <!-- STEP 2: PRE-IMPORT REVIEW & COMPARISON AGAINST DATABASE -->
        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <!-- Total Rows -->
            <div class="kpi-card p-3.5">
                <div class="kpi-header">
                    <span class="kpi-label">إجمالي السطور المقروءة</span>
                    <span class="kpi-icon"><x-icon name="trending-up" size="20" /></span>
                </div>
                <div class="kpi-value font-numeric">{{ $reviewData['stats']['total'] }}</div>
                <div class="kpi-note font-numeric">سطر في الملف المرفوع</div>
            </div>

            <!-- New Items -->
            <div class="kpi-card p-3.5 border-t-[3px] border-t-[var(--success)]">
                <div class="kpi-header">
                    <span class="kpi-label text-[var(--success-text)]">أصناف جديدة للإدراج</span>
                    <span class="kpi-icon"><x-icon name="plus" size="20" /></span>
                </div>
                <div class="kpi-value font-numeric text-[var(--success-text)]">{{ $reviewData['stats']['new_count'] }}</div>
                <div class="kpi-note font-numeric">غير مسجلة بقاعدة البيانات</div>
            </div>

            <!-- Existing Items (Update) -->
            <div class="kpi-card p-3.5 border-t-[3px] border-t-[var(--warning)]">
                <div class="kpi-header">
                    <span class="kpi-label text-[var(--warning)]">أصناف قائمة للتحديث</span>
                    <span class="kpi-icon"><x-icon name="refresh-cw" size="20" /></span>
                </div>
                <div class="kpi-value font-numeric text-[var(--warning)]">{{ $reviewData['stats']['update_count'] }}</div>
                <div class="kpi-note font-numeric">موجودة بالكود أو الباركود</div>
            </div>

            <!-- Invalid Rows -->
            <div class="kpi-card p-3.5 border-t-[3px] border-t-[var(--danger)]">
                <div class="kpi-header">
                    <span class="kpi-label text-[var(--danger)]">سطور تحتوي على أخطاء</span>
                    <span class="kpi-icon"><x-icon name="alert-triangle" size="20" /></span>
                </div>
                <div class="kpi-value font-numeric text-[var(--danger)]">{{ $reviewData['stats']['invalid_count'] }}</div>
                <div class="kpi-note font-numeric">سيتم تخطيها أو تصحيحها</div>
            </div>
        </div>

        <!-- Confirmation Form & Options -->
        <form id="confirmImportForm" action="{{ route('products.import.confirm') }}" method="POST" class="erp-card mb-5 py-3.5 px-4.5">
            @csrf

            <div class="flex flex-wrap items-center justify-between gap-3.5">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[var(--text-main)]" title="تحديث الاسم، التصنيف، الباركود، التعبئة، والحد الأدنى">
                        <input type="checkbox" name="update_existing" value="1" checked class="w-4 h-4 accent-[var(--primary)]">
                        <span>تحديث بيانات ومواصفات الأصناف المسجلة مسبقاً</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[var(--text-main)]" title="إلغاء التحديد يحافظ على كافة أسعارك الحالية في قاعدة البيانات دون أي تغيير">
                        <input type="checkbox" name="update_prices" value="1" {{ empty($reviewData['stats']['has_zero_prices_on_existing']) ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                        <span>تحديث الأسعار للأصناف المسجلة مسبقاً (إلغاء التحديد يحافظ على أسعارك الحالية)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                        <input type="checkbox" name="create_categories" value="1" checked class="w-4 h-4 accent-[var(--primary)]">
                        <span>إنشاء التصنيفات الجديدة تلقائياً إذا لم تكن موجودة</span>
                    </label>

                    @if(!empty($reviewData['stats']['has_zero_prices_on_existing']))
                        <div class="w-full flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <x-icon name="shield-check" size="16" class="text-amber-600 shrink-0" />
                            <span>حماية ذكية للأسعار: تم رصد أسعار صفرية بالملف لأصناف قائمة — تم إلغاء تحديد تحديث الأسعار تلقائياً لحماية أسعار النظام من التصفير.</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary py-2 px-5.5 font-bold">
                        <x-icon name="check" size="16" />
                        <span>تأكيد الاستيراد الفعلي وحفظ البيانات</span>
                    </button>
                    <a href="{{ route('products.import.cancel') }}" class="erp-btn erp-btn-secondary py-2 px-4">
                        <x-icon name="x" size="16" />
                        <span>إلغاء</span>
                    </a>
                </div>
            </div>
        </form>

        <!-- Comparison Table -->
        <div class="erp-card p-0 overflow-hidden">
            <div class="erp-card-header py-3 px-4 border-b border-[var(--border-color)]">
                <div class="erp-card-title">
                    <x-icon name="clipboard" size="18" />
                    <span>جدول معاينة وتدقيق السطور ومقارنتها بقاعدة البيانات</span>
                </div>
            </div>

            <div class="erp-table-responsive max-h-[520px] overflow-y-auto">
                <table class="erp-table">
                    <thead>
                        <tr class="sticky top-0 z-[2] bg-[var(--bg-surface)]">
                            <th class="w-[45px] text-center">#</th>
                            <th class="w-[100px] text-center">حالة السطر</th>
                            <th class="w-[130px]">كود الصنف (SKU)</th>
                            <th>اسم المنتج</th>
                            <th class="w-[120px]">التصنيف</th>
                            <th class="w-[140px]">الباركود</th>
                            <th class="w-[85px] text-left">التكلفة</th>
                            <th class="w-[95px] text-left">الجملة</th>
                            <th class="w-[85px] text-left">التجزئة</th>
                            <th class="w-[85px] text-left">الموزع</th>
                            <th class="w-[60px] text-center">الوحدة</th>
                            <th class="w-[60px] text-center">التعبئة</th>
                            <th class="w-[90px] text-center">رصيد أولي</th>
                            <th class="w-[110px]">المستودع</th>
                            <th>المطابقة وملاحظات التدقيق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviewData['rows'] as $row)
                            <tr class="{{ $row['action'] === 'invalid' ? 'bg-red-500/5' : ($row['action'] === 'update' ? 'bg-amber-500/5' : '') }}">
                                <td class="text-center font-numeric">{{ $row['line'] }}</td>

                                <!-- Status Badge -->
                                <td class="text-center">
                                    @if($row['action'] === 'new')
                                        <span class="erp-badge erp-badge-success text-[11px] inline-flex items-center gap-1">
                                            <x-icon name="plus" size="11" />
                                            <span>جديد</span>
                                        </span>
                                    @elseif($row['action'] === 'update')
                                        <span class="erp-badge erp-badge-warning text-[11px] inline-flex items-center gap-1">
                                            <x-icon name="refresh-cw" size="11" />
                                            <span>تحديث</span>
                                        </span>
                                    @else
                                        <span class="erp-badge erp-badge-danger text-[11px] inline-flex items-center gap-1">
                                            <x-icon name="alert-triangle" size="11" />
                                            <span>خطأ</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- SKU -->
                                <td>
                                    <span class="font-numeric font-bold text-[var(--primary)]">
                                        {{ $row['sku'] ?: '—' }}
                                    </span>
                                </td>

                                <!-- Name -->
                                <td>
                                    <strong class="text-[var(--text-main)]">{{ $row['name'] ?: '—' }}</strong>
                                </td>

                                <!-- Category -->
                                <td>
                                    <span class="erp-badge erp-badge-neutral">{{ $row['category_name'] }}</span>
                                </td>

                                <!-- Barcode -->
                                <td>
                                    @if($row['barcode'])
                                        <span class="font-numeric font-mono text-[11px] bg-[var(--bg-surface-alt)] py-0.5 px-1.5 rounded border border-[var(--border-color)]">
                                            {{ $row['barcode'] }}
                                        </span>
                                    @else
                                        <span class="text-[var(--text-light)] text-[11px]">—</span>
                                    @endif
                                </td>

                                <!-- Cost Price -->
                                <td class="text-left font-numeric">
                                    {{ number_format((float)$row['cost_price'], 2) }}
                                </td>

                                <!-- Wholesale Price with Comparison -->
                                <td class="text-left font-numeric">
                                    @if(!empty($row['preserve_prices']) && $row['existing_product'])
                                        <div class="flex flex-col gap-0.5">
                                            <strong class="text-[var(--success-text)]">{{ number_format($row['existing_product']['old_wholesale_price'], 2) }}</strong>
                                            <span class="inline-flex items-center gap-1 text-[10px] text-amber-700 bg-amber-50 px-1 py-0.5 rounded border border-amber-200 w-fit">
                                                سعر النظام محفوظ (بالملف: 0.00)
                                            </span>
                                        </div>
                                    @else
                                        <strong class="text-[var(--success-text)]">{{ number_format((float)$row['wholesale_price'], 2) }}</strong>
                                        @if($row['existing_product'])
                                            <small class="block text-[10px] text-[var(--text-muted)]">
                                                السابق: {{ number_format($row['existing_product']['old_wholesale_price'], 2) }}
                                            </small>
                                        @endif
                                    @endif
                                </td>

                                <!-- Retail Price -->
                                <td class="text-left font-numeric">
                                    {{ number_format((float)$row['retail_price'], 2) }}
                                </td>

                                <!-- Distributor Price -->
                                <td class="text-left font-numeric">
                                    @if($row['distributor_price'] !== null)
                                        <span>{{ number_format((float)$row['distributor_price'], 2) }}</span>
                                    @else
                                        <span class="text-[var(--text-light)] text-[11px]">—</span>
                                    @endif
                                </td>

                                <!-- Unit -->
                                <td class="text-center">
                                    <span class="text-[11px] text-[var(--text-muted)]">{{ $row['unit'] }}</span>
                                </td>

                                <!-- Pieces Per Package -->
                                <td class="text-center font-numeric">
                                    @if(!empty($row['pieces_per_package']))
                                        <span class="text-xs">{{ (int)$row['pieces_per_package'] }}</span>
                                    @else
                                        <span class="text-[var(--text-light)] text-[11px]">—</span>
                                    @endif
                                </td>

                                <!-- Opening Stock -->
                                <td class="text-center font-numeric">
                                    @if(!empty($row['opening_quantity']) && (float)$row['opening_quantity'] > 0)
                                        @php
                                            $qtyVal = (float)$row['opening_quantity'];
                                            $displayQty = ($qtyVal == floor($qtyVal)) ? (int)$qtyVal : number_format($qtyVal, 2);
                                        @endphp
                                        <span class="erp-badge erp-badge-info text-[11px] font-bold">{{ $displayQty }}</span>
                                    @else
                                        <span class="text-[var(--text-light)] text-[11px]">0</span>
                                    @endif
                                </td>

                                <!-- Warehouse -->
                                <td>
                                    <span class="text-xs text-[var(--text-muted)]">{{ $row['opening_warehouse_name'] ?? '—' }}</span>
                                </td>

                                <!-- Audit Notes -->
                                <td>
                                    @if($row['action'] === 'new')
                                        @if(!empty($row['sku_adjusted']))
                                            <div class="text-[var(--success-text)] text-xs flex flex-col gap-0.5">
                                                <span>• صنف جديد سيتم إدراجه.</span>
                                                <span class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded text-[11px] font-medium border border-amber-200">
                                                    تم ضبط الكود آلياً إلى (<strong>{{ $row['sku'] }}</strong>) لمنع التضارب مع صنف آخر.
                                                </span>
                                            </div>
                                        @elseif(!empty($row['barcode_generated']))
                                            <div class="text-[var(--success-text)] text-xs flex flex-col gap-0.5">
                                                <span>• صنف جديد سيتم إدراجه.</span>
                                                <span class="text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded text-[11px] font-mono border border-blue-200">
                                                    تم توليد باركود تلقائي: {{ $row['barcode'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-[var(--success-text)] text-xs">صنف جديد سيتم إدراجه في قاعدة البيانات.</span>
                                        @endif
                                    @elseif($row['action'] === 'update')
                                        @if(!empty($row['name_mismatch']))
                                            <div class="text-rose-600 font-bold text-xs flex flex-col gap-0.5 bg-rose-50/60 p-1.5 rounded border border-rose-200">
                                                <span class="inline-flex items-center gap-1 text-rose-700">
                                                    <x-icon name="alert-triangle" size="13" />
                                                    <strong>تحذير تضارب الباركود:</strong>
                                                </span>
                                                <span>الباركود مسجل لمنتج آخر: (<strong>{{ $row['existing_product']['name'] }}</strong>) بينما اسم الصنف بالملف هو (<strong>{{ $row['name'] }}</strong>). التأكيد سيستبدل بيانات الصنف القديم!</span>
                                            </div>
                                        @elseif(!empty($row['preserve_prices']))
                                            <span class="text-emerald-700 text-xs font-medium flex items-center gap-1">
                                                <x-icon name="shield-check" size="13" class="text-emerald-600 shrink-0" />
                                                <span>صنف مسجل مسبقاً (<strong>{{ $row['existing_product']['name'] }}</strong>) — سيتم تحديث المواصفات والباركود مع الحفاظ على الأسعار الحالية دون تصفير.</span>
                                            </span>
                                        @else
                                            <span class="text-[var(--warning)] text-xs">
                                                صنف مسجل مسبقاً (<strong>{{ $row['existing_product']['name'] }}</strong>) — سيتم تحديث الأسعار والمواصفات.
                                            </span>
                                        @endif
                                    @else
                                        <div class="text-[var(--danger)] text-[11px]">
                                            @foreach($row['errors'] as $err)
                                                <div>• {{ $err }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadSubmitBtn');

    if (uploadForm && uploadBtn) {
        uploadForm.addEventListener('submit', function() {
            setTimeout(function() {
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>جاري قراءة وفحص ومقارنة الأصناف...</span>
                `;
                uploadBtn.style.opacity = '0.85';
                uploadBtn.style.cursor = 'wait';
            }, 50);
        });
    }

    const confirmForm = document.getElementById('confirmImportForm');
    if (confirmForm) {
        confirmForm.addEventListener('submit', function() {
            setTimeout(function() {
                const btns = document.querySelectorAll('button[type="submit"][form="confirmImportForm"], #confirmImportForm button[type="submit"]');
                btns.forEach(b => {
                    b.disabled = true;
                    b.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>جاري حفظ الأصناف وتحديث الأرصدة...</span>
                    `;
                    b.style.opacity = '0.85';
                    b.style.cursor = 'wait';
                });
            }, 50);
        });
    }
});
</script>
@endpush
