<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    /**
     * Ensure request is accessing from the central domain, not from an isolated tenant subdomain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get('is_central', false)) {
            return response()->view('errors.403-standalone', [
                'message' => 'غير مصرح: لوحة الإدارة المركزية متاحة فقط عبر النطاق الرئيسي للمنصة ولا يمكن الوصول إليها من نطاق المشتركين الفرعي.',
            ], 403);
        }

        return $next($request);
    }
}
