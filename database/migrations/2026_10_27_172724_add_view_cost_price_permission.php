<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $perm = Permission::firstOrCreate(
            ['slug' => 'view-cost-price'],
            [
                'name' => 'عرض سعر الشراء والتكلفة',
                'module' => 'المنتجات والتسعير',
                'description' => 'الاطلاع على أسعار الشراء وتكلفة المنتجات في جداول وبطاقات الأصناف',
            ]
        );

        // Attach to Admin
        $admin = Role::where('slug', 'admin')->first();
        if ($admin && ! $admin->permissions()->where('permissions.id', $perm->id)->exists()) {
            $admin->permissions()->attach($perm->id);
        }

        // Attach to Warehouse
        $warehouse = Role::where('slug', 'warehouse')->first();
        if ($warehouse && ! $warehouse->permissions()->where('permissions.id', $perm->id)->exists()) {
            $warehouse->permissions()->attach($perm->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('slug', 'view-cost-price')->first();
        if ($perm) {
            $perm->roles()->detach();
            $perm->delete();
        }
    }
};
