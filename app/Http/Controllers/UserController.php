<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of system users.
     */
    public function index(): View
    {
        $users = User::where('role', '!=', User::ROLE_SALES_REP)
            ->with(['roleRelation', 'warehouse'])
            ->orderBy('id')
            ->paginate(15);

        return view('users.index', [
            'title' => 'إدارة المستخدمين وحسابات الدخول',
            'moduleName' => 'الإدارة والنظام',
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View|RedirectResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant && ! $tenant->canAddUser(User::count())) {
            return redirect()->route('users.index')
                ->with('error', "عفواً، لقد بلغت الحد الأقصى للمستخدمين المسموح به في باقتك الحالية ({$tenant->max_users} مستخدمين). يرجى ترقية باقة الاشتراك لإضافة مستخدمين جدد.");
        }

        $roles = Role::where('slug', '!=', User::ROLE_SALES_REP)->orderBy('id')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('users.create', [
            'title' => 'إضافة مستخدم جديد',
            'moduleName' => 'الإدارة والنظام',
            'roles' => $roles,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant && ! $tenant->canAddUser(User::count())) {
            return redirect()->route('users.index')
                ->with('error', "عفواً، لقد بلغت الحد الأقصى للمستخدمين المسموح به في باقتك الحالية ({$tenant->max_users} مستخدمين). يرجى ترقية باقة الاشتراك لإضافة مستخدمين جدد.");
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'phone' => ['nullable', 'string', 'max:35'],
            'can_access_all_warehouses' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل لمستخدم آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'role_id.required' => 'يرجى اختيار دور المستخدم.',
            'role_id.exists' => 'الدور المختار غير موجود في النظام.',
            'warehouse_id.exists' => 'المستودع المختار غير صحيح.',
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if ($role->slug === User::ROLE_SALES_REP) {
            return redirect()->route('sales-reps.create')
                ->with('info', 'تسجيل وإدارة حسابات مناديب المبيعات يتم بشكل مخصص ومستقل عبر صفحة مناديب المبيعات.');
        }

        $username = filled($request->input('username'))
            ? Str::slug($request->input('username'), '_')
            : Str::slug(explode('@', $validated['email'])[0], '_');
        if (empty($username)) {
            $username = 'user_'.time();
        }
        $candidate = $username;
        $counter = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $username.$counter++;
        }

        User::create([
            'name' => $validated['name'],
            'username' => $candidate,
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'role' => $role->slug, // Keep synced for backward compatibility
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'commission_rate' => 0.00,
            'commission_type' => 'percentage',
            'can_access_all_warehouses' => $request->boolean('can_access_all_warehouses', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تمت إضافة المستخدم بنجاح.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = Role::where('slug', '!=', User::ROLE_SALES_REP)->orderBy('id')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('users.edit', [
            'title' => 'تعديل بيانات المستخدم: '.$user->name,
            'moduleName' => 'الإدارة والنظام',
            'user' => $user,
            'roles' => $roles,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'phone' => ['nullable', 'string', 'max:35'],
            'can_access_all_warehouses' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل لمستخدم آخر.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'role_id.required' => 'يرجى اختيار دور المستخدم.',
            'warehouse_id.exists' => 'المستودع المختار غير صحيح.',
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if ($role->slug === User::ROLE_SALES_REP) {
            return redirect()->route('sales-reps.index')
                ->with('info', 'مناديب المبيعات يتم تعديلهم وحساب عمولاتهم من صفحة إدارة المناديب.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'role_id' => $role->id,
            'role' => $role->slug,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'commission_rate' => array_key_exists('commission_rate', $validated) ? (float) $validated['commission_rate'] : $user->commission_rate,
            'commission_type' => $validated['commission_type'] ?? $user->commission_type ?? 'percentage',
            'can_access_all_warehouses' => $request->boolean('can_access_all_warehouses', false),
            'is_active' => $request->boolean('is_active', true),
        ];

        // Only update password if a new one was provided
        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Prevent admin from deactivating their own account
        if ($user->id === auth()->id() && ! $updateData['is_active']) {
            return back()->withInput()->withErrors(['is_active' => 'لا يمكنك تعطيل حسابك الحالي أثناء تسجيل الدخول منه.']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكنك حذف حسابك الشخصي الحالي.');
        }

        // Prevent deleting the last remaining admin
        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()->route('users.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف مدير النظام الأخير في المؤسسة.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تم حذف المستخدم بنجاح.');
    }
}
