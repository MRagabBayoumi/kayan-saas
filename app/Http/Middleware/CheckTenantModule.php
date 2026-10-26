<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModule
{
    /**
     * Handle an incoming request and ensure the tenant's subscription includes this module.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && ! $tenant->hasModule($module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "موديول {$module} غير مفعل في باقة اشتراكك الحالية.",
                ], 403);
            }

            return response()->view('errors.module-disabled', [
                'module' => $module,
                'tenant' => $tenant,
            ], 403);
        }

        return $next($request);
    }
}
