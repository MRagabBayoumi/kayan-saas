<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManagerService;
use Closure;
use Illuminate\Http\Request;
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

            if ($request->is('login')) {
                return redirect()->route('saas.index');
            }

            return $next($request);
        }

        // Look up the tenant
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

        return $next($request);
    }

    /**
     * Extract tenant subdomain from host.
     */
    protected function extractSubdomain(string $host): ?string
    {
        // Ignore plain IP addresses or localhost
        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return null;
        }

        $parts = explode('.', $host);

        // e.g. client1.lvh.me (3 parts) -> subdomain is client1
        // e.g. client1.kayan-erp.com (3 parts) -> subdomain is client1
        // e.g. client1.kayan.local (3 parts) -> subdomain is client1
        if (count($parts) >= 3) {
            $subdomain = strtolower($parts[0]);
            if (in_array($subdomain, ['www', 'app', 'saas', 'admin', 'central', 'mail', 'cpanel', 'api'], true)) {
                return null;
            }

            // Security: Strictly allow alphanumeric and hyphens only (prevent path traversal / injection)
            if (! preg_match('/^[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]$/i', $subdomain)) {
                return null;
            }

            return $subdomain;
        }

        return null;
    }
}
