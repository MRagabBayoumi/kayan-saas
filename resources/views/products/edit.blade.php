@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل المنتج: {{ $product->name }}" :breadcrumbs="['المنتجات والأصناف' => route('products.index'), $product->name => route('products.show', $product), 'تعديل' => '#']">
        <x-slot:actions>
            <button type="submit" form="productEditForm" class="erp-btn erp-btn-primary">
                <x-icon name="save" size="16" />
                <span>حفظ التعديلات</span>
            </button>
            <button type="button" data-action="direct-print-edit" data-product-id="{{ $product->id }}" class="erp-btn erp-btn-secondary" title="طباعة فورية للملصق ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم) بدون نوافذ">
                <x-icon name="printer" size="16" />
                <span>طباعة الباركود</span>
            </button>
            <a href="{{ route('products.show', $product) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="x" size="16" />
                <span>إلغاء</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <!-- Validation Errors Alert -->
    @if($errors->any())
        <div class="erp-alert erp-alert-danger mb-5" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>
                <strong>يرجى تصحيح الأخطاء التالية قبل حفظ التعديلات:</strong>
                <ul class="my-1.5 mx-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="productEditForm" action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" data-product-form>
        @csrf
        @method('PUT')

        <div class="erp-form-card-grid">
            <!-- Panel 1: Main Product Details -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="package" size="18" />
                        <span>بيانات الصنف الأساسية</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-4">
                    <!-- Product Name -->
                    <div class="erp-form-group">
                        <label for="productName" class="erp-form-label">
                            اسم المنتج <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="productName" 
                            name="name" 
                            value="{{ old('name', $product->name) }}" 
                            class="erp-input @error('name') erp-input-error @enderror" 
                            required
                        >
                    </div>

                    <!-- SKU & Category Row -->
                    <div class="erp-form-row-2col">
                        <div class="erp-form-group">
                            <label for="productSku" class="erp-form-label">
                                كود الصنف (SKU) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="productSku" 
                                name="sku" 
                                value="{{ old('sku', $product->sku) }}" 
                                class="erp-input font-numeric @error('sku') erp-input-error @enderror" 
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="productCategory" class="erp-form-label">
                                التصنيف <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select 
                                id="productCategory" 
                                name="category_id" 
                                class="erp-input @error('category_id') erp-input-error @enderror" 
                                required
                            >
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)old('category_id', $product->category_id) === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Unit and Packaging -->
                    <div class="erp-form-row-2col">
                        <div class="erp-form-group">
                            <label for="productUnit" class="erp-form-label">
                                وحدة القياس <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select id="productUnit" name="unit" class="erp-input">
                                @if(isset($units) && $units->isNotEmpty())
                                    @foreach($units as $u)
                                        <option value="{{ $u->name }}" data-factor="{{ (int) round((float) $u->conversion_factor) }}" {{ old('unit', $product->unit) === $u->name ? 'selected' : '' }}>
                                            {{ $u->name }} @if((float)$u->conversion_factor > 1)({{ (int)round((float)$u->conversion_factor) }} قطعة)@endif
                                        </option>
                                    @endforeach
                                @else
                                    <option value="قطعة" {{ old('unit', $product->unit) === 'قطعة' ? 'selected' : '' }}>قطعة (Piece)</option>
                                    <option value="دستة" {{ old('unit', $product->unit) === 'دستة' ? 'selected' : '' }}>دستة (Dozen - 12 قطعة)</option>
                                    <option value="كرتونة" {{ old('unit', $product->unit) === 'كرتونة' ? 'selected' : '' }}>كرتونة (Carton)</option>
                                @endif
                            </select>
                        </div>

                        <div class="erp-form-group">
                            <label for="piecesPerPackage" class="erp-form-label">
                                عدد القطع في الطرد / الكرتونة
                            </label>
                            <input 
                                type="number" 
                                min="1" 
                                id="piecesPerPackage" 
                                name="pieces_per_package" 
                                value="{{ old('pieces_per_package', $product->pieces_per_package) }}" 
                                class="erp-input font-numeric @error('pieces_per_package') erp-input-error @enderror" 
                                placeholder="مثال: 12 أو 24"
                            >
                            <small class="text-[11px] text-[var(--text-muted)]">اختياري — لحساب تكلفة وعدد الطرود</small>
                        </div>
                    </div>

                    <!-- Barcode Section with Camera Scanner Integration -->
                    <div class="erp-form-group">
                        <div class="erp-form-label flex items-center justify-between flex-wrap gap-1.5">
                            <label for="productBarcode" class="m-0 cursor-pointer">الباركود (Barcode)</label>
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" data-action="open-barcode-scanner" data-target-input="productBarcode" class="erp-btn erp-btn-secondary text-[11px] py-1 px-2 text-[var(--primary)] inline-flex items-center gap-1" title="تشغيل الكاميرا لمسح باركود الصنف مباشرة">
                                    <x-icon name="camera" size="14" />
                                    <span>مسح بالكاميرا</span>
                                </button>
                                <button type="button" data-action="generate-barcode" class="erp-btn erp-btn-ghost font-numeric text-[11px] py-0.5 px-2 text-[var(--text-muted)] inline-flex items-center gap-1">
                                    <x-icon name="zap" size="13" />
                                    <span>توليد كود</span>
                                </button>
                            </div>
                        </div>
                        <div class="relative flex items-center">
                            <input 
                                type="text" 
                                id="productBarcode" 
                                name="barcode" 
                                value="{{ old('barcode', $product->barcode) }}" 
                                class="erp-input font-numeric pl-11 @error('barcode') erp-input-error @enderror" 
                                placeholder="امسح بالكاميرا أو اكتب الباركود..."
                            >
                            <button 
                                type="button" 
                                data-action="open-barcode-scanner" 
                                data-target-input="productBarcode"
                                title="تشغيل الكاميرا لمسح الباركود"
                                class="absolute left-1.5 h-7 w-8 border-0 bg-[var(--primary-soft)] text-[var(--primary)] rounded inline-flex items-center justify-center cursor-pointer transition-all">
                                <x-icon name="camera" size="16" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Pricing & Inventory Control -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="banknote" size="18" />
                        <span>التسعير وإعدادات المخزون</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-4">
                    <!-- Prices 4-Column Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="erp-form-group">
                            <label for="costPrice" class="erp-form-label">
                                سعر التكلفة <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="costPrice" 
                                name="cost_price" 
                                value="{{ old('cost_price', $product->cost_price) }}" 
                                class="erp-input font-numeric @error('cost_price') erp-input-error @enderror" 
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="retailPrice" class="erp-form-label">
                                سعر التجزئة (القطاعي) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="retailPrice" 
                                name="retail_price" 
                                value="{{ old('retail_price', $product->retail_price) }}" 
                                class="erp-input font-numeric @error('retail_price') erp-input-error @enderror" 
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="wholesalePrice" class="erp-form-label">
                                سعر الجملة <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="wholesalePrice" 
                                name="wholesale_price" 
                                value="{{ old('wholesale_price', $product->wholesale_price) }}" 
                                class="erp-input font-numeric @error('wholesale_price') erp-input-error @enderror" 
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="distributorPrice" class="erp-form-label">
                                سعر الموزع (اختياري)
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                id="distributorPrice" 
                                name="distributor_price" 
                                value="{{ old('distributor_price', $product->distributor_price) }}" 
                                class="erp-input font-numeric @error('distributor_price') erp-input-error @enderror" 
                                placeholder="0.00"
                            >
                        </div>
                    </div>

                    <!-- Min Stock Alert -->
                    <div class="erp-form-group">
                        <label for="minStockAlert" class="erp-form-label">
                            حد الطلب الأدنى (تنبيه النواقص) <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="number" 
                            min="0" 
                            id="minStockAlert" 
                            name="min_stock_alert" 
                            value="{{ old('min_stock_alert', $product->min_stock_alert) }}" 
                            class="erp-input font-numeric @error('min_stock_alert') erp-input-error @enderror" 
                            required
                        >
                    </div>

                    <!-- Product Image -->
                    <div class="erp-form-group">
                        <label for="productImage" class="erp-form-label">صورة المنتج</label>
                        @if($product->image_path)
                            <div class="mb-2">
                                <small class="block text-[var(--text-muted)] text-[11px] mb-1">الصورة الحالية:</small>
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="max-h-20 rounded border border-[var(--border-color)] object-contain">
                            </div>
                        @endif
                        <input 
                            type="file" 
                            id="productImage" 
                            name="product_image" 
                            accept="image/png,image/jpeg,image/webp" 
                            class="erp-input @error('product_image') erp-input-error @enderror"
                        >
                        <div id="imagePreviewContainer" class="mt-2.5 hidden">
                            <small class="block text-[var(--text-muted)] mb-1">معاينة الصورة الجديدة:</small>
                            <img id="imagePreview" src="" alt="معاينة جديدة" class="max-h-20 rounded border border-[var(--border-color)] object-contain">
                        </div>
                    </div>

                    <!-- Active Status Toggle -->
                    <div class="mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="is_active" 
                                value="1" 
                                {{ old('is_active', $product->is_active) ? 'checked' : '' }} 
                                class="w-4 h-4 accent-[var(--primary)]"
                            >
                            <span class="text-xs font-semibold text-[var(--text-main)]">تفعيل الصنف وإتاحته في النظام</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Panel 3: Opening Stock Balance Summary / Edit -->
            <div class="erp-card col-span-full">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title">
                        <x-icon name="layers" size="18" />
                        <span>بيانات الرصيد الافتتاحي</span>
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <span class="erp-badge erp-badge-warning text-[11px] flex items-center gap-1">
                            <x-icon name="shield-check" size="14" />
                            <span>متاح للتعديل — صلاحية خاصة بمدير النظام</span>
                        </span>
                    @else
                        <span class="erp-badge erp-badge-neutral text-[11px]">مغلق ضد التعديل حفاظاً على سلامة المخزون</span>
                    @endif
                </div>
                <div class="erp-card-body">
                    @if(auth()->user()?->isAdmin())
                        <p class="text-xs text-[var(--text-muted)] mb-4">
                            بصفتك مدير النظام، يمكنك تعديل كمية وتكلفة ومستودع وتاريخ الرصيد الافتتاحي لهذا الصنف مباشرة. سيتم تحديث أرصدة المخزون وحركات الجرد آلياً.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Opening Quantity -->
                            <div class="erp-form-group">
                                <label for="openingQuantity" class="erp-form-label">
                                    الكمية الافتتاحية <span id="openingUnitLabel" class="text-[var(--primary)] text-xs font-semibold"></span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    id="openingQuantity" 
                                    name="opening_quantity" 
                                    value="{{ old('opening_quantity', $product->opening_quantity ?? '0.00') }}" 
                                    class="erp-input font-numeric @error('opening_quantity') erp-input-error @enderror"
                                >
                                <div id="openingQtyBreakdown" class="text-[11px] font-semibold text-[var(--primary)] mt-1 hidden"></div>
                                <small class="text-[10px] text-[var(--text-muted)]">اتركه 0 إذا لم يتوفر رصيد أولي</small>
                            </div>

                            <!-- Opening Cost -->
                            <div class="erp-form-group">
                                <label for="openingCost" class="erp-form-label">
                                    تكلفة الوحدة للرصيد الافتتاحي <span id="openingCostUnitLabel" class="text-[var(--text-muted)] text-[11px]"></span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    id="openingCost" 
                                    name="opening_cost" 
                                    value="{{ old('opening_cost', $product->opening_cost ?? $product->cost_price) }}" 
                                    class="erp-input font-numeric @error('opening_cost') erp-input-error @enderror" 
                                    placeholder="فارغ = نفس سعر التكلفة"
                                >
                                <div id="openingCostBreakdown" class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1 hidden"></div>
                                <small class="text-[10px] text-[var(--text-muted)]">تكلفة الوحدة المحددة (ج.م) للرصيد الأولي</small>
                            </div>

                            <!-- Opening Warehouse -->
                            <div class="erp-form-group">
                                <label for="openingWarehouse" class="erp-form-label">
                                    المستودع المستلم
                                </label>
                                <select 
                                    id="openingWarehouse" 
                                    name="opening_warehouse_id" 
                                    class="erp-input @error('opening_warehouse_id') erp-input-error @enderror"
                                >
                                    <option value="">-- اختر المستودع --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (string)old('opening_warehouse_id', $product->opening_warehouse_id ?? '') === (string)$wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} {{ $wh->is_default ? '(الافتراضي)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-[10px] text-[var(--text-muted)]">المستودع المودع به الرصيد الافتتاحي</small>
                            </div>

                            <!-- Opening Date -->
                            <div class="erp-form-group">
                                <label for="openingBalanceDate" class="erp-form-label">
                                    تاريخ إثبات الرصيد
                                </label>
                                <input 
                                    type="date" 
                                    id="openingBalanceDate" 
                                    name="opening_balance_date" 
                                    value="{{ old('opening_balance_date', ($product->opening_balance_date && $product->opening_balance_date->year >= 2000) ? $product->opening_balance_date->format('Y-m-d') : ($product->created_at ? $product->created_at->format('Y-m-d') : date('Y-m-d'))) }}" 
                                    class="erp-input font-numeric @error('opening_balance_date') erp-input-error @enderror"
                                >
                                <small class="text-[10px] text-[var(--text-muted)]">تاريخ قيد الرصيد بالدفاتر</small>
                            </div>
                        </div>
                    @else
                        @if($product->opening_quantity > 0)
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="erp-summary-item">
                                    <span class="text-xs text-[var(--text-muted)] block mb-1">الكمية الافتتاحية:</span>
                                    <span class="font-bold font-numeric text-base text-[var(--text-main)]">{{ number_format($product->opening_quantity, 2) }} {{ $product->unit }}</span>
                                </div>
                                <div class="erp-summary-item">
                                    <span class="text-xs text-[var(--text-muted)] block mb-1">تكلفة الوحدة عند البدء:</span>
                                    <span class="font-bold font-numeric text-base text-[var(--text-main)]">{{ number_format($product->opening_cost ?? $product->cost_price, 2) }} ج.م</span>
                                </div>
                                <div class="erp-summary-item">
                                    <span class="text-xs text-[var(--text-muted)] block mb-1">مستودع الرصيد الافتتاحي:</span>
                                    <span class="font-bold text-sm text-[var(--primary)]">{{ $product->openingWarehouse->name ?? 'غير محدد' }}</span>
                                </div>
                                <div class="erp-summary-item">
                                    <span class="text-xs text-[var(--text-muted)] block mb-1">تاريخ إثبات الرصيد:</span>
                                    <span class="font-bold font-numeric text-sm text-[var(--text-main)]">{{ ($product->opening_balance_date && $product->opening_balance_date->year >= 2000) ? $product->opening_balance_date->format('Y-m-d') : 'غير مسجل' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-xs text-[var(--text-muted)] p-3 rounded bg-[var(--surface-muted)]">
                                لم يتم تسجيل رصيد افتتاحي لهذا الصنف عند إنشائه. يتم قيد الأرصدة عبر أوامر الشراء أو التحويلات أو التسويات الجردية.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </form>

    <iframe id="editPrintIframe" class="hidden"></iframe>
    <x-barcode-scanner-modal target-input-id="productBarcode" />
@endsection
