@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="المستودعات والمخازن" :breadcrumbs="['المخزون والمنتجات' => route('inventory.index'), 'المستودعات' => route('warehouses.index')]">
        <x-slot:actions>
            <button type="button" data-action="open-add-warehouse" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مستودع جديد</span>
            </button>
            <a href="{{ route('inventory.stocktake') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clipboard-check" size="16" />
                <span>جرد وتسوية المخزون</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="warehouse" size="16" />
                <span>نظرة عامة على المخزون</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>عدد المستودعات:</span>
                <strong>{{ $warehouses->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="warehouses">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Warehouses Grid -->
        <div class="warehouses-grid">
            @forelse($warehouses as $warehouse)
                <div class="erp-card warehouse-card {{ $warehouse->is_default ? 'is-default' : '' }}">
                    <div class="warehouse-card-body">
                        <div class="warehouse-card-header">
                            <div>
                                <div class="warehouse-title-group">
                                    <h3 class="warehouse-name">
                                        {{ $warehouse->name }}
                                    </h3>
                                    @if($warehouse->is_default)
                                        <span class="erp-badge erp-badge-success text-[11px] px-1.5 py-0.5">افتراضي</span>
                                    @endif
                                </div>
                                <div class="warehouse-code font-numeric">
                                    {{ $warehouse->code }}
                                </div>
                            </div>
                            <span class="erp-badge {{ $warehouse->is_active ? 'erp-badge-neutral' : 'erp-badge-danger' }}">
                                {{ $warehouse->is_active ? 'نشط' : 'معطل' }}
                            </span>
                        </div>

                        <div class="warehouse-location">
                            <x-icon name="map-pin" size="14" />
                            <span>{{ $warehouse->location ?: 'لم يحدد الموقع بعد' }}</span>
                        </div>

                        @if($warehouse->notes)
                            <p class="warehouse-notes">
                                {{ $warehouse->notes }}
                            </p>
                        @endif

                        <div class="warehouse-stats">
                            <span class="text-xs text-[var(--text-secondary)] font-medium">عدد الأصناف المسجلة:</span>
                            <span class="font-numeric font-extrabold text-sm text-[var(--text-main)]">
                                {{ $warehouse->stock_balances_count }}
                            </span>
                        </div>
                    </div>

                    <div class="warehouse-card-footer">
                        <a href="{{ route('warehouses.show', $warehouse) }}" class="erp-btn erp-btn-sm erp-btn-primary" title="عرض تفاصيل المخزون والجرد">
                            <x-icon name="eye" size="14" />
                            <span>رصيد المخزون</span>
                        </a>

                        <button 
                            type="button" 
                            data-action="edit-warehouse"
                            data-warehouse="{{ json_encode($warehouse) }}"
                            class="erp-btn erp-btn-sm erp-btn-secondary"
                            title="تعديل">
                            <x-icon name="edit" size="14" />
                            <span>تعديل</span>
                        </button>

                        @if(!$warehouse->is_default)
                            <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" data-confirm="هل أنت متأكد من حذف المستودع {{ $warehouse->name }}؟" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="erp-btn erp-btn-sm erp-btn-danger" title="حذف">
                                    <x-icon name="trash" size="14" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="erp-card warehouse-empty-state">
                    <x-icon name="warehouse" size="48" class="text-[var(--text-muted)] mb-3 inline-block" />
                    <h3 class="m-0 mb-2 text-base font-bold">لا توجد مستودعات مسجلة حتى الآن</h3>
                    <p class="text-[var(--text-muted)] text-sm mb-4">قم بإنشاء المستودع الرئيسي أو الفروع لتوزيع وإدارة المخزون بدقة.</p>
                    <button type="button" data-action="open-add-warehouse" class="erp-btn erp-btn-primary">
                        <x-icon name="plus" size="16" />
                        <span>إضافة أول مستودع</span>
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Modal: Add Warehouse -->
        <div id="addWarehouseModal" class="erp-modal-overlay">
            <div class="erp-modal-container">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="home" size="18" />
                        <span>إضافة مستودع جديد</span>
                    </div>
                    <button type="button" data-action="close-add-warehouse" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form action="{{ route('warehouses.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body warehouse-modal-body">
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">كود المستودع *</label>
                            <input type="text" name="code" class="erp-input font-numeric w-full" placeholder="مثال: WH-MAIN, WH-BRANCH-1" required>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المستودع *</label>
                            <input type="text" name="name" class="erp-input w-full" placeholder="مثال: المستودع الرئيسي - العتبة" required>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">الموقع / العنوان</label>
                            <input type="text" name="location" class="erp-input w-full" placeholder="مثال: شارع عبد العزيز، العتبة، القاهرة">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات إضافية عن المستودع..."></textarea>
                        </div>

                        <div class="mt-2.5">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" name="is_default" value="1">
                                <span>تعيين كمستودع افتراضي للنظام</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer warehouse-modal-footer">
                        <button type="button" data-action="close-add-warehouse" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ المستودع</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Edit Warehouse -->
        <div id="editWarehouseModal" class="erp-modal-overlay">
            <div class="erp-modal-container">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="edit" size="18" />
                        <span>تعديل بيانات المستودع</span>
                    </div>
                    <button type="button" data-action="close-edit-warehouse" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form id="editWarehouseForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="erp-modal-body warehouse-modal-body">
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">كود المستودع *</label>
                            <input type="text" id="edit_code" name="code" class="erp-input font-numeric w-full" required>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المستودع *</label>
                            <input type="text" id="edit_name" name="name" class="erp-input w-full" required>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">الموقع / العنوان</label>
                            <input type="text" id="edit_location" name="location" class="erp-input w-full">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات</label>
                            <textarea id="edit_notes" name="notes" class="erp-input w-full" rows="2"></textarea>
                        </div>

                        <div class="mb-2.5">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                                <span>مستودع نشط</span>
                            </label>
                        </div>

                        <div class="mt-2">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" id="edit_is_default" name="is_default" value="1">
                                <span>تعيين كمستودع افتراضي للنظام</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer warehouse-modal-footer">
                        <button type="button" data-action="close-edit-warehouse" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
