<?php

use App\Http\Middleware\CheckTenantModule;
use App\Http\Middleware\EnsureActiveAttendanceSession;
use App\Http\Middleware\EnsureSalesRepScopedToPortal;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            '_boots/*',
        ]);
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->user()?->isSalesRep()) {
                return route('portal.index');
            }

            return route('dashboard');
        });
        $middleware->alias([
            'attendance.required' => EnsureActiveAttendanceSession::class,
            'module' => CheckTenantModule::class,
        ]);
        $middleware->web(prepend: [
            IdentifyTenant::class,
        ], append: [
            SecurityHeadersMiddleware::class,
            EnsureSalesRepScopedToPortal::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
