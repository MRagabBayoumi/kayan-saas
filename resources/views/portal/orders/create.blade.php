@extends('layouts.portal', ['title' => 'طلب بيع جديد'])

@section('content')
<div class="space-y-4" 
     id="portalOrderApp"
     data-products="{{ $productsJson }}"
     data-selected-customer="{{ $selectedCustomerId ?? '' }}"
     data-selected-product="{{ $selectedProductId ?? '' }}"
     data-is-admin="{{ $isAdmin ? '1' : '0' }}"
     data-commission-rate="{{ (float) (auth()->user()->commission_rate ?? 0) }}"
     data-commission-type="{{ auth()->user()->commission_type ?? 'percentage' }}"
     x-data="portalOrderForm()">

    <!-- Header Card (Kayan ERP Odoo Card) -->
    <div class="erp-card mb-0 p-4 flex items-center justify-between">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="plus-circle" size="18" class="text-[var(--primary)]" />
                <span>إنشاء طلب بيع ميداني</span>
            </h1>
            <p class="text-xs text-[var(--text-muted)] mt-0.5">تسجيل فاتورة أو مسودة طلب لعميلك واحتساب العمولة آلياً</p>
        </div>
        <a href="{{ route('portal.orders.index') }}" class="erp-btn erp-btn-secondary erp-btn-sm text-xs">
            <x-icon name="arrow-right" size="13" />
            <span>رجوع للسجل</span>
        </a>
    </div>

    <form method="POST" action="{{ route('portal.orders.store') }}" @submit="validateAndSubmit($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <!-- Left 2 Columns: Customer Info & Items List -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Customer & Warehouse Card -->
                <div class="erp-card mb-0 p-4 sm:p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="erp-form-label font-bold text-xs">
                            العميل <span class="required">*</span>
                        </label>
                        <a href="{{ route('portal.customers.create') }}" class="text-xs font-bold text-[var(--primary)] hover:underline flex items-center gap-1">
                            <x-icon name="user-plus" size="13" />
                            <span>تسجيل عميل جديد</span>
                        </a>
                    </div>

                    @if($customers->isEmpty())
                        <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2">
                                <x-icon name="alert-triangle" size="16" class="text-amber-600 shrink-0" />
                                <span>لا يوجد عملاء تابعين لك مسجلين حتى الآن. يرجى تسجيل بيانات العميل أولاً للمتابعة.</span>
                            </div>
                            <a href="{{ route('portal.customers.create') }}" class="erp-btn erp-btn-sm erp-btn-primary shrink-0 text-xs w-full sm:w-auto justify-center">
                                <span>+ تسجيل عميل</span>
                            </a>
                        </div>
                    @endif

                    <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                        class="erp-select text-xs sm:text-sm">
                        <option value="">-- اختر العميل من القائمة --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-tier="{{ $c->pricing_tier }}" {{ (string)$selectedCustomerId === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->phone }})
                            </option>
                        @endforeach
                    </select>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="erp-form-label font-bold text-xs mb-1">
                                المستودع <span class="required">*</span>
                            </label>
                            <select name="warehouse_id" required class="erp-select text-xs sm:text-sm">
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="erp-form-label font-bold text-xs mb-1">تاريخ الفاتورة</label>
                            <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required
                                class="erp-input text-xs sm:text-sm font-numeric">
                        </div>
                    </div>

                    <!-- Price Tier Switcher (Admin Only) or Locked to Wholesale (Sales Rep) -->
                    @if($isAdmin)
                        <div class="pt-1">
                            <label class="erp-form-label font-bold text-xs mb-1.5">فئة السعر المطبقة للطلب (صلاحية مدير)</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                    :class="priceTier === 'retail' ? 'bg-[var(--primary-soft)] border-[var(--primary)] text-[var(--primary)]' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                    <input type="radio" name="price_tier" value="retail" x-model="priceTier" @change="recalculateItemPrices()" class="sr-only">
                                    <span>قطاعي</span>
                                </label>
                                <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                    :class="priceTier === 'wholesale' ? 'bg-[var(--primary-soft)] border-[var(--primary)] text-[var(--primary)]' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                    <input type="radio" name="price_tier" value="wholesale" x-model="priceTier" @change="recalculateItemPrices()" class="sr-only">
                                    <span>جملة</span>
                                </label>
                                <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                    :class="priceTier === 'distributor' ? 'bg-[var(--primary-soft)] border-[var(--primary)] text-[var(--primary)]' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                    <input type="radio" name="price_tier" value="distributor" x-model="priceTier" @change="recalculateItemPrices()" class="sr-only">
                                    <span>موزع</span>
                                </label>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="price_tier" value="wholesale">
                        <div class="pt-1">
                            <div class="p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200 text-xs flex items-center justify-between">
                                <span class="font-bold text-emerald-950">فئة السعر المعتمدة للمندوب:</span>
                                <span class="erp-badge erp-badge-success text-[11px] font-bold">سعر الجملة</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Add Items Section -->
                <div class="erp-card mb-0 p-4 sm:p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2 border-b border-[var(--border-light)]">
                        <div class="flex items-center justify-between w-full sm:w-auto">
                            <h2 class="erp-card-title text-xs sm:text-sm">
                                <x-icon name="shopping-bag" size="16" class="text-[var(--primary)]" />
                                <span>بنود وأصناف الطلب</span>
                                <span class="erp-badge erp-badge-neutral text-[10px] font-numeric" x-text="items.length + ' أصناف'"></span>
                            </h2>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <!-- Hidden input receiving the scanned barcode -->
                            <input type="hidden" id="portalOrderBarcodeInput">

                            <button type="button" data-action="open-barcode-scanner" data-target-input="portalOrderBarcodeInput" class="erp-btn erp-btn-secondary erp-btn-sm text-xs flex-1 sm:flex-none justify-center">
                                <x-icon name="camera" size="14" class="text-[var(--primary)]" />
                                <span>مسح باركود</span>
                            </button>

                            <button type="button" @click="showProductPicker = true" class="erp-btn erp-btn-primary erp-btn-sm text-xs flex-1 sm:flex-none justify-center">
                                <x-icon name="plus" size="14" />
                                <span>إضافة صنف</span>
                            </button>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-2.5">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-3 sm:p-3.5 bg-[var(--bg-hover)] rounded border border-[var(--border-color)] space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-xs sm:text-sm font-bold text-[var(--text-main)] truncate" x-text="item.name"></div>
                                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                            <div class="text-[11px] text-[var(--text-muted)] font-numeric" x-text="item.stock_display || (item.is_available ? 'متوفر للطلب' : 'غير متوفر')"></div>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                                  x-text="'الوحدة: ' + (item.unit || 'قطعة') + (item.pieces_per_package > 1 ? ' (' + item.pieces_per_package + ' قطعة)' : '')"></span>
                                        </div>
                                    </div>
                                    <button type="button" @click="removeItem(index)" class="p-1 text-slate-400 hover:text-rose-600 transition-colors" title="حذف البند">
                                        <x-icon name="trash-2" size="15" />
                                    </button>
                                </div>

                                <!-- Quantity & Price controls -->
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <!-- Quantity with +/- buttons -->
                                    <div class="col-span-5 sm:col-span-4 flex items-center border border-[var(--border-color)] rounded bg-white overflow-hidden h-[34px]">
                                        <button type="button" @click="decrementQty(index)" class="px-2.5 h-full text-slate-600 hover:bg-slate-100 font-bold text-sm">-</button>
                                        <input type="number" step="1" min="0.001" x-model.number="item.quantity" @input="calculateTotals()"
                                            class="w-full text-center h-full text-xs sm:text-sm font-bold font-numeric focus:outline-none border-x border-[var(--border-color)]">
                                        <button type="button" @click="incrementQty(index)" class="px-2.5 h-full text-slate-600 hover:bg-slate-100 font-bold text-sm">+</button>
                                    </div>

                                    <!-- Unit Price input -->
                                    <div class="col-span-4 sm:col-span-4">
                                        <input type="number" step="0.01" min="0" x-model.number="item.unit_price" @input="calculateTotals()"
                                            placeholder="السعر" class="erp-input h-[34px] text-xs sm:text-sm font-bold font-numeric text-center">
                                    </div>

                                    <!-- Line Total -->
                                    <div class="col-span-3 sm:col-span-4 text-left font-numeric">
                                        <span class="text-xs sm:text-sm font-extrabold text-[var(--text-main)]" x-text="formatCurrency((item.quantity * item.unit_price) - (item.line_discount || 0))"></span>
                                        <span class="text-[10px] text-[var(--text-muted)]">ج.م</span>
                                    </div>
                                </div>

                                <!-- Hidden Inputs for Form Submission -->
                                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                                <input type="hidden" :name="'items[' + index + '][unit_price]'" :value="item.unit_price">
                                <input type="hidden" :name="'items[' + index + '][line_discount]'" :value="item.line_discount || 0">
                            </div>
                        </template>

                        <div x-show="items.length === 0" class="p-8 text-center text-[var(--text-muted)] text-xs border border-dashed border-[var(--border-color)] rounded">
                            لم تتم إضافة أي صنف بعد. اضغط على «إضافة صنف» أو امسح الباركود بالكاميرا.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Summary, Payment & Submission -->
            <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-16">
                <!-- Payment & Commission Summary -->
                <div class="erp-card mb-0 p-4 sm:p-5 space-y-3.5">
                    <h2 class="erp-card-title text-xs sm:text-sm pb-2 border-b border-[var(--border-light)]">
                        <x-icon name="credit-card" size="16" class="text-[var(--primary)]" />
                        <span>السداد واحتساب العمولة</span>
                    </h2>

                    <!-- Payment Type -->
                    <div>
                        <label class="erp-form-label font-bold text-xs mb-1.5">طريقة السداد</label>
                        <div class="grid grid-cols-3 gap-1.5">
                            <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                :class="paymentType === 'cash' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                <input type="radio" name="payment_type" value="cash" x-model="paymentType" class="sr-only">
                                <span>نقدي</span>
                            </label>
                            <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                :class="paymentType === 'credit' ? 'bg-amber-50 border-amber-500 text-amber-700' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                <input type="radio" name="payment_type" value="credit" x-model="paymentType" class="sr-only">
                                <span>آجل</span>
                            </label>
                            <label class="flex items-center justify-center p-2 rounded border border-[var(--border-color)] text-xs font-bold cursor-pointer text-center transition-all select-none"
                                :class="paymentType === 'partial' ? 'bg-[var(--primary-soft)] border-[var(--primary)] text-[var(--primary)]' : 'bg-[var(--bg-hover)] text-[var(--text-secondary)]'">
                                <input type="radio" name="payment_type" value="partial" x-model="paymentType" class="sr-only">
                                <span>جزئي</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="paymentType === 'partial'" class="pt-1">
                        <label class="erp-form-label font-bold text-xs mb-1">المبلغ المدفوع مقدماً</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" x-model.number="paidAmount" placeholder="0.00"
                            class="erp-input text-xs font-numeric">
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="erp-form-label font-bold text-xs mb-1">ملاحظات الفاتورة</label>
                        <input type="text" name="notes" placeholder="موعد التوصيل، تعليمات خاصة..."
                            class="erp-input text-xs">
                    </div>

                    <!-- Order Financial Breakdown -->
                    <div class="p-3.5 rounded bg-[var(--bg-hover)] border border-[var(--border-light)] space-y-2">
                        <div class="flex items-center justify-between text-xs text-[var(--text-secondary)]">
                            <span>إجمالي الفاتورة:</span>
                            <span class="font-extrabold text-[var(--text-main)] text-base font-numeric" x-text="formatCurrency(subtotal) + ' ج.م'"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-emerald-700 font-bold border-t border-[var(--border-color)] pt-2">
                            <span class="flex items-center gap-1">
                                <span>عمولتك الميدانية:</span>
                                <span class="text-[10px] text-emerald-600 font-medium">({{ auth()->user()->commission_rate ?? 0 }}{{ auth()->user()->commission_type === 'percentage' ? '%' : ' ج.م' }})</span>
                            </span>
                            <span class="font-numeric text-sm font-extrabold" x-text="'+ ' + formatCurrency(estimatedCommission) + ' ج.م'"></span>
                        </div>
                    </div>
                </div>

                <!-- Action Submission Buttons -->
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="submit" name="action" value="draft"
                        class="erp-btn erp-btn-secondary w-full py-2.5 text-xs sm:text-sm font-bold justify-center">
                        <x-icon name="file-text" size="15" class="text-amber-600" />
                        <span>حفظ كمسودة</span>
                    </button>

                    <button type="submit" name="action" value="post"
                        class="erp-btn erp-btn-primary w-full py-2.5 text-xs sm:text-sm font-bold justify-center">
                        <x-icon name="check-circle" size="15" />
                        <span>ترحيل الطلب</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Product Picker Modal -->
    <div x-show="showProductPicker" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showProductPicker = false"></div>

        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-t-lg sm:rounded-lg bg-white text-right shadow-xl transition-all w-full max-w-lg max-h-[85vh] flex flex-col border border-[var(--border-color)]">
                <!-- Modal Header -->
                <div class="p-3.5 border-b border-[var(--border-light)] flex items-center justify-between bg-[var(--bg-hover)]">
                    <h3 class="text-xs sm:text-sm font-bold text-[var(--text-main)] flex items-center gap-2">
                        <x-icon name="package" size="16" class="text-[var(--primary)]" />
                        <span>اختر الصنف لإضافته للطلب</span>
                    </h3>
                    <button type="button" @click="showProductPicker = false" class="p-1 text-slate-400 hover:text-slate-600 rounded">
                        <x-icon name="x" size="18" />
                    </button>
                </div>

                <!-- Product Search Inside Modal -->
                <div class="p-3 border-b border-[var(--border-light)] bg-white flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text" x-model="productSearch" placeholder="ابحث باسم المنتج أو الكود أو الباركود..."
                            class="erp-input pr-8 text-xs">
                        <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                            <x-icon name="search" size="14" />
                        </div>
                    </div>
                    <button type="button" data-action="open-barcode-scanner" data-target-input="portalOrderBarcodeInput" class="erp-btn erp-btn-secondary p-2 shrink-0" title="مسح الباركود بالكاميرا">
                        <x-icon name="camera" size="15" class="text-[var(--primary)]" />
                    </button>
                </div>

                <!-- Product List in Modal -->
                <div class="overflow-y-auto p-3 space-y-2 flex-1">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="p-3 rounded border border-[var(--border-color)] hover:border-[var(--primary)] hover:bg-[var(--bg-hover)] transition-all flex items-center justify-between cursor-pointer gap-2.5"
                            @click="selectProduct(product)">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <template x-if="product.image_url">
                                    <img :src="product.image_url" :alt="product.name" class="w-10 h-10 object-contain rounded bg-slate-100 border border-slate-200 shrink-0">
                                </template>
                                <template x-if="!product.image_url">
                                    <div class="w-10 h-10 rounded bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                                        <x-icon name="image" size="18" />
                                    </div>
                                </template>
                                <div class="min-w-0">
                                    <div class="text-xs sm:text-sm font-bold text-[var(--text-main)] truncate" x-text="product.name"></div>
                                    <div class="text-[11px] font-numeric mt-0.5"
                                         :class="product.is_available ? 'text-emerald-700' : 'text-rose-600'"
                                         x-text="'كود: ' + (product.sku || '---') + ' · ' + (product.stock_display || (product.is_available ? 'متوفر للطلب' : 'غير متوفر'))"></div>
                                </div>
                            </div>
                            <div class="text-left shrink-0">
                                <div class="text-xs font-extrabold text-emerald-700 font-numeric" x-text="formatCurrency(getProductPrice(product)) + ' ج.م'"></div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[var(--primary-soft)] text-[var(--primary)]">+ إضافة</span>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredProducts.length === 0" class="p-8 text-center text-[var(--text-muted)] text-xs">
                        لا توجد أصناف مطابقة للبحث
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
