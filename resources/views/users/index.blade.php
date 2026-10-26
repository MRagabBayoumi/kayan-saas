@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة المستخدمين وحسابات الدخول" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'المستخدمون' => route('users.index')]">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مستخدم جديد</span>
            </a>
            <a href="{{ route('roles.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shield" size="16" />
                <span>الأدوار ومصفوفة الصلاحيات</span>
            </a>
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary" title="إدارة مناديب المبيعات الميدانيين">
                <x-icon name="users" size="16" class="text-[var(--primary)]" />
                <span>إدارة مناديب المبيعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @php
                $currentTenant = app()->bound('tenant') ? app('tenant') : null;
            @endphp
            @if($currentTenant)
                <span class="erp-badge {{ $users->total() >= $currentTenant->max_users ? 'erp-badge-danger' : 'erp-badge-neutral' }} font-numeric px-2.5 py-1 text-xs">
                    <span>المستخدمون المستهلكون:</span>
                    <strong>{{ $users->total() }} / {{ $currentTenant->max_users }}</strong>
                    <span class="text-slate-400 mr-1">({{ $currentTenant->plan_name }})</span>
                </span>
            @else
                <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                    <span>مستخدمو النظام:</span>
                    <strong>{{ $users->total() }}</strong>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="users">
        @if(session('info'))
            <div class="erp-alert erp-alert-info mb-4" role="alert">
                <x-icon name="info" size="16" />
                <div>{{ session('info') }}</div>
            </div>
        @endif

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

        <div class="p-3 mb-4 rounded-md bg-amber-50/70 border border-amber-200 text-amber-900 text-xs flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <x-icon name="info" size="16" class="text-amber-600 shrink-0" />
                <span>هذه الصفحة مخصصة لمستخدمي النظام (المدير، أمناء المخازن، الكاشير). مناديب المبيعات مفصولون تماماً ويدارون من صفحة إدارة المناديب.</span>
            </div>
            <a href="{{ route('sales-reps.index') }}" class="font-bold text-[var(--primary)] hover:underline inline-flex items-center gap-1">
                <span>الانتقال لإدارة المناديب &larr;</span>
            </a>
        </div>

        <!-- Summary KPI Cards -->
        <div class="users-kpi-grid">
            <div class="users-kpi-card">
                <div class="users-kpi-icon primary">
                    <x-icon name="users" size="22" />
                </div>
                <div class="users-kpi-info">
                    <div class="users-kpi-label">إجمالي حسابات المستخدمين</div>
                    <div class="users-kpi-value font-numeric">{{ $users->total() }}</div>
                </div>
            </div>

            <div class="users-kpi-card">
                <div class="users-kpi-icon success">
                    <x-icon name="check-circle" size="22" />
                </div>
                <div class="users-kpi-info">
                    <div class="users-kpi-label">الحسابات النشطة والمفعلة</div>
                    <div class="users-kpi-value font-numeric">{{ $users->where('is_active', true)->count() }}</div>
                </div>
            </div>

            <div class="users-kpi-card">
                <div class="users-kpi-icon warning">
                    <x-icon name="alert-triangle" size="22" />
                </div>
                <div class="users-kpi-info">
                    <div class="users-kpi-label">الحسابات المعطلة</div>
                    <div class="users-kpi-value font-numeric">{{ $users->where('is_active', false)->count() }}</div>
                </div>
            </div>

            <div class="users-kpi-card">
                <div class="users-kpi-icon info">
                    <x-icon name="shield" size="22" />
                </div>
                <div class="users-kpi-info">
                    <div class="users-kpi-label">مسؤولو النظام (Admins)</div>
                    <div class="users-kpi-value font-numeric">{{ $users->filter(fn($u) => $u->isAdmin())->count() }}</div>
                </div>
            </div>
        </div>

        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="users" size="18" />
                    <span>قائمة المستخدمين المسجلين في النظام</span>
                </div>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>اسم المستخدم</th>
                            <th>البريد الإلكتروني للدخول</th>
                            <th>الدور الوظيفي</th>
                            <th>مكان العمل / المستودع</th>
                            <th>حالة الحساب</th>
                            <th>تاريخ الإنشاء</th>
                            <th class="w-32 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="font-numeric text-center">{{ $user->id }}</td>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="user-profile-badge">
                                            {{ mb_substr($user->name, 0, 1) }}
                                        </div>
                                        <span class="font-bold text-[var(--text-main)]">{{ $user->name }}</span>
                                        @if($user->id === auth()->id())
                                            <span class="erp-badge erp-badge-primary text-[10px] px-1.5 py-0.5">أنت</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="font-numeric text-[var(--text-muted)] text-xs">{{ $user->email }}</span>
                                </td>
                                <td>
                                    @if($user->isAdmin())
                                        <span class="erp-badge erp-badge-primary">{{ $user->role_label }}</span>
                                    @elseif($user->isWarehouse())
                                        <span class="erp-badge erp-badge-warning">{{ $user->role_label }}</span>
                                    @else
                                        <span class="erp-badge erp-badge-neutral">{{ $user->role_label }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->isAdmin() || $user->can_access_all_warehouses)
                                        <span class="erp-badge erp-badge-info text-xs">
                                            <x-icon name="globe" size="12" />
                                            <span>كافة المستودعات</span>
                                        </span>
                                    @elseif($user->warehouse)
                                        <span class="erp-badge erp-badge-neutral text-xs">
                                            <x-icon name="home" size="12" />
                                            <span>{{ $user->warehouse->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-[var(--text-muted)] text-xs">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_active ?? true)
                                        <span class="erp-badge erp-badge-success">مفعل</span>
                                    @else
                                        <span class="erp-badge erp-badge-danger">معطل</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-numeric text-[var(--text-muted)] text-xs">{{ $user->created_at?->format('Y-m-d') ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('users.edit', $user) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="تعديل المستخدم">
                                            <x-icon name="edit" size="14" />
                                            <span>تعديل</span>
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" data-confirm="هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء." class="inline m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="حذف المستخدم">
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
                                    لا يوجد مستخدمون مسجلون.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-3 border-t border-[var(--border-color)]">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
