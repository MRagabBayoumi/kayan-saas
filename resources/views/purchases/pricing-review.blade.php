@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مراجعة واعتماد أسعار البيع للفاتورة: {{ $purchase->invoice_number }}" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), $purchase->invoice_number => route('purchases.show', $purchase), 'مراجعة الأسعار' => '#']">
        <x-slot:actions>
            <button type="submit" form="pricingReviewForm" class="erp-btn erp-btn-primary">
                <x-icon name="check-circle" size="16" />
                <span>تأكيد واعتماد الأسعار والترحيل للمخازن</span>
            </button>
            <a href="{{ route('purchases.show', $purchase) }}" class="erp-btn erp-btn-ghost">
                <x-icon name="x" size="16" />
                <span>إلغاء والعودة للمسودة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning py-1 px-2.5 text-xs font-semibold">
                بانتظار اعتماد الأسعار والترحيل
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchases-pricing-review" class="flex flex-col gap-5">
        <!-- Guidance Alert -->
        <div class="erp-alert erp-alert-info" role="alert">
            <x-icon name="info" size="18" />
            <div>
                <strong>مراجعة تسعير الدفعات الواردة (Batch Pricing Review):</strong>
                <span>
                    يمكنك هنا تحديد أو تحديث فئات أسعار البيع (قطاعي، جملة، موزع) لربطها بدفعات المخزون الواردة بهذه الفاتورة بنظام FIFO.
                    هذه الخطوة تضمن تسعيراً دقيقاً قبل صرف أي كميات في فواتير المبيعات ونقاط البيع.
                </span>
            </div>
        </div>

        @if(session('error'))
            <div class="erp-alert erp-alert-danger" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Invoice Meta Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="erp-card">
                <div class="erp-card-body flex flex-col gap-1 text-xs">
                    <span class="text-[var(--text-muted)]">المورد:</span>
                    <strong class="text-sm text-[var(--text-main)]">{{ $purchase->supplier->name ?? 'مورد غير محدد' }}</strong>
                </div>
            </div>
            <div class="erp-card">
                <div class="erp-card-body flex flex-col gap-1 text-xs">
                    <span class="text-[var(--text-muted)]">مستودع الاستلام:</span>
                    <strong class="text-sm text-[var(--text-main)]">{{ $purchase->warehouse->name ?? 'المخزن الرئيسي' }}</strong>
                </div>
            </div>
            <div class="erp-card">
                <div class="erp-card-body flex flex-col gap-1 text-xs">
                    <span class="text-[var(--text-muted)]">تاريخ الفاتورة:</span>
                    <strong class="text-sm font-numeric text-[var(--text-main)]">{{ \Carbon\Carbon::parse($purchase->invoice_date)->format('Y-m-d') }}</strong>
                </div>
            </div>
            <div class="erp-card">
                <div class="erp-card-body flex flex-col gap-1 text-xs">
                    <span class="text-[var(--text-muted)]">إجمالي الفاتورة:</span>
                    <strong class="text-sm font-numeric text-[var(--primary)]">{{ number_format((float)$purchase->total_amount, 2) }} ج.م</strong>
                </div>
            </div>
        </div>

        <!-- Pricing Form -->
        <form id="pricingReviewForm" action="{{ route('purchases.confirm-pricing', $purchase) }}" method="POST" data-confirm="هل تريد اعتماد هذه الأسعار وترحيل الفاتورة وتوليد دفعات المخزون بنظام FIFO؟">
            @csrf

            <div class="erp-card">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title">
                        <x-icon name="tag" size="18" />
                        <span>جدول مراجعة واعتماد أسعار البيع للأصناف المشتراة</span>
                    </div>

                    @if($canEditPrices)
                        <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer text-[var(--text-main)]">
                            <input type="checkbox" name="update_master_prices" value="1" class="erp-checkbox" checked>
                            <span>تحديث الأسعار في بطاقة الصنف الرئيسية (Master Product) أيضاً</span>
                        </label>
                    @endif
                </div>

                <div class="erp-card-body p-0">
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>الصنف والوصف</th>
                                    <th class="w-28 text-center">كمية الشراء</th>
                                    <th class="w-32 text-center">الكمية الأساسية</th>
                                    <th class="w-28 text-center">تكلفة القطعة (Base Cost)</th>
                                    <th class="w-36 text-center">سعر القطاعي (Retail)</th>
                                    <th class="w-36 text-center">سعر الجملة (Wholesale)</th>
                                    <th class="w-36 text-center">سعر الموزع (Distributor)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                    @php
                                        $prod = $item->product;
                                        $factor = (float)($item->conversion_factor > 0 ? $item->conversion_factor : 1.0);
                                        $baseQty = (float)($item->base_quantity ?: round((float)$item->quantity * $factor, 4));
                                        $baseCost = $baseQty > 0 ? round((float)$item->subtotal / $baseQty, 4) : (float)$item->unit_cost;
                                        $curRetail = !is_null($prod?->retail_price) ? (float)$prod->retail_price : null;
                                        $curWholesale = !is_null($prod?->wholesale_price) ? (float)$prod->wholesale_price : null;
                                        $curDistributor = !is_null($prod?->distributor_price) ? (float)$prod->distributor_price : null;
                                    @endphp
                                    <tr>
                                        <td class="text-center font-numeric">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-bold text-[var(--text-main)]">{{ $prod->name ?? 'صنف غير محدد' }}</div>
                                            <div class="text-[11px] text-[var(--text-muted)] font-numeric">
                                                كود: {{ $prod->sku ?? '—' }}
                                                @if($prod?->category)
                                                    · قسم: {{ $prod->category->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center text-xs">
                                            <span class="font-numeric font-semibold">{{ (float)$item->quantity }}</span>
                                            <span class="text-[var(--text-muted)]">{{ $item->unit->name ?? 'قطعة' }}</span>
                                        </td>
                                        <td class="text-center text-xs">
                                            <span class="font-numeric font-semibold text-[var(--primary)]">{{ $baseQty }}</span>
                                            <span class="text-[var(--text-muted)]">قطعة</span>
                                            @if($factor != 1.0)
                                                <div class="text-[10px] text-[var(--text-muted)] font-numeric">معامل: {{ $factor }} قطعة / {{ $item->unit->name ?? 'وحدة' }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center font-numeric font-bold text-amber-600 text-xs">
                                            {{ number_format($baseCost, 2) }} ج.م
                                            @if($factor > 1)
                                                <div class="text-[10px] text-[var(--text-muted)] font-normal font-numeric">
                                                    ({{ number_format((float)$item->unit_cost, 2) }} ج.م / {{ $item->unit->name ?? $prod->unit }})
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    name="prices[{{ $item->product_id }}][retail_price]" 
                                                    value="{{ old('prices.'.$item->product_id.'.retail_price', $curRetail ?? $baseCost * 1.3) }}" 
                                                    class="erp-input font-numeric text-center text-xs w-full"
                                                    {{ $canEditPrices ? '' : 'readonly' }}
                                                    required
                                                >
                                                @if(!is_null($curRetail))
                                                    <span class="text-[10px] text-[var(--text-muted)] text-center font-numeric">الحالي: {{ number_format($curRetail, 2) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    name="prices[{{ $item->product_id }}][wholesale_price]" 
                                                    value="{{ old('prices.'.$item->product_id.'.wholesale_price', $curWholesale ?? $baseCost * 1.15) }}" 
                                                    class="erp-input font-numeric text-center text-xs w-full"
                                                    {{ $canEditPrices ? '' : 'readonly' }}
                                                    required
                                                >
                                                @if(!is_null($curWholesale))
                                                    <span class="text-[10px] text-[var(--text-muted)] text-center font-numeric">الحالي: {{ number_format($curWholesale, 2) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    name="prices[{{ $item->product_id }}][distributor_price]" 
                                                    value="{{ old('prices.'.$item->product_id.'.distributor_price', $curDistributor ?? $baseCost * 1.1) }}" 
                                                    class="erp-input font-numeric text-center text-xs w-full"
                                                    {{ $canEditPrices ? '' : 'readonly' }}
                                                    required
                                                >
                                                @if(!is_null($curDistributor))
                                                    <span class="text-[10px] text-[var(--text-muted)] text-center font-numeric">الحالي: {{ number_format($curDistributor, 2) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="erp-card-footer flex items-center justify-between">
                    <span class="text-xs text-[var(--text-muted)]">
                        سيتم حفظ أسعار البيع على مستوى الدفعة الواردة لضمان صحة هوامش الربح في تقارير المبيعات.
                    </span>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>اعتماد الأسعار والترحيل النهائي (Post FIFO)</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
