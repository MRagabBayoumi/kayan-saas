<?php

namespace App\Http\Controllers;

use App\Models\SalesRepAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesRepAttendanceController extends Controller
{
    /**
     * Admin view of sales reps daily attendance, work hours, and GPS locations.
     */
    public function index(Request $request): View
    {
        $today = now()->format('Y-m-d');
        $selectedDate = $request->input('date', $today);

        $query = SalesRepAttendance::with('salesRep');

        if ($selectedDate !== 'all') {
            $query->whereDate('date', $selectedDate);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', (int) $request->input('sales_rep_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $attendances = $query->latest('check_in_time')->paginate(20)->withQueryString();

        $activeRepsCount = User::salesReps()->active()->count();
        $presentTodayCount = SalesRepAttendance::whereDate('date', $today)->where('status', 'present')->count();
        $checkedOutTodayCount = SalesRepAttendance::whereDate('date', $today)->where('status', 'checked_out')->count();

        $salesReps = User::salesReps()->active()->orderBy('name')->get();

        return view('sales-reps.attendance.index', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'salesReps' => $salesReps,
            'selectedRep' => $request->input('sales_rep_id'),
            'selectedStatus' => $request->input('status'),
            'activeRepsCount' => $activeRepsCount,
            'presentTodayCount' => $presentTodayCount,
            'checkedOutTodayCount' => $checkedOutTodayCount,
        ]);
    }
}
