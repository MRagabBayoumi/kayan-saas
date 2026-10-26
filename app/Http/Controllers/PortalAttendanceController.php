<?php

namespace App\Http\Controllers;

use App\Models\SalesRepAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalAttendanceController extends Controller
{
    /**
     * Sales Rep Mobile Attendance Screen.
     */
    public function index(): View
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $todayAttendance = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->whereDate('date', $today)
            ->latest('id')
            ->first();

        $history = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        return view('portal.attendance.index', [
            'todayAttendance' => $todayAttendance,
            'history' => $history,
        ]);
    }

    /**
     * Sales Rep Mobile Check-In with GPS Coordinates.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $existing = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->whereDate('date', $today)
            ->where('status', 'present')
            ->whereNull('check_out_time')
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('status', 'warning')
                ->with('message', 'أنت مسجل حضورك الميداني بالفعل اليوم.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'latitude.required' => 'تحديد الموقع الجغرافي (GPS) إلزامي وأساسي لتسجيل حضور المندوب الميداني. يرجى تفعيل الـ GPS بهاتفك والسماح للمتصفح بالوصول لموقعك.',
            'longitude.required' => 'تحديد الموقع الجغرافي (GPS) إلزامي وأساسي لتسجيل حضور المندوب الميداني.',
        ]);

        SalesRepAttendance::create([
            'sales_rep_id' => $user->id,
            'date' => $today,
            'check_in_time' => now(),
            'check_in_latitude' => $validated['latitude'],
            'check_in_longitude' => $validated['longitude'],
            'check_in_address' => $validated['address'] ?? null,
            'status' => 'present',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()
            ->with('status', 'success')
            ->with('message', 'تم تسجيل حضورك الميداني بنجاح والتقاط موقعك الجغرافي (GPS). نتمنى لك يوماً موفقاً!');
    }

    /**
     * Sales Rep Mobile Check-Out with GPS Coordinates.
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->whereDate('date', $today)
            ->where('status', 'present')
            ->whereNull('check_out_time')
            ->latest('id')
            ->first();

        if (! $attendance) {
            return redirect()->back()
                ->with('status', 'danger')
                ->with('message', 'لا يوجد تسجيل حضور نشط اليوم لإتمام الانصراف.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'latitude.required' => 'تحديد الموقع الجغرافي (GPS) إلزامي لتسجيل الانصراف وإنهاء يوم العمل.',
            'longitude.required' => 'تحديد الموقع الجغرافي (GPS) إلزامي لتسجيل الانصراف وإنهاء يوم العمل.',
        ]);

        $checkOutTime = now();
        $durationMinutes = (int) $attendance->check_in_time->diffInMinutes($checkOutTime);

        $attendance->update([
            'check_out_time' => $checkOutTime,
            'check_out_latitude' => $validated['latitude'] ?? null,
            'check_out_longitude' => $validated['longitude'] ?? null,
            'check_out_address' => $validated['address'] ?? null,
            'work_duration_minutes' => $durationMinutes,
            'status' => 'checked_out',
        ]);

        return redirect()->back()
            ->with('status', 'success')
            ->with('message', "تم تسجيل انصرافك الميداني بنجاح. إجمالي ساعات العمل اليوم: {$attendance->formatted_work_duration}.");
    }
}
