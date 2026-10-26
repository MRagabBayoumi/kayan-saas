@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل صلاحيات الدور: {{ $role->name }}" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'الأدوار' => route('roles.index'), 'تعديل الدور' => route('roles.edit', $role)]">
        <x-slot:actions>
            <a href="{{ route('roles.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للأدوار</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div class="w-full">
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

        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Basic Info Card -->
            <div class="erp-card mb-4">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="shield" size="18" />
                        <span>بيانات الدور الأساسية</span>
                    </div>
                </div>
                <div class="erp-card-body">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="erp-form-group">
                            <label for="roleName" class="erp-form-label">
                                اسم الدور الوظيفي <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="text"
                                id="roleName"
                                name="name"
                                value="{{ old('name', $role->name) }}"
                                class="erp-input @error('name') erp-input-error @enderror"
                                required
                            >
                        </div>

                        <div class="erp-form-group lg:col-span-2">
                            <label for="roleDescription" class="erp-form-label">الوصف والمهام الوظيفية</label>
                            <input
                                type="text"
                                id="roleDescription"
                                name="description"
                                class="erp-input"
                                value="{{ old('description', $role->description) }}"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions Matrix by Module -->
            <div class="erp-card">
                <div class="erp-card-header flex-wrap gap-3">
                    <div class="erp-card-title flex items-center gap-2">
                        <x-icon name="check-square" size="18" />
                        <span>مصفوفة الصلاحيات الممنوحة لهذا الدور</span>
                        <span id="selectedPermsCounter" class="erp-badge erp-badge-primary font-numeric text-xs">0 محددة</span>
                    </div>

                    <div class="flex items-center flex-wrap gap-2 ms-auto">
                        <div class="relative min-w-[200px] sm:min-w-[240px]">
                            <input
                                type="text"
                                id="permSearchInput"
                                class="erp-input text-xs py-1.5 pe-8 ps-3"
                                placeholder="بحث سريع في الصلاحيات..."
                            >
                            <x-icon name="search" size="14" class="absolute end-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        </div>
                        <button type="button" class="erp-btn erp-btn-sm erp-btn-ghost text-xs" data-action="select-all-permissions">
                            <x-icon name="check" size="14" />
                            <span>تحديد كافة الصلاحيات</span>
                        </button>
                        <button type="button" class="erp-btn erp-btn-sm erp-btn-ghost text-xs text-rose-600 hover:bg-rose-50" data-action="deselect-all-permissions">
                            <x-icon name="x" size="14" />
                            <span>إلغاء تحديد الكل</span>
                        </button>
                    </div>
                </div>
                <div class="erp-card-body">
                    <div class="role-matrix-container">
                        @foreach($groupedPermissions as $moduleName => $permissions)
                            <div class="role-matrix-module">
                                <div class="role-matrix-header">
                                     <div class="role-matrix-title-group">
                                         <x-icon name="folder" size="16" class="text-[var(--primary)]" />
                                         <span>{{ $moduleName }}</span>
                                         <span class="erp-badge erp-badge-neutral text-[10px]">{{ $permissions->count() }} صلاحية</span>
                                     </div>
                                     <button type="button" class="erp-btn erp-btn-sm erp-btn-ghost" data-action="toggle-module-permissions" title="تحديد / إلغاء تحديد الكل لهذا القسم">
                                         <span>تحديد الكل</span>
                                     </button>
                                </div>

                                <div class="role-matrix-grid">
                                    @foreach($permissions as $perm)
                                        <label class="role-perm-card">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $perm->id }}"
                                                {{ in_array($perm->id, old('permissions', $rolePermissionIds)) ? 'checked' : '' }}
                                                class="role-perm-checkbox"
                                            >
                                            <div class="role-perm-content">
                                                <span class="role-perm-name">{{ $perm->name }}</span>
                                                @if($perm->description)
                                                    <span class="role-perm-desc">{{ $perm->description }}</span>
                                                @endif
                                                <span class="role-perm-slug font-mono text-[10px] text-slate-400">{{ $perm->slug }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="role-form-actions">
                    <a href="{{ route('roles.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>حفظ التعديلات على الدور</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.role-perm-checkbox');
    const counter = document.getElementById('selectedPermsCounter');

    function updateCounter() {
        const checked = document.querySelectorAll('.role-perm-checkbox:checked').length;
        const total = checkboxes.length;
        if (counter) {
            counter.textContent = `${checked} من ${total} محددة`;
        }

        document.querySelectorAll('.role-matrix-module').forEach(module => {
            const moduleChecks = module.querySelectorAll('.role-perm-checkbox');
            const moduleChecked = module.querySelectorAll('.role-perm-checkbox:checked');
            const btn = module.querySelector('[data-action="toggle-module-permissions"]');
            if (btn) {
                btn.textContent = (moduleChecks.length > 0 && moduleChecked.length === moduleChecks.length) ? 'إلغاء التحديد' : 'تحديد الكل';
            }
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });

    document.querySelectorAll('[data-action="toggle-module-permissions"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const module = this.closest('.role-matrix-module');
            if (!module) return;
            const cbs = module.querySelectorAll('.role-perm-checkbox');
            const allChecked = Array.from(cbs).every(c => c.checked);
            cbs.forEach(c => c.checked = !allChecked);
            updateCounter();
        });
    });

    const selectAllBtn = document.querySelector('[data-action="select-all-permissions"]');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            checkboxes.forEach(c => c.checked = true);
            updateCounter();
        });
    }

    const deselectAllBtn = document.querySelector('[data-action="deselect-all-permissions"]');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function () {
            checkboxes.forEach(c => c.checked = false);
            updateCounter();
        });
    }

    // Live Instant Search Filter
    const searchInput = document.getElementById('permSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            document.querySelectorAll('.role-matrix-module').forEach(module => {
                let hasVisibleCard = false;
                module.querySelectorAll('.role-perm-card').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const match = !query || text.includes(query);
                    card.style.display = match ? 'flex' : 'none';
                    if (match) hasVisibleCard = true;
                });
                module.style.display = hasVisibleCard ? 'block' : 'none';
            });
        });
    }

    updateCounter();
});
</script>
@endpush
