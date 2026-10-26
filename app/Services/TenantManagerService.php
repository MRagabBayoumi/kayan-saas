<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantManagerService
{
    /**
     * Create a new tenant, its isolated physical database, runs migrations, and creates initial admin.
     */
    public function createTenant(array $data): Tenant
    {
        $subdomain = Str::slug($data['subdomain']);
        // Security: enforce strict clean characters
        $safeSubdomain = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', $subdomain));
        $dbName = 'kayan_tenant_'.$safeSubdomain;

        // 1. Create the physical database in MySQL safely
        DB::connection('central')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Resolve plan data
        $plan = null;
        if (! empty($data['plan_id'])) {
            $plan = Plan::find($data['plan_id']);
        } elseif (! empty($data['plan_code'])) {
            $plan = Plan::where('code', $data['plan_code'])->first();
        }

        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        $maxUsers = $data['max_users'] ?? ($plan?->max_users ?? 3);
        $planName = $plan?->name ?? ($data['plan_name'] ?? 'الباقة المخصصة');
        $price = $data['subscription_price'] ?? ($billingCycle === 'yearly' ? ($plan?->price_yearly ?? 0) : ($plan?->price_monthly ?? 0));
        $startsAt = now();
        $expiresAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addDays(30);

        $modules = $data['modules'] ?? ($plan?->modules ?? [
            'accounting' => true,
            'sales_reps' => true,
            'installments' => true,
        ]);

        // 2. Register Tenant in central database
        $tenant = Tenant::create([
            'name' => $data['name'],
            'subdomain' => $subdomain,
            'database_name' => $dbName,
            'plan_id' => $plan?->id,
            'plan_name' => $planName,
            'max_users' => $maxUsers,
            'billing_cycle' => $billingCycle,
            'subscription_price' => $price,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'modules' => $modules,
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        // 3. Migrate the new tenant database with all ERP tables
        $this->migrateTenantDatabase($dbName);

        // 4. Seed default admin user inside the tenant's database
        $this->seedTenantAdmin($dbName, $data['admin_email'] ?? "admin@{$subdomain}.local", $data['admin_password'] ?? 'admin123', $data['name']);

        return $tenant;
    }

    /**
     * Run all ERP migrations on the tenant's database.
     */
    public function migrateTenantDatabase(string $dbName): void
    {
        // Temporarily configure a dedicated migration connection
        Config::set('database.connections.tenant_migration', array_merge(
            Config::get('database.connections.mysql'),
            ['database' => $dbName]
        ));

        DB::purge('tenant_migration');

        Artisan::call('migrate', [
            '--database' => 'tenant_migration',
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    /**
     * Seed initial admin user in the newly created tenant DB.
     */
    public function seedTenantAdmin(string $dbName, string $email, string $password, string $companyName): void
    {
        Config::set('database.connections.tenant_migration.database', $dbName);
        DB::purge('tenant_migration');

        // Check if admin user exists, otherwise create
        $exists = DB::connection('tenant_migration')->table('users')->where('username', 'admin')->exists();
        if (! $exists) {
            DB::connection('tenant_migration')->table('users')->insert([
                'name' => 'مدير النظام',
                'username' => 'admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set initial company settings with the tenant's company name
        $hasSettings = DB::connection('tenant_migration')->table('company_settings')->exists();
        if (! $hasSettings) {
            DB::connection('tenant_migration')->table('company_settings')->insert([
                'company_name' => $companyName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set default warehouse
        $hasWarehouse = DB::connection('tenant_migration')->table('warehouses')->exists();
        if (! $hasWarehouse) {
            DB::connection('tenant_migration')->table('warehouses')->insert([
                'code' => 'WH-MAIN',
                'name' => 'المستودع الرئيسي',
                'location' => 'المقر الرئيسي',
                'is_active' => true,
                'is_default' => true,
                'notes' => 'المستودع الافتراضي للنظام',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Run seeders for default units, print templates, roles & permissions, and chart of accounts
        Config::set('database.connections.mysql.database', $dbName);
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::setDefaultConnection('mysql');

        try {
            if (DB::table('roles')->count() === 0) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder', '--force' => true]);
            }
            if (DB::table('units')->count() === 0) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\UnitSeeder', '--force' => true]);
            }
            if (DB::table('print_templates')->count() === 0) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PrintTemplateSeeder', '--force' => true]);
            }
            if (DB::table('accounts')->count() === 0) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ChartOfAccountsSeeder', '--force' => true]);
            }
        } catch (\Throwable $e) {
            // Silently log or ignore seeder conflicts if any already ran
        }
    }

    /**
     * Switch application default database to the tenant database.
     */
    public function switchConnection(Tenant $tenant): void
    {
        // Security: Strictly whitelist database name format to prevent arbitrary database access
        if (! preg_match('/^kayan_tenant_[a-z0-9_]{1,64}$/', $tenant->database_name)) {
            throw new \RuntimeException('Security Exception: Invalid tenant database name detected.');
        }

        Config::set('database.connections.mysql.database', $tenant->database_name);
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::setDefaultConnection('mysql');

        app()->instance('tenant', $tenant);
    }
}
