@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة وحدات القياس ومعاملات التحويل" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'وحدات القياس' => route('units.index')]">
        <x-slot:actions>
            <a href="{{ route('units.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة وحدة قياس جديدة</span>
            </a>
            <a href="{{ url('/products') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="package" size="16" />
                <span>دليل المنتجات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الوحدات:</span>
                <strong>{{ $units->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="units">
        <!-- Flash Messages -->
        @if(session('status') === 'success' && session('message'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @if(session('status') === 'error' && session('message'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية:</strong>
                    <ul class="my-1 mx-4 text-xs list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Summary KPI Cards -->
        <div class="units-kpi-grid">
            <div class="units-kpi-card">
                <div class="units-kpi-icon primary">
                    <x-icon name="layers" size="22" />
                </div>
                <div class="units-kpi-info">
                    <div class="units-kpi-label">إجمالي وحدات القياس</div>
                    <div class="units-kpi-value font-numeric">{{ $units->count() }}</div>
                </div>
            </div>

            <div class="units-kpi-card">
                <div class="units-kpi-icon success">
                    <x-icon name="check-circle" size="22" />
                </div>
                <div class="units-kpi-info">
                    <div class="units-kpi-label">الوحدة المرجعية الأساسية</div>
                    <div class="units-kpi-value">
                        {{ $units->firstWhere('is_default', true)?->name ?? 'قطعة' }}
                    </div>
                </div>
            </div>

            <div class="units-kpi-card">
                <div class="units-kpi-icon info">
                    <x-icon name="package" size="22" />
                </div>
                <div class="units-kpi-info">
                    <div class="units-kpi-label">الوحدات المجمعة والمشتقة</div>
                    <div class="units-kpi-value font-numeric">
                        {{ $units->where('is_default', false)->count() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Master-Detail Dual Layout -->
        <div class="units-layout-grid">
            <!-- Left Panel: Quick Add Unit Form -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="plus" size="18" />
                        <span>إضافة وحدة قياس جديدة</span>
                    </div>
                </div>
                <div class="erp-card-body">
                    <form action="{{ route('units.store') }}" method="POST">
                        @csrf

                        <div class="erp-form-group mb-3">
                            <label for="quickUnitName" class="erp-form-label">
                                اسم وحدة القياس <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="text"
                                id="quickUnitName"
                                name="name"
                                value="{{ old('name') }}"
                                class="erp-input @error('name') erp-input-error @enderror"
                                placeholder="مثال: دستة، كرتونة 24، باكت 50"
                                required
                            >
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickUnitCode" class="erp-form-label">الرمز أو الاختصار</label>
                            <input
                                type="text"
                                id="quickUnitCode"
                                name="code"
                                value="{{ old('code') }}"
                                class="erp-input font-numeric @error('code') erp-input-error @enderror"
                                placeholder="مثال: DZ، CTN، PKT"
                            >
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickBaseUnit" class="erp-form-label">الوحدة المرجعية الأساسية</label>
                            <select id="quickBaseUnit" name="base_unit_id" class="erp-input">
                                <option value="">-- بدون (وحدة صغرى أساسية) --</option>
                                @foreach($baseUnits as $bu)
                                    <option value="{{ $bu->id }}" {{ (string) old('base_unit_id', $bu->is_default ? $bu->id : '') === (string) $bu->id ? 'selected' : '' }}>
                                        {{ $bu->name }} (الأساس)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickConversionFactor" class="erp-form-label">
                                معامل التحويل (عدد القطع) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                id="quickConversionFactor"
                                name="conversion_factor"
                                value="{{ old('conversion_factor', '12.0000') }}"
                                class="erp-input font-numeric @error('conversion_factor') erp-input-error @enderror"
                                required
                            >
                        </div>

                        <input type="hidden" name="operator" value="*">

                        <div class="unit-equation-banner">
                            <x-icon name="info" size="16" class="text-[var(--primary)]" />
                            <div>
                                <span>معادلة التحويل:</span>
                                <strong>تحدد عدد القطع المعبأة داخل هذه الوحدة</strong>
                            </div>
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickNotes" class="erp-form-label">ملاحظات توضيحية</label>
                            <textarea id="quickNotes" name="notes" rows="2" class="erp-input" placeholder="مثال: تستخدم في تغليف إكسسوارات الشعر...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="erp-form-group mb-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                                <span class="font-medium text-[var(--text-main)] text-xs">الوحدة مفعلة ومتاحة في بطاقات المنتجات</span>
                            </label>
                        </div>

                        <button type="submit" class="erp-btn erp-btn-primary w-full">
                            <x-icon name="plus" size="16" />
                            <span>حفظ وحدة القياس</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Panel: Units Table -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="layers" size="18" />
                        <span>قائمة وحدات القياس المسجلة ومعاملاتها</span>
                    </div>
                </div>
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>اسم الوحدة</th>
                                <th>الكود</th>
                                <th>الوحدة المرجعية</th>
                                <th>معادلة التحويل الصريحة</th>
                                <th class="text-center">الحالة</th>
                                <th>ملاحظات</th>
                                <th class="w-24 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($units as $unit)
                                <tr>
                                    <td class="font-numeric text-center">{{ $unit->id }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[var(--text-main)]">{{ $unit->name }}</span>
                                            @if($unit->is_default)
                                                <span class="erp-badge erp-badge-primary text-[10px] px-1.5 py-0.5">أساسية</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($unit->code)
                                            <code class="text-xs bg-[var(--bg-canvas)] px-1.5 py-0.5 rounded border border-[var(--border-color)]">{{ $unit->code }}</code>
                                        @else
                                            <span class="text-[var(--text-muted)] text-xs">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($unit->baseUnit)
                                            <span class="text-xs font-semibold text-[var(--text-main)]">{{ $unit->baseUnit->name }}</span>
                                        @else
                                            <span class="text-xs text-[var(--text-muted)]">الوحدة الصغرى (نفسها)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($unit->is_default)
                                            <span class="unit-equation-badge base">
                                                <span>الوحدة الأساسية المرجعية</span>
                                                <span class="font-numeric font-bold">(1.0)</span>
                                            </span>
                                        @else
                                            <span class="unit-equation-badge font-numeric">
                                                <span>1 {{ $unit->name }}</span>
                                                <span>=</span>
                                                <strong>{{ (float) $unit->conversion_factor }}</strong>
                                                <span>{{ $unit->baseUnit?->name ?? 'قطعة' }}</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($unit->is_active)
                                            <span class="erp-badge erp-badge-success">مفعلة</span>
                                        @else
                                            <span class="erp-badge erp-badge-danger">معطلة</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-[var(--text-muted)]">{{ $unit->notes ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1.5 justify-center">
                                            <a href="{{ route('units.edit', $unit) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="تعديل الوحدة">
                                                <x-icon name="edit" size="14" />
                                                <span>تعديل</span>
                                            </a>
                                            @if(! $unit->is_default)
                                                <form action="{{ route('units.destroy', $unit) }}" method="POST" data-confirm="هل أنت متأكد من حذف وحدة القياس هذه؟" class="inline m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="حذف الوحدة">
                                                        <x-icon name="trash" size="14" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد وحدات قياس مسجلة في النظام.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
