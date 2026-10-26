<?php

namespace App\Http\Middleware;

use App\Models\SalesRepAttendance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAttendanceSession
{
    /**
     * Handle an incoming request.
     * Enforces that the sales representative has an active, GPS-verified attendance session for today
     * before being permitted to register customer requests, create field orders, or record visits.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If unauthenticated or administrator, pass through
        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        // Check for active attendance session today with valid GPS coordinates
        $today = now()->format('Y-m-d');
        $activeSession = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->whereDate('date', $today)
            ->where('status', 'present')
            ->whereNotNull('check_in_latitude')
            ->whereNotNull('check_in_longitude')
            ->whereNull('check_out_time')
            ->latest('id')
            ->first();

        if (! $activeSession) {
            $message = 'جلسة العمل غير مفتوحة: يجب تسجيل حضورك الميداني وتوثيق موقعك الجغرافي (GPS) أولاً لفتح جلسة العمل وبدء تسجيل الطلبات أو العملاء.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'status' => 'ATTENDANCE_REQUIRED',
                    'message' => $message,
                    'redirect_url' => route('portal.attendance.index'),
                ], 403);
            }

            return redirect()->route('portal.attendance.index')
                ->with('status', 'warning')
                ->with('message', $message);
        }

        return $next($request);
    }
}
