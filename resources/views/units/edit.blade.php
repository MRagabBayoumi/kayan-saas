@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل وحدة القياس: {{ $unit->name }}" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'وحدات القياس' => route('units.index'), 'تعديل الوحدة' => route('units.edit', $unit)]">
        <x-slot:actions>
            <a href="{{ route('units.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للوحدات</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
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

        <div class="unit-form-card">
            <div class="unit-form-header">
                <div class="erp-card-title">
                    <x-icon name="layers" size="18" />
                    <span>تعديل بيانات وحدة القياس: {{ $unit->name }}</span>
                </div>
            </div>

            <form action="{{ route('units.update', $unit) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="unit-form-body">
                    <div class="erp-form-group mb-3.5">
                        <label for="unitName" class="erp-form-label">
                            اسم وحدة القياس <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input
                            type="text"
                            id="unitName"
                            name="name"
                            value="{{ old('name', $unit->name) }}"
                            class="erp-input @error('name') erp-input-error @enderror"
                            required
                        >
                    </div>

                    <div class="erp-form-group mb-3.5">
                        <label for="unitCode" class="erp-form-label">الرمز أو الاختصار</label>
                        <input
                            type="text"
                            id="unitCode"
                            name="code"
                            value="{{ old('code', $unit->code) }}"
                            class="erp-input font-numeric @error('code') erp-input-error @enderror"
                        >
                    </div>

                    <div class="erp-form-row-2col mb-3.5">
                        <div class="erp-form-group">
                            <label for="baseUnitId" class="erp-form-label">الوحدة المرجعية الأساسية</label>
                            <select id="baseUnitId" name="base_unit_id" class="erp-input">
                                <option value="">-- بدون وحدة مرجعية (وحدة صغرى أساسية) --</option>
                                @foreach($baseUnits as $bu)
                                    <option value="{{ $bu->id }}" {{ (string) old('base_unit_id', $unit->base_unit_id) === (string) $bu->id ? 'selected' : '' }}>
                                        {{ $bu->name }} ({{ (float) $bu->conversion_factor }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="erp-form-group">
                            <label for="conversionFactor" class="erp-form-label">
                                معامل التحويل (عدد القطع) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                id="conversionFactor"
                                name="conversion_factor"
                                value="{{ old('conversion_factor', (float) $unit->conversion_factor) }}"
                                class="erp-input font-numeric @error('conversion_factor') erp-input-error @enderror"
                                required
                            >
                        </div>
                    </div>

                    <input type="hidden" name="operator" value="*">

                    <div class="unit-equation-banner">
                        <x-icon name="info" size="16" class="text-[var(--primary)] shrink-0" />
                        <div>
                            <span>معادلة الاحتساب الحالية:</span>
                            <strong>1 {{ $unit->name }} = {{ (float) $unit->conversion_factor }} {{ $unit->baseUnit?->name ?? 'قطعة' }}</strong>
                        </div>
                    </div>

                    <div class="erp-form-group mb-3.5">
                        <label for="unitNotes" class="erp-form-label">ملاحظات توضيحية</label>
                        <textarea id="unitNotes" name="notes" rows="2" class="erp-input">{{ old('notes', $unit->notes) }}</textarea>
                    </div>

                    <div class="erp-form-group">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $unit->is_active ? '1' : '0') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                            <span class="font-medium text-[var(--text-main)] text-sm">الوحدة مفعلة ومتاحة في شاشات المنتجات</span>
                        </label>
                    </div>
                </div>

                <div class="unit-form-footer">
                    <a href="{{ route('units.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>حفظ التعديلات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
