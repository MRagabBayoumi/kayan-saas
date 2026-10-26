<?php

namespace App\Providers;

use App\Listeners\CopyBackupToCustomDirectory;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupZipWasCreated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            static $settings = null;
            static $hasChecked = false;

            if (! $hasChecked) {
                try {
                    if (Schema::hasTable('company_settings')) {
                        $settings = CompanySetting::getSettings();
                    }
                } catch (\Throwable $e) {
                    // Silently fallback if database is not reachable during early boot
                }
                $hasChecked = true;
            }

            $view->with('globalCompanySetting', $settings);
        });

        Blade::if('hasModule', function (string $module) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            return ! $tenant || $tenant->hasModule($module);
        });

        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.simple-custom');

        // Authorization Gates (Kayan ERP Dynamic Roles & Permissions)
        Gate::before(function (User $user, string $ability) {
            if ($ability === 'access-dashboard' && $user->isCashier()) {
                return false;
            }

            if ($user->isAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }
        });

        Gate::define('access-dashboard', fn (User $user) => ! $user->isCashier() && ($user->isAdmin() || $user->hasPermission('access-dashboard')));
        Gate::define('open-shifts', fn (User $user) => $user->hasPermission('open-shifts') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('manage-shifts', fn (User $user) => $user->hasPermission('manage-shifts') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('close-shifts', fn (User $user) => $user->hasPermission('close-shifts') || $user->isAdmin());
        Gate::define('create-customers-pos', fn (User $user) => $user->hasPermission('create-customers-pos') || $user->hasPermission('manage-customers') || $user->isAdmin());
        Gate::define('manage-customers', fn (User $user) => $user->hasPermission('manage-customers') || $user->isAdmin());
        Gate::define('access-customers', fn (User $user) => $user->hasPermission('manage-customers') || $user->hasPermission('create-customers-pos') || $user->hasPermission('access-sales') || $user->isAdmin());
        Gate::define('manage-company-settings', fn (User $user) => $user->hasPermission('manage-company-settings') || $user->isAdmin());
        Gate::define('manage-print-templates', fn (User $user) => $user->hasPermission('manage-print-templates') || $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->hasPermission('manage-users') || $user->isAdmin());
        Gate::define('manage-roles', fn (User $user) => $user->hasPermission('manage-roles') || $user->isAdmin());
        Gate::define('manage-units', fn (User $user) => $user->hasPermission('manage-units') || $user->isAdmin());
        Gate::define('edit-product-prices', fn (User $user) => $user->hasPermission('edit-product-prices') || $user->isAdmin());
        Gate::define('post-purchase-invoice', fn (User $user) => $user->hasPermission('post-purchase-invoice') || $user->isAdmin());
        Gate::define('cancel-purchase-invoice', fn (User $user) => $user->hasPermission('cancel-purchase-invoice') || $user->isAdmin());

        Gate::define('manage-products', fn (User $user) => $user->hasPermission('manage-products') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));
        Gate::define('access-inventory', fn (User $user) => $user->hasPermission('access-inventory') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));
        Gate::define('manage-inventory', fn (User $user) => $user->hasPermission('manage-inventory') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));
        Gate::define('access-purchases', fn (User $user) => $user->hasPermission('access-purchases') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));
        Gate::define('manage-purchases', fn (User $user) => $user->hasPermission('manage-purchases') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));

        Gate::define('access-sales', fn (User $user) => $user->hasPermission('access-sales') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_CASHIER]));
        Gate::define('manage-sales', fn (User $user) => $user->hasPermission('manage-sales') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_CASHIER]));
        Gate::define('post-sales-invoice', fn (User $user) => $user->hasPermission('post-sales-invoice') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('void-sales-invoice', fn (User $user) => $user->hasPermission('void-sales-invoice') || $user->isAdmin());
        Gate::define('view-cogs', fn (User $user) => $user->hasPermission('view-cogs') || $user->isAdmin());

        Gate::define('view-products', fn (User $user) => $user->hasPermission('view-products') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_CASHIER]));
        Gate::define('access-sales-portal', fn (User $user) => $user->isSalesRep() || $user->isAdmin() || $user->hasPermission('access-sales-portal'));
        Gate::define('manage-sales-reps', fn (User $user) => $user->isAdmin() || $user->hasPermission('manage-sales-reps'));

        // Sales Returns & Cashier Operations
        Gate::define('access-sales-returns', fn (User $user) => $user->hasPermission('access-sales-returns') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER, User::ROLE_WAREHOUSE]));
        Gate::define('create-sales-returns', fn (User $user) => $user->hasPermission('create-sales-returns') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('post-sales-return', fn (User $user) => $user->hasPermission('post-sales-return') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('manage-purchase-returns', fn (User $user) => $user->hasPermission('manage-purchase-returns') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_WAREHOUSE]));
        Gate::define('access-shifts', fn (User $user) => $user->hasPermission('access-shifts') || $user->hasRole([User::ROLE_ADMIN, User::ROLE_CASHIER]));
        Gate::define('unpost-sales-invoice', fn (User $user) => $user->hasPermission('unpost-sales-invoice') || $user->isAdmin());
        Gate::define('apply-sales-discounts', fn (User $user) => $user->hasPermission('apply-sales-discounts') || $user->isAdmin() || $user->hasRole(User::ROLE_CASHIER));
        Gate::define('override-credit-limit', fn (User $user) => $user->hasPermission('override-credit-limit') || $user->isAdmin());
        Gate::define('approve-rep-customers', fn (User $user) => $user->hasPermission('approve-rep-customers') || $user->isAdmin());
        Gate::define('track-rep-gps', fn (User $user) => $user->hasPermission('track-rep-gps') || $user->isAdmin());

        // Auto copy backup zip to optional custom folder
        Event::listen(
            BackupZipWasCreated::class,
            CopyBackupToCustomDirectory::class
        );
    }
}
