<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use App\Models\Tenant;
use App\Services\TenantManagerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request and switch DB to the appropriate tenant based on subdomain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // If no subdomain or root domain (Central SaaS management)
        if (empty($subdomain)) {
            $request->attributes->set('is_central', true);

            return $next($request);
        }

        // Look up the tenant directly from Central DB by unique subdomain index
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant) {
            return response()->view('errors.tenant-not-found', [
                'subdomain' => $subdomain,
            ], 404);
        }

        if (! $tenant->is_active) {
            return response()->view('errors.tenant-suspended', [
                'tenant' => $tenant,
            ], 403);
        }

        if ($tenant->isExpired()) {
            return response()->view('errors.tenant-expired', [
                'tenant' => $tenant,
            ], 403);
        }

        // Switch DB connection dynamically to this tenant's isolated database
        app(TenantManagerService::class)->switchConnection($tenant);
        $request->attributes->set('is_central', false);

        // Synchronize company settings in the tenant database with tenant's registered business name
        try {
            $currentSettings = DB::table('company_settings')->first();
            if (! $currentSettings || empty($currentSettings->company_name) || in_array($currentSettings->company_name, ['Kayan Fashion Accessories', 'شركة كيان للاستيراد والتوزيع'])) {
                DB::table('company_settings')->updateOrInsert(
                    ['id' => 1],
                    [
                        'company_name' => $tenant->name,
                        'updated_at' => now(),
                    ]
                );
                CompanySetting::clearSettingsCache();
            }
        } catch (\Throwable $e) {
        }

        return $next($request);
    }

    /**
     * Extract tenant subdomain from host.
     */
    protected function extractSubdomain(string $host): ?string
    {
        // Strip port if present in host (e.g. localhost:8080 -> localhost)
        $cleanHost = strtolower(explode(':', $host)[0]);

        // Ignore plain IP addresses or root localhost
        if (filter_var($cleanHost, FILTER_VALIDATE_IP) || $cleanHost === 'localhost') {
            return null;
        }

        // Support *.localhost (e.g. client1.localhost -> subdomain is client1)
        if (str_ends_with($cleanHost, '.localhost')) {
            $subdomain = substr($cleanHost, 0, -strlen('.localhost'));
            $parts = explode('.', $subdomain);
            $candidate = $parts[0];

            if ($this->isValidSubdomain($candidate)) {
                return $candidate;
            }

            return null;
        }

        $parts = explode('.', $cleanHost);

        // e.g. client1.lvh.me (3 parts) -> subdomain is client1
        // e.g. client1.kayan-erp.com (3 parts) -> subdomain is client1
        // e.g. client1.kayan.local (3 parts) -> subdomain is client1
        if (count($parts) >= 3) {
            $candidate = strtolower($parts[0]);

            if ($this->isValidSubdomain($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Validate if a subdomain candidate is safe and not a reserved name.
     */
    protected function isValidSubdomain(string $subdomain): bool
    {
        if (in_array($subdomain, ['www', 'app', 'saas', 'admin', 'central', 'mail', 'cpanel', 'api'], true)) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]$/i', $subdomain);
    }
}
