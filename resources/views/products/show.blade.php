@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="{{ $product->name }}" :breadcrumbs="['المنتجات والأصناف' => route('products.index'), $product->sku => '#']">
        <x-slot:actions>
            <div class="erp-show-print-group">
                <button type="button" data-action="direct-print-show" class="erp-btn erp-btn-secondary text-[var(--primary)] flex-1 justify-center" title="طباعة فورية للملصق ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم) بدون نوافذ">
                    <x-icon name="printer" size="16" />
                    <span>طباعة ملصق الباركود</span>
                </button>
                <div class="inline-flex items-center bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-sm p-0.5 shrink-0" title="عدد النسخ المطلوبة">
                    <button type="button" data-action="adjust-show-qty" data-delta="-1" class="erp-btn erp-btn-ghost font-numeric py-0.5 px-2 font-bold text-sm">−</button>
                    <input type="number" id="showLabelQty" value="1" min="1" max="500" class="font-numeric w-9 text-center p-0.5 font-bold border-0 bg-transparent text-[var(--text-main)]" title="عدد النسخ">
                    <button type="button" data-action="adjust-show-qty" data-delta="1" class="erp-btn erp-btn-ghost font-numeric py-0.5 px-2 font-bold text-sm">+</button>
                </div>
            </div>
            <a href="{{ route('products.ledger.print', $product) }}" class="erp-btn erp-btn-secondary" target="_blank" title="طباعة كشف حركة الصنف">
                <x-icon name="printer" size="16" />
                <span>طباعة حركة الصنف</span>
            </a>
            <a href="{{ route('products.ledger', $product) }}" class="erp-btn erp-btn-secondary" title="كشف حركة الصنف التفصيلي والرصيد التراكمي">
                <x-icon name="activity" size="16" />
                <span>كشف حركة الصنف</span>
            </a>
            <a href="{{ route('products.edit', $product) }}" class="erp-btn erp-btn-primary">
                <x-icon name="pencil" size="16" />
                <span>تعديل الصنف</span>
            </a>
            <form action="{{ route('products.toggle-status', $product) }}" method="POST" class="inline" data-confirm="هل تريد {{ $product->is_active ? 'تعطيل' : 'تنشيط' }} هذا الصنف؟">
                @csrf
                <button type="submit" class="erp-btn {{ $product->is_active ? 'erp-btn-warning' : 'erp-btn-success' }}">
                    @if($product->is_active)
                        <x-icon name="lock" size="16" />
                        <span>تعطيل الصنف</span>
                    @else
                        <x-icon name="check-circle" size="16" />
                        <span>تنشيط الصنف</span>
                    @endif
                </button>
            </form>
            <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left" size="16" />
                <span>العودة للقائمة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($product->is_active)
                <span class="erp-badge erp-badge-success py-1 px-2.5 text-xs">
                    <x-icon name="check" size="12" />
                    <span>الحالة: نشط</span>
                </span>
            @else
                <span class="erp-badge erp-badge-danger py-1 px-2.5 text-xs">
                    <x-icon name="x" size="12" />
                    <span>الحالة: معطل</span>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="products-show" data-product-id="{{ $product->id }}">
        <div class="erp-product-show-grid">
            <!-- Side Column: Product Image, Barcode & Management Actions -->
            <div class="flex flex-col gap-4">
                <!-- 1. Dedicated Product Image Card -->
                <div class="erp-card">
                    <div class="erp-card-header flex items-center justify-between">
                        <div class="erp-card-title">
                            <x-icon name="image" size="18" />
                            <span>صورة المنتج</span>
                        </div>
                        @if($product->image_url)
                            <a href="{{ $product->image_url }}" target="_blank" class="erp-btn erp-btn-ghost py-1 px-2 text-xs text-[var(--primary)]" title="عرض الصورة بالحجم الكامل">
                                <x-icon name="external-link" size="13" />
                                <span>تكبير</span>
                            </a>
                        @endif
                    </div>
                    <div class="erp-card-body p-4 flex flex-col items-center justify-center">
                        <div class="erp-product-image-frame">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="erp-product-image">
                            @else
                                <div class="erp-product-image-empty">
                                    <x-icon name="package" size="44" class="opacity-35" />
                                    <small class="text-[var(--text-muted)] mt-2">لا توجد صورة مسجلة للمنتج</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 2. Thermal Barcode Label Card -->
                <div class="erp-card">
                    <div class="erp-card-header">
                        <div class="erp-card-title">
                            <x-icon name="barcode" size="18" />
                            <span>ملصق الباركود ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم)</span>
                        </div>
                    </div>
                    <div class="erp-card-body p-4 text-center flex flex-col items-center">
                        <div class="erp-barcode-label-box">
                            {!! $completeLabelSvg !!}
                        </div>

                        <div class="mt-3 w-full flex flex-col gap-2">
                            <div class="flex items-center justify-between bg-[var(--bg-surface-alt)] p-1.5 rounded border border-[var(--border-color)]">
                                <span class="text-xs font-semibold text-[var(--text-muted)] mr-1">عدد النسخ:</span>
                                <div class="inline-flex items-center bg-[var(--bg-surface)] border border-[var(--border-color)] rounded p-0.5">
                                    <button type="button" data-action="adjust-show-qty" data-delta="-1" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 font-bold text-sm" title="إنقاص الكمية">−</button>
                                    <input type="number" id="cardLabelQty" value="1" min="1" max="500" class="font-numeric w-12 text-center p-1 font-bold border-0 bg-transparent text-[var(--text-main)] text-sm" title="عدد النسخ">
                                    <button type="button" data-action="adjust-show-qty" data-delta="1" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 font-bold text-sm" title="زيادة الكمية">+</button>
                                </div>
                            </div>
                            <button type="button" data-action="direct-print-show" class="erp-btn erp-btn-primary w-full justify-center text-sm" title="طباعة فورية على الطابعة بدون نوافذ">
                                <x-icon name="printer" size="16" />
                                <span>طباعة فورية على الطابعة</span>
                            </button>
                            <button type="button" data-action="open-print-modal" class="erp-btn erp-btn-secondary w-full justify-center text-xs" title="تخصيص عدد النسخ وخيارات الملصق">
                                <x-icon name="sliders" size="14" />
                                <span>تخصيص الكميات والخيارات</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Delete Action Card -->
                <div class="erp-card p-3">
                    <form action="{{ route('products.destroy', $product) }}" method="POST" data-confirm="تأكيد حذف المنتج نهائياً من النظام؟">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="erp-btn erp-btn-ghost w-full text-[var(--danger)] justify-center text-xs">
                            <x-icon name="trash" size="14" />
                            <span>حذف هذا المنتج</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Product Specifications & Commercial Data -->
            <div class="flex flex-col gap-4">
                <!-- Specs Card -->
                <div class="erp-card">
                    <div class="erp-card-header">
                        <div class="erp-card-title">
                            <x-icon name="clipboard" size="18" />
                            <span>المواصفات والبيانات التجارية</span>
                        </div>
                        <span class="font-numeric font-bold text-[var(--primary)] text-sm">
                            {{ $product->sku }}
                        </span>
                    </div>
                    <div class="erp-card-body">
                        <div class="erp-specs-grid">
                            <div class="erp-specs-item">
                                <span class="erp-specs-label">اسم المنتج:</span>
                                <strong class="erp-specs-value text-sm">{{ $product->name }}</strong>
                            </div>

                            <div class="erp-specs-item">
                                <span class="erp-specs-label">التصنيف:</span>
                                <span class="erp-badge erp-badge-neutral mt-1">
                                    {{ $product->category?->name ?? 'غير محدد' }}
                                </span>
                            </div>

                            <div class="erp-specs-item">
                                <span class="erp-specs-label">وحدة القياس:</span>
                                <strong class="erp-specs-value">{{ $product->unit }}</strong>
                            </div>

                            <div class="erp-specs-item">
                                <span class="erp-specs-label">التعبئة في الطرد:</span>
                                <strong class="erp-specs-value">{{ $product->pieces_per_package ? $product->pieces_per_package.' قطعة' : 'غير محدد' }}</strong>
                            </div>

                            <div class="erp-specs-item">
                                <span class="erp-specs-label">حد تنبيه النواقص:</span>
                                <span class="font-numeric erp-specs-value text-[var(--warning)]">{{ $product->min_stock_alert }} {{ $product->unit }}</span>
                            </div>

                            <div class="erp-specs-item">
                                <span class="erp-specs-label">إجمالي الرصيد المخزني:</span>
                                <span class="font-numeric font-bold erp-specs-value text-[var(--primary)]">{{ $product->formatStockBreakdown() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Profitability Breakdown -->
                <div class="erp-card">
                    <div class="erp-card-header">
                        <div class="erp-card-title">
                            <x-icon name="banknote" size="18" />
                            <span>هيكل التسعير وهامش الربح</span>
                        </div>
                    </div>
                    <div class="erp-card-body">
                        <div class="erp-pricing-grid">
                            <!-- Cost Price -->
                            <div class="erp-pricing-card">
                                <span class="erp-pricing-label">سعر الشراء</span>
                                <div class="font-numeric erp-pricing-value">
                                    {{ number_format((float)$product->cost_price, 2) }}
                                    <span class="erp-pricing-unit">ج.م</span>
                                </div>
                            </div>

                            <!-- Retail Price -->
                            <div class="erp-pricing-card erp-pricing-card-retail">
                                <span class="erp-pricing-label">سعر بيع التجزئة (القطاعي)</span>
                                <div class="font-numeric erp-pricing-value">
                                    {{ number_format((float)$product->retail_price, 2) }}
                                    <span class="erp-pricing-unit">ج.م</span>
                                </div>
                                @php
                                    $retailProfit = (float)$product->retail_price - (float)$product->cost_price;
                                    $retailMargin = (float)$product->cost_price > 0 ? ($retailProfit / (float)$product->cost_price) * 100 : 0;
                                @endphp
                                <small class="font-numeric block mt-1 text-[11px] text-[var(--danger-text)]">
                                    هامش الربح: +{{ number_format($retailProfit, 2) }} ج.م ({{ number_format($retailMargin, 1) }}%)
                                </small>
                            </div>

                            <!-- Wholesale Price -->
                            <div class="erp-pricing-card erp-pricing-card-highlight erp-pricing-card-wholesale">
                                <span class="erp-pricing-label">سعر الجملة لتجار الإكسسوارات</span>
                                <div class="font-numeric erp-pricing-value">
                                    {{ number_format((float)$product->wholesale_price, 2) }}
                                    <span class="erp-pricing-unit">ج.م</span>
                                </div>
                                @php
                                    $wholesaleProfit = (float)$product->wholesale_price - (float)$product->cost_price;
                                    $wholesaleMargin = (float)$product->cost_price > 0 ? ($wholesaleProfit / (float)$product->cost_price) * 100 : 0;
                                @endphp
                                <small class="font-numeric block mt-1 text-[11px] text-[var(--success-text)]">
                                    هامش الربح: +{{ number_format($wholesaleProfit, 2) }} ج.م ({{ number_format($wholesaleMargin, 1) }}%)
                                </small>
                            </div>

                            <!-- Distributor Price -->
                            <div class="erp-pricing-card erp-pricing-card-distributor">
                                <span class="erp-pricing-label">سعر الموزع والوكيل</span>
                                <div class="font-numeric erp-pricing-value">
                                    @if($product->distributor_price)
                                        {{ number_format((float)$product->distributor_price, 2) }}
                                        <span class="erp-pricing-unit">ج.م</span>
                                    @else
                                        <span class="text-sm font-normal text-[var(--text-muted)]">—</span>
                                    @endif
                                </div>
                                @if($product->distributor_price)
                                    @php
                                        $distributorProfit = (float)$product->distributor_price - (float)$product->cost_price;
                                        $distributorMargin = (float)$product->cost_price > 0 ? ($distributorProfit / (float)$product->cost_price) * 100 : 0;
                                    @endphp
                                    <small class="font-numeric block mt-1 text-[11px] text-[var(--info-text)]">
                                        هامش الربح: +{{ number_format($distributorProfit, 2) }} ج.م ({{ number_format($distributorMargin, 1) }}%)
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Stock Movements Overview Card -->
                <div class="erp-card">
                    <div class="erp-card-header flex items-center justify-between">
                        <div class="erp-card-title">
                            <x-icon name="activity" size="18" />
                            <span>سجل حركة المخزون للصنف</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('products.ledger.print', $product) }}" class="erp-btn erp-btn-secondary py-1 px-2.5 text-xs" target="_blank" title="طباعة كشف حركة الصنف">
                                <x-icon name="printer" size="14" />
                                <span>طباعة حركة الصنف</span>
                            </a>
                            <a href="{{ route('products.ledger', $product) }}" class="erp-btn erp-btn-ghost py-1 px-2.5 text-xs text-[var(--primary)]" title="عرض كامل كشف الحركة مع الفلاتر">
                                <span>عرض كامل السجل</span>
                                <x-icon name="arrow-left" size="14" />
                            </a>
                        </div>
                    </div>
                    <div class="erp-card-body p-0">
                        <div class="erp-table-responsive">
                            <table class="erp-table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>نوع الحركة</th>
                                        <th>المستودع</th>
                                        <th class="text-center">الكمية</th>
                                        <th class="text-center">الرصيد بعد الحركة</th>
                                        <th>البيان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentMovements as $rm)
                                        <tr>
                                            <td class="font-numeric text-xs">{{ $rm->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @if($rm->reference_type === 'opening_balance')
                                                    <span class="erp-badge erp-badge-info text-[10px]">رصيد افتتاحي</span>
                                                @else
                                                    <span class="erp-badge {{ $rm->type_badge_class }} text-[10px]">{{ $rm->type_label }}</span>
                                                @endif
                                            </td>
                                            <td class="text-xs">{{ $rm->toWarehouse->name ?? $rm->fromWarehouse->name ?? '—' }}</td>
                                            <td class="font-numeric text-center text-xs font-bold">
                                                @if($rm->type === 'in' || $rm->reference_type === 'opening_balance')
                                                    <span class="text-[var(--success-text)]">+{{ number_format((float)$rm->quantity, 2) }}</span>
                                                @elseif($rm->type === 'out')
                                                    <span class="text-[var(--danger)]">-{{ number_format((float)$rm->quantity, 2) }}</span>
                                                @else
                                                    <span>{{ number_format((float)$rm->quantity, 2) }}</span>
                                                @endif
                                                <small class="text-[10px] text-[var(--text-muted)]">قطعة</small>
                                            </td>
                                            <td class="font-numeric text-center text-xs font-bold text-[var(--primary)]">
                                                {{ $rm->running_balance !== null ? $product->formatStockBreakdown((float)$rm->running_balance) : '—' }}
                                            </td>
                                            <td class="text-xs text-[var(--text-muted)]">{{ $rm->notes ?: ($rm->reference_type ? $rm->reference_type.' #'.$rm->reference_id : '—') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-6 text-xs text-[var(--text-muted)]">
                                                لا توجد حركات مسجلة لهذا الصنف حتى الآن.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Barcode Modal (38x15mm Xprinter XP-233B) -->
        <div id="printBarcodeModal" class="erp-modal-overlay hidden">
            <div class="erp-card erp-modal-container max-w-[480px]">
                <!-- Modal Header -->
                <div class="erp-card-header erp-modal-header">
                    <div class="erp-card-title erp-modal-title">
                        <x-icon name="printer" size="18" />
                        <span>طباعة ملصق الباركود الحراري</span>
                    </div>
                    <button type="button" data-action="close-print-modal" class="erp-btn erp-btn-ghost erp-btn-icon erp-modal-close" title="إغلاق">
                        <x-icon name="x" size="16" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="erp-card-body p-5">
                    <!-- Physical Label Live Preview (38x15mm ratio) -->
                    <div class="text-center mb-5">
                        <div class="inline-block bg-[#f8fafc] border border-dashed border-[var(--border-color)] rounded-lg p-4">
                            <small class="block text-[11px] text-[var(--text-muted)] mb-2">
                                معاينة الملصق بمقاس <strong>38×15 مم</strong> (Xprinter XP-233B):
                            </small>
                            <div class="w-[260px] h-[102px] bg-white border border-[#cbd5e1] rounded shadow-sm py-1.5 px-2.5 flex flex-col justify-between items-center mx-auto">
                                <!-- Product Name -->
                                <div class="text-[11px] font-bold text-black truncate w-full text-center font-['Cairo']">
                                    {{ $product->name }}
                                </div>
                                <!-- Barcode SVG -->
                                <div class="w-[90%] h-[50px] flex items-center justify-center my-0.5">
                                    {!! $barcodeSvg !!}
                                </div>
                                <!-- ITEM-NO: SKU -->
                                <div class="text-[9.5px] font-bold text-black tracking-wide font-mono text-center">
                                    ITEM-NO: {{ $product->sku }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selection -->
                    <div class="mb-4">
                        <label for="labelQuantity" class="block text-xs font-semibold text-[var(--text-main)] mb-2">
                            عدد الملصقات المطلوب طباعتها:
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" data-action="adjust-modal-qty" data-delta="-1" class="erp-btn erp-btn-secondary py-1.5 px-3.5 text-base font-bold">−</button>
                            <input type="number" id="labelQuantity" value="1" min="1" max="500" class="erp-input font-numeric text-center text-base font-bold w-[100px]">
                            <button type="button" data-action="adjust-modal-qty" data-delta="1" class="erp-btn erp-btn-secondary py-1.5 px-3.5 text-base font-bold">+</button>
                            
                            <!-- Quick Quantity Pills -->
                            <div class="flex gap-1 mr-2 flex-wrap">
                                <button type="button" data-action="set-modal-qty" data-qty="1" class="erp-btn erp-btn-ghost font-numeric py-1 px-2 text-[11px]">1</button>
                                <button type="button" data-action="set-modal-qty" data-qty="5" class="erp-btn erp-btn-ghost font-numeric py-1 px-2 text-[11px]">5</button>
                                <button type="button" data-action="set-modal-qty" data-qty="10" class="erp-btn erp-btn-ghost font-numeric py-1 px-2 text-[11px]">10</button>
                                <button type="button" data-action="set-modal-qty" data-qty="20" class="erp-btn erp-btn-ghost font-numeric py-1 px-2 text-[11px]">20</button>
                                <button type="button" data-action="set-modal-qty" data-qty="50" class="erp-btn erp-btn-ghost font-numeric py-1 px-2 text-[11px]">50</button>
                            </div>
                        </div>
                    </div>

                    <!-- Notice -->
                    <div class="text-[11.5px] text-[var(--text-muted)] bg-[var(--bg-surface-alt)] py-2 px-3 rounded border border-[var(--border-color)] mb-5">
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="info" size="14" />
                            <span>يتم إرسال أمر الطباعة بمقاس 38×15 مم المخصص لطابعات الباركود الحرارية (Xprinter XP-233B).</span>
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 justify-end">
                        <button type="button" data-action="close-print-modal" class="erp-btn erp-btn-secondary">
                            <span>إلغاء</span>
                        </button>
                        <button type="button" data-action="submit-print-preview" data-auto-print="true" class="erp-btn erp-btn-primary" title="طباعة فورية مباشرة على طابعة الباركود بدون نوافذ">
                            <x-icon name="printer" size="16" />
                            <span>طباعة فورية للطابعة</span>
                        </button>
                        <button type="button" data-action="submit-print-preview" data-auto-print="false" class="erp-btn erp-btn-secondary" title="فتح صفحة المعاينة في نافذة جديدة">
                            <x-icon name="eye" size="16" />
                            <span>معاينة في المتصفح</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification for Direct Print Feedback -->
        <div id="printNotificationToast" class="erp-toast hidden">
            <span class="erp-toast-icon"><x-icon name="printer" size="18" /></span>
            <span id="printToastMessage" class="erp-toast-message">جاري إرسال الملصق للطباعة...</span>
        </div>
    </div>
@endsection
