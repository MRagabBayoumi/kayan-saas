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
        <!-- STEP 1: UPLOAD FILE & TEMPLATE DOWNLOAD -->
        <div class="grid grid-cols-[1fr_340px] gap-5">
            <!-- Upload Box -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="upload" size="18" />
                        <span>رفع ملف البيانات للمراجعة والتدقيق</span>
                    </div>
                </div>
                <div class="erp-card-body">
                    <p class="text-xs text-[var(--text-muted)] mb-4 leading-relaxed">
                        ارفع ملف إكسيل (<strong>.xlsx</strong>) أو ملف قيم مفصولة بفواصل (<strong>.csv</strong>). 
                        سيقوم النظام بفحص محتويات الملف سطر بسطر ومقارنتها بقاعدة البيانات الحالية <code>kayan_erp</code> لاكتشاف الأصناف الجديدة، والتحقق من الأسعار، وتمييز الأصناف الموجودة مسبقاً قبل تنفيذ أي عملية حفظ.
                    </p>

                    <form id="uploadForm" action="{{ route('products.import.review') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="erp-form-group mb-5">
                            <label for="importFile" class="erp-form-label font-bold mb-2">
                                اختر ملف الإكسيل أو الـ CSV من جهازك:
                            </label>
                            <input 
                                type="file" 
                                id="importFile" 
                                name="import_file" 
                                accept=".csv,.xlsx,.xls,text/csv" 
                                class="erp-input p-2.5 @error('import_file') erp-input-error @enderror" 
                                required
                            >
                            @error('import_file')
                                <small class="text-[var(--danger)] block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between border-t border-[var(--border-color)] pt-4">
                            <button type="submit" id="uploadSubmitBtn" class="erp-btn erp-btn-primary py-2.5 px-6 font-bold">
                                <x-icon name="search" size="16" />
                                <span>فحص ومراجعة محتويات الملف</span>
                            </button>
                            <small class="text-[var(--text-muted)] text-[11px]">الحد الأقصى لحجم الملف: 10MB</small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Optional Template Info Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="download" size="18" />
                        <span>قالب البيانات الجاهز</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-3.5">
                    <div class="erp-alert erp-alert-info m-0 py-2.5 px-3 text-xs">
                        <x-icon name="info" size="16" />
                        <div>تنزيل القالب <strong>اختياري (Option)</strong> وليس فرضاً، والغرض منه مساعدتك في معرفة التنسيق الأمثل للأعمدة.</div>
                    </div>

                    <a href="{{ route('products.import.template') }}" class="erp-btn erp-btn-secondary w-full justify-center p-2.5 font-bold border-[var(--primary)] text-[var(--primary)] inline-flex items-center gap-1.5">
                        <x-icon name="download" size="16" />
                        <span>تحميل القالب المنسق (CSV)</span>
                    </a>

                    <div>
                        <strong class="block text-xs text-[var(--text-main)] mb-1.5 font-bold">الأعمدة المدعومة في الملف:</strong>
                        <ul class="text-[11px] text-[var(--text-muted)] my-0 mx-4 leading-loose">
                            <li><strong>الباركود (Barcode):</strong> الأساس الأول في التكويد والمطابقة لتمييز الأصناف بدقة.</li>
                            <li><strong>كود الصنف (SKU):</strong> كود تمييز الصنف (يُنشأ أو يُضبط آلياً لمنع التضارب).</li>
                            <li><strong>اسم المنتج:</strong> إلزامي بالعربية.</li>
                            <li><strong>التصنيف:</strong> اختياري (يُنشأ تلقائياً إن لم يوجد).</li>
                            <li><strong>الأسعار:</strong> التكلفة، الجملة، القطاعي، وسعر الموزع (أرقام صحيحة أو عشرية).</li>
                            <li><strong>المواصفات:</strong> وحدة القياس وعدد القطع في الطرد / الكرتونة (أرقام صحيحة).</li>
                            <li><strong>حد الطلب الأدنى:</strong> رقم صحيح لتنبيه النواقص.</li>
                            <li><strong>الرصيد الافتتاحي:</strong> كمية أول المدة، تكلفة الرصيد، واسم المستودع المستلم (لتسجيل FIFO آلياً).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

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
                <div class="flex flex-wrap items-center gap-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                        <input type="checkbox" name="update_existing" value="1" checked class="w-4 h-4 accent-[var(--primary)]">
                        <span>تحديث أسعار وبيانات الأصناف الموجودة مسبقاً</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                        <input type="checkbox" name="create_categories" value="1" checked class="w-4 h-4 accent-[var(--primary)]">
                        <span>إنشاء التصنيفات الجديدة تلقائياً إذا لم تكن موجودة</span>
                    </label>
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
                                    <strong class="text-[var(--success-text)]">{{ number_format((float)$row['wholesale_price'], 2) }}</strong>
                                    @if($row['existing_product'])
                                        <small class="block text-[10px] text-[var(--text-muted)]">
                                            السابق: {{ number_format($row['existing_product']['old_wholesale_price'], 2) }}
                                        </small>
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
