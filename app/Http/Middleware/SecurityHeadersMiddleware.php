<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and enforce strict HTTP security headers
     * as well as blocking CGI argument injection attacks (CVE-2024-4577 & RCE probes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Block PHP-CGI argument injection probes (e.g. ?-d+allow_url_include=1 or auto_prepend_file)
        $queryString = (string) ($request->getQueryString() ?: $request->server('QUERY_STRING', ''));
        $rawUri = (string) $request->server('REQUEST_URI', '');
        $combinedQuery = $queryString.' '.$rawUri;

        if ($combinedQuery !== ' ') {
            if (
                preg_match('/(^|[&?\s])-[dswb]/i', $combinedQuery) ||
                stripos($combinedQuery, 'auto_prepend_file') !== false ||
                stripos($combinedQuery, 'allow_url_include') !== false ||
                stripos($combinedQuery, 'php://input') !== false
            ) {
                abort(400, 'Bad Request: Potentially malicious query parameters detected.');
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // 2. Enforce standard HTTP Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=()');

        // Remove PHP version exposure if present in header
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
