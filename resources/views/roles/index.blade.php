@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة الأدوار ومصفوفة الصلاحيات" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'المستخدمون' => route('users.index'), 'الأدوار والصلاحيات' => route('roles.index')]">
        <x-slot:actions>
            <a href="{{ route('roles.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة دور جديد</span>
            </a>
            <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>إدارة المستخدمين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الأدوار:</span>
                <strong>{{ $roles->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="roles">
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

        <!-- Summary KPI Cards -->
        <div class="roles-kpi-grid">
            <div class="roles-kpi-card">
                <div class="roles-kpi-icon primary">
                    <x-icon name="shield" size="22" />
                </div>
                <div class="roles-kpi-info">
                    <div class="roles-kpi-label">إجمالي الأدوار المعرفة</div>
                    <div class="roles-kpi-value font-numeric">{{ $roles->count() }}</div>
                </div>
            </div>

            <div class="roles-kpi-card">
                <div class="roles-kpi-icon success">
                    <x-icon name="lock" size="22" />
                </div>
                <div class="roles-kpi-info">
                    <div class="roles-kpi-label">أدوار النظام الأساسية</div>
                    <div class="roles-kpi-value font-numeric">{{ $roles->where('is_system', true)->count() }}</div>
                </div>
            </div>

            <div class="roles-kpi-card">
                <div class="roles-kpi-icon info">
                    <x-icon name="sliders" size="22" />
                </div>
                <div class="roles-kpi-info">
                    <div class="roles-kpi-label">أدوار مخصصة ديناميكية</div>
                    <div class="roles-kpi-value font-numeric">{{ $roles->where('is_system', false)->count() }}</div>
                </div>
            </div>

            <div class="roles-kpi-card">
                <div class="roles-kpi-icon warning">
                    <x-icon name="check-square" size="22" />
                </div>
                <div class="roles-kpi-info">
                    <div class="roles-kpi-label">إجمالي مستخدمي الأدوار</div>
                    <div class="roles-kpi-value font-numeric">{{ $roles->sum('users_count') }}</div>
                </div>
            </div>
        </div>

        <!-- Roles Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="shield" size="18" />
                    <span>قائمة الأدوار الوظيفية ومستويات الصلاحيات في المؤسسة</span>
                </div>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>اسم الدور الوظيفي</th>
                            <th>الرمز البرمجي (Slug)</th>
                            <th>طبيعة الدور والمسؤوليات</th>
                            <th class="text-center">الصلاحيات الممنوحة</th>
                            <th class="text-center">المستخدمون المسندون</th>
                            <th class="w-32 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="font-numeric text-center">{{ $role->id }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-icon name="shield" size="16" class="text-[var(--primary)]" />
                                        <span class="font-bold text-[var(--text-main)]">{{ $role->name }}</span>
                                        @if($role->is_system)
                                            <span class="erp-badge erp-badge-primary text-[10px] px-1.5 py-0.5">أساسي</span>
                                        @else
                                            <span class="erp-badge erp-badge-neutral text-[10px] px-1.5 py-0.5">مخصص</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <code class="text-xs bg-[var(--bg-canvas)] px-1.5 py-0.5 rounded border border-[var(--border-color)] font-mono">{{ $role->slug }}</code>
                                </td>
                                <td class="text-xs text-[var(--text-muted)] max-w-xs">
                                    {{ $role->description ?? 'لا يوجد وصف مخصص' }}
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge erp-badge-info font-numeric">
                                        {{ $role->permissions_count }} صلاحية
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge erp-badge-neutral font-numeric">
                                        {{ $role->users_count }} مستخدم
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('roles.edit', $role) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="تعديل صلاحيات الدور">
                                            <x-icon name="edit" size="14" />
                                            <span>تعديل الصلاحيات</span>
                                        </a>
                                        @if(! $role->is_system && $role->users_count === 0)
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" data-confirm="هل أنت متأكد من حذف هذا الدور؟ لا يمكن التراجع عن هذا الإجراء." class="inline m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="حذف الدور">
                                                    <x-icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-[var(--text-muted)]">
                                    لا توجد أدوار مسجلة في النظام.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
