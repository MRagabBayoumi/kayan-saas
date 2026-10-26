@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تصنيفات المنتجات" :breadcrumbs="['المنتجات والأصناف' => route('products.index'), 'التصنيفات' => '#']">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="plus" size="16" />
                <span>إضافة منتج جديد</span>
            </a>
            <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="package" size="16" />
                <span>قائمة المنتجات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي التصنيفات:</span>
                <strong>{{ $categories->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <!-- Flash Messages -->
    @if(session('status') === 'success')
        <div class="erp-alert erp-alert-success mb-4" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('message', 'تمت العملية بنجاح.') }}</div>
        </div>
    @endif

    @if(session('status') === 'error')
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>{{ session('message', 'حدث خطأ أثناء تنفيذ العملية.') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul class="my-1.5 mx-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="categories-layout-grid">
        <!-- Left: Quick Add Category Form -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="plus" size="18" />
                    <span>إضافة تصنيف جديد</span>
                </div>
            </div>
            <div class="erp-card-body">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf

                    <div class="erp-form-group mb-3.5">
                        <label for="catName" class="erp-form-label">
                            اسم التصنيف <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="catName" 
                            name="name" 
                            value="{{ old('name') }}" 
                            class="erp-input @error('name') erp-input-error @enderror" 
                            placeholder="مثال: توك شعر، بكلات، أطواق" 
                            required
                        >
                    </div>

                    <div class="erp-form-group mb-3.5">
                        <label for="catParent" class="erp-form-label">التصنيف الرئيسي (اختياري)</label>
                        <select id="catParent" name="parent_id" class="erp-input">
                            <option value="">-- بدون (تصنيف رئيسي) --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string)old('parent_id') === (string)$cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="erp-form-group mb-3.5">
                        <label for="catDesc" class="erp-form-label">وصف التصنيف</label>
                        <textarea id="catDesc" name="description" rows="3" class="erp-input" placeholder="وصف محتويات هذا التصنيف من الإكسسوارات...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 accent-[var(--primary)]" />
                            <span class="text-xs text-[var(--text-main)]">تفعيل التصنيف</span>
                        </label>
                    </div>

                    <button type="submit" class="erp-btn erp-btn-primary w-full justify-center inline-flex items-center gap-1.5">
                        <x-icon name="save" size="16" />
                        <span>حفظ التصنيف</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Categories Table -->
        <div class="erp-card p-0 overflow-hidden">
            <div class="erp-card-header px-4 py-3 border-b border-[var(--border-color)]">
                <div class="erp-card-title">
                    <x-icon name="tag" size="18" />
                    <span>قائمة التصنيفات الحالية</span>
                </div>
            </div>

            @if($categories->isEmpty())
                <div class="text-center py-10 px-5">
                    <div class="mb-3 opacity-40 flex justify-center">
                        <x-icon name="tag" size="42" />
                    </div>
                    <h3 class="text-sm font-bold text-[var(--text-main)]">لا توجد تصنيفات مسجلة حتى الآن</h3>
                    <p class="text-xs text-[var(--text-muted)]">استخدم النموذج على اليمين لإضافة أول تصنيف لمخزونك.</p>
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>اسم التصنيف</th>
                                <th class="w-[150px]">التصنيف الرئيسي</th>
                                <th class="w-[120px] text-center">عدد الأصناف</th>
                                <th class="w-[90px] text-center">الحالة</th>
                                <th class="w-[100px] text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td>
                                        <strong class="text-[var(--text-main)] text-sm">{{ $category->name }}</strong>
                                        @if($category->description)
                                            <small class="block text-[var(--text-muted)] text-[11px]">{{ Str::limit($category->description, 60) }}</small>
                                        @endif
                                    </td>

                                    <td>
                                        @if($category->parent)
                                            <span class="erp-badge erp-badge-neutral">{{ $category->parent->name }}</span>
                                        @else
                                            <span class="text-[var(--text-light)] text-[11px]">تصنيف رئيسي</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <span class="erp-badge erp-badge-info font-numeric">
                                            {{ $category->products_count }} صنف
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        @if($category->is_active)
                                            <span class="erp-badge erp-badge-success text-[11px]">نشط</span>
                                        @else
                                            <span class="erp-badge erp-badge-danger text-[11px]">معطل</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" data-confirm="هل أنت متأكد من حذف التصنيف: {{ $category->name }}؟" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)]" 
                                                title="{{ $category->products_count > 0 ? 'لا يمكن حذفه لاحتوائه على منتجات' : 'حذف التصنيف' }}"
                                                {{ $category->products_count > 0 ? 'disabled' : '' }}
                                            >
                                                <x-icon name="trash" size="15" />
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
