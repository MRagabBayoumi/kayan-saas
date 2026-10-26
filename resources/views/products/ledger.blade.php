@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="كشف حركة الصنف: {{ $product->name }}" :breadcrumbs="['المنتجات والأصناف' => route('products.index'), $product->name => route('products.show', $product), 'كشف الحركة' => '#']">
        <x-slot:actions>
            <a href="{{ route('products.ledger.print', array_merge(['product' => $product->id], request()->query())) }}" class="erp-btn erp-btn-secondary" target="_blank" title="طباعة كشف حركة الصنف">
                <x-icon name="printer" size="16" />
                <span>طباعة كشف الحركة</span>
            </a>
            <a href="{{ route('products.show', $product) }}" class="erp-btn erp-btn-primary">
                <x-icon name="eye" size="16" />
                <span>تفاصيل الصنف</span>
            </a>
            <a href="{{ route('products.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-left" size="16" />
                <span>العودة للأصناف</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric text-xs">
                <span>SKU: {{ $product->sku }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="product-ledger" class="flex flex-col gap-5">
        <!-- Product Header Overview Card -->
        <div class="erp-card p-4">
            <div class="erp-ledger-header">
                <!-- Product Identity -->
                <div class="erp-ledger-identity">
                    <div class="erp-ledger-avatar">
                        @if($product->image_path)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <x-icon name="package" size="28" />
                        @endif
                    </div>
                    <div class="erp-ledger-details">
                        <h2 class="erp-ledger-title">{{ $product->name }}</h2>
                        <div class="erp-ledger-badges-row">
                            <span class="erp-ledger-badge">
                                <x-icon name="tag" size="12" />
                                <span>التصنيف: <strong>{{ $product->category->name ?? 'غير محدد' }}</strong></span>
                            </span>
                            <span class="erp-ledger-badge">
                                <x-icon name="layers" size="12" />
                                <span>الوحدة: <strong>{{ $product->unit }}</strong></span>
                            </span>
                            @if($product->barcode)
                                <span class="erp-ledger-badge font-numeric">
                                    <x-icon name="barcode" size="12" />
                                    <span>باركود: <strong>{{ $product->barcode }}</strong></span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 5-Metric Strip: Balance & 4 Price Tiers -->
                <div class="erp-ledger-metrics-grid">
                    <!-- Current Available Stock -->
                    <div class="erp-ledger-metric-card highlight">
                        <span class="erp-ledger-metric-label">الرصيد المتوفر</span>
                        <div class="erp-ledger-metric-val font-numeric text-sm sm:text-base">
                            {{ $product->formatStockBreakdown() }}
                        </div>
                    </div>

                    <!-- Cost Price -->
                    <div class="erp-ledger-metric-card">
                        <span class="erp-ledger-metric-label">سعر التكلفة</span>
                        <div class="erp-ledger-metric-val font-numeric">
                            {{ number_format((float)$product->cost_price, 2) }}
                            <span class="erp-ledger-metric-unit">ج.م</span>
                        </div>
                    </div>

                    <!-- Retail Price -->
                    <div class="erp-ledger-metric-card">
                        <span class="erp-ledger-metric-label">سعر القطاعي</span>
                        <div class="erp-ledger-metric-val font-numeric">
                            {{ number_format((float)$product->retail_price, 2) }}
                            <span class="erp-ledger-metric-unit">ج.م</span>
                        </div>
                    </div>

                    <!-- Wholesale Price -->
                    <div class="erp-ledger-metric-card">
                        <span class="erp-ledger-metric-label">سعر الجملة</span>
                        <div class="erp-ledger-metric-val font-numeric">
                            {{ number_format((float)$product->wholesale_price, 2) }}
                            <span class="erp-ledger-metric-unit">ج.م</span>
                        </div>
                    </div>

                    <!-- Distributor Price -->
                    <div class="erp-ledger-metric-card">
                        <span class="erp-ledger-metric-label">سعر الموزع</span>
                        <div class="erp-ledger-metric-val font-numeric">
                            {{ number_format((float)($product->distributor_price ?? $product->wholesale_price), 2) }}
                            <span class="erp-ledger-metric-unit">ج.م</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warehouse Distribution Strip -->
            @if($product->stockBalances->isNotEmpty())
                <div class="erp-ledger-warehouses-strip">
                    <span class="erp-ledger-warehouses-title">
                        <x-icon name="home" size="14" />
                        <span>توزيع الرصيد بالمستودعات:</span>
                    </span>
                    @foreach($product->stockBalances as $sb)
                        <span class="erp-ledger-wh-pill font-numeric">
                            <span>{{ $sb->warehouse->name ?? 'مستودع' }}:</span>
                            <strong>{{ $product->formatStockBreakdown((float)$sb->quantity) }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Filter Bar -->
        <div class="erp-card p-4">
            <form action="{{ route('products.ledger', $product) }}" method="GET" class="erp-ledger-filter-form">
                <!-- Warehouse -->
                <div class="erp-form-group mb-0">
                    <label for="ledgerWarehouse" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                        <x-icon name="home" size="13" class="text-[var(--primary)]" />
                        <span>المستودع</span>
                    </label>
                    <select id="ledgerWarehouse" name="warehouse_id" class="erp-input w-full text-xs">
                        <option value="">-- جميع المستودعات --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$currentWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Movement Type -->
                <div class="erp-form-group mb-0">
                    <label for="ledgerType" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                        <x-icon name="activity" size="13" class="text-[var(--primary)]" />
                        <span>نوع الحركة</span>
                    </label>
                    <select id="ledgerType" name="type" class="erp-input w-full text-xs">
                        <option value="">-- جميع الحركات --</option>
                        <option value="in" {{ $currentType === 'in' ? 'selected' : '' }}>وارد / إيداع</option>
                        <option value="out" {{ $currentType === 'out' ? 'selected' : '' }}>منصرف / مبيعات</option>
                        <option value="transfer" {{ $currentType === 'transfer' ? 'selected' : '' }}>تحويل مخزني</option>
                        <option value="adjustment" {{ $currentType === 'adjustment' ? 'selected' : '' }}>تسوية جردية</option>
                    </select>
                </div>

                <!-- Date From -->
                <div class="erp-form-group mb-0">
                    <label for="dateFrom" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                        <x-icon name="calendar" size="13" />
                        <span>من تاريخ</span>
                    </label>
                    <input type="date" id="dateFrom" name="date_from" value="{{ $currentDateFrom }}" class="erp-input w-full text-xs font-numeric">
                </div>

                <!-- Date To -->
                <div class="erp-form-group mb-0">
                    <label for="dateTo" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                        <x-icon name="calendar" size="13" />
                        <span>إلى تاريخ</span>
                    </label>
                    <input type="date" id="dateTo" name="date_to" value="{{ $currentDateTo }}" class="erp-input w-full text-xs font-numeric">
                </div>

                <!-- Filter Actions -->
                <div class="erp-ledger-filter-actions">
                    <button type="submit" class="erp-btn erp-btn-secondary text-xs h-[36px]">
                        <x-icon name="filter" size="14" />
                        <span>تطبيق الفلتر</span>
                    </button>

                    @if($currentWarehouseId || $currentType || $currentDateFrom || $currentDateTo)
                        <a href="{{ route('products.ledger', $product) }}" class="erp-btn erp-btn-ghost text-xs text-[var(--danger)] h-[36px]" title="إلغاء الفلاتر">
                            <x-icon name="x" size="14" />
                            <span>إلغاء</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Detailed Movement Table -->
        <div class="erp-card overflow-hidden">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title">
                    <x-icon name="activity" size="18" />
                    <span>سجل الحركات التفصيلي ({{ $movements->total() }} حركة)</span>
                </div>
                <a href="{{ route('products.ledger.print', array_merge(['product' => $product->id], request()->query())) }}" class="erp-btn erp-btn-secondary py-1 px-2.5 text-xs" target="_blank" title="طباعة كشف الحركة">
                    <x-icon name="printer" size="14" />
                    <span>طباعة كشف الحركة</span>
                </a>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>التاريخ والوقت</th>
                            <th>رقم الحركة</th>
                            <th>نوع الحركة</th>
                            <th>المستودع (من / إلى)</th>
                            <th>الطرف (المورد / العميل)</th>
                            <th class="text-center">الكمية</th>
                            <th class="text-center">تكلفة الوحدة</th>
                            <th class="text-center">الرصيد التراكمي</th>
                            <th>المرجع والبيان</th>
                            <th>المستخدم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                            <tr>
                                <td class="font-numeric text-xs">
                                    {{ $m->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    <span class="font-numeric font-bold text-xs text-[var(--text-main)]">{{ $m->movement_number }}</span>
                                </td>
                                <td>
                                    @if($m->reference_type === 'opening_balance')
                                        <span class="erp-badge erp-badge-info text-[11px]">رصيد افتتاحي</span>
                                    @else
                                        <span class="erp-badge {{ $m->type_badge_class }} text-[11px]">
                                            {{ $m->type_label }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-xs">
                                    @if($m->type === 'transfer')
                                        <span>{{ $m->fromWarehouse->name ?? '—' }} ⬅ {{ $m->toWarehouse->name ?? '—' }}</span>
                                    @elseif($m->toWarehouse)
                                        <span>{{ $m->toWarehouse->name }}</span>
                                    @elseif($m->fromWarehouse)
                                        <span>{{ $m->fromWarehouse->name }}</span>
                                    @else
                                        <span>—</span>
                                    @endif
                                </td>
                                <td class="text-xs">
                                    @if($m->supplier)
                                        <span class="text-[var(--primary)] font-semibold">{{ $m->supplier->name }} (مورد)</span>
                                    @elseif($m->customer)
                                        <span class="text-[var(--success-text)] font-semibold">{{ $m->customer->name }} (عميل)</span>
                                    @else
                                        <span class="text-[var(--text-muted)]">—</span>
                                    @endif
                                </td>
                                <td class="font-numeric text-center font-bold text-xs">
                                    @if($m->type === 'in' || $m->reference_type === 'opening_balance')
                                        <span class="text-[var(--success-text)]">+{{ number_format((float)$m->quantity, 2) }}</span>
                                    @elseif($m->type === 'out')
                                        <span class="text-[var(--danger)]">-{{ number_format((float)$m->quantity, 2) }}</span>
                                    @else
                                        <span>{{ number_format((float)$m->quantity, 2) }}</span>
                                    @endif
                                    <small class="text-[10px] text-[var(--text-muted)]">قطعة</small>
                                    @if(($product->unit_factor ?? 1) > 1 && $product->unit && $product->unit !== 'قطعة')
                                        <div class="text-[10px] text-[var(--text-muted)] font-normal">({{ $product->formatStockBreakdown((float)$m->quantity) }})</div>
                                    @endif
                                </td>
                                <td class="font-numeric text-center text-xs">
                                    {{ number_format((float)$m->unit_cost, 2) }} ج.م
                                </td>
                                <td class="font-numeric text-center font-bold text-xs text-[var(--primary)] bg-[var(--surface-muted)]">
                                    @if($m->running_balance !== null)
                                        <div>{{ number_format((float)$m->running_balance, 2) }}</div>
                                        @if(($product->unit_factor ?? 1) > 1 && $product->unit && $product->unit !== 'قطعة')
                                            <div class="text-[10px] text-[var(--text-muted)] font-normal">({{ $product->formatStockBreakdown((float)$m->running_balance) }})</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-xs text-[var(--text-muted)]">
                                    <div>{{ $m->notes ?? '—' }}</div>
                                    @if($m->reference_type)
                                        <small class="text-[10px] text-[var(--text-muted)] block font-numeric">{{ $m->reference_type }}</small>
                                    @endif
                                </td>
                                <td class="text-xs text-[var(--text-muted)]">
                                    {{ $m->user->name ?? 'النظام' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-[var(--text-muted)]">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <x-icon name="activity" size="32" class="opacity-40" />
                                        <p class="m-0 text-sm font-semibold">لا توجد حركات مخزنية مسجلة لهذا الصنف حتى الآن.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="p-3 border-t border-[var(--border-color)]">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
