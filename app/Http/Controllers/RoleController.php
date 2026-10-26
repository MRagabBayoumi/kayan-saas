<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of system roles with their permission counts.
     */
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('id')
            ->get();

        return view('roles.index', [
            'title' => 'إدارة الأدوار ومصفوفة الصلاحيات',
            'moduleName' => 'الإدارة والنظام',
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        $permissions = Permission::all()->groupBy('module');

        return view('roles.create', [
            'title' => 'إضافة دور جديد وتحديد صلاحياته',
            'moduleName' => 'الإدارة والنظام',
            'groupedPermissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.required' => 'اسم الدور مطلوب.',
            'name.unique' => 'اسم الدور مستخدم بالفعل في دور آخر.',
            'permissions.*.exists' => 'إحدى الصلاحيات المحددة غير موجودة في النظام.',
        ]);

        $slug = Str::slug($validated['name'], '_', null) ?: 'role_'.Str::random(6);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')
            ->with('status', 'success')
            ->with('message', 'تم إنشاء الدور الجديد وتعيين صلاحياته بنجاح.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        $groupedPermissions = Permission::all()->groupBy('module');
        $rolePermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        return view('roles.edit', [
            'title' => 'تعديل صلاحيات الدور: '.$role->name,
            'moduleName' => 'الإدارة والنظام',
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'rolePermissionIds' => $rolePermissionIds,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.required' => 'اسم الدور مطلوب.',
            'name.unique' => 'اسم الدور مستخدم بالفعل في دور آخر.',
        ]);

        // If system role, keep original slug; else allow updating name/desc
        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sync selected permissions
        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث صلاحيات الدور بنجاح.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->route('roles.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف الأدوار الأساسية للنظام (System Roles).');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف هذا الدور لوجود مستخدمين مسندين إليه حالياً. يرجى نقل المستخدمين إلى دور آخر أولاً.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('status', 'success')
            ->with('message', 'تم حذف الدور بنجاح.');
    }
}
