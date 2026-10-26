<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesRepScopedToPortal
{
    /**
     * Handle an incoming request.
     * Ensure sales representatives are strictly confined to the field portal interface.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSalesRep() && ! $user->isAdmin()) {
            if (! $request->is('portal*') && ! $request->is('logout') && ! $request->is('_boots*')) {
                return redirect()->route('portal.index');
            }
        }

        return $next($request);
    }
}
