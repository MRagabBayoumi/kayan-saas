<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Admin User (Credentials read from .env)
        $adminEmail = env('ADMIN_DEFAULT_EMAIL', 'admin@kayan-erp.com');
        $existingAdmin = User::where('email', $adminEmail)->first();

        if (! $existingAdmin) {
            $adminPassword = env('ADMIN_DEFAULT_PASSWORD');
            if (empty($adminPassword)) {
                throw new \RuntimeException('ADMIN_DEFAULT_PASSWORD must be explicitly configured in the environment before seeding the initial admin account.');
            }

            $adminName = env('ADMIN_DEFAULT_NAME', 'مدير النظام');

            User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => User::ROLE_ADMIN,
            ]);
        }

        // Default Main Warehouse
        Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'المستودع الرئيسي - العتبة',
                'location' => 'شارع عبد العزيز - العتبة - القاهرة',
                'is_active' => true,
                'is_default' => true,
                'notes' => 'المستودع المركزي لفرز وتوزيع الإكسسوارات',
            ]
        );

        // Default Print Templates (4 document types x 2 paper formats = 8 defaults)
        $this->call(PrintTemplateSeeder::class);

        // Dynamic Roles, Permissions & Default Units
        $this->call(RolePermissionSeeder::class);
        $this->call(UnitSeeder::class);
    }
}
