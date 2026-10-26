<?php

namespace App\Http\Controllers;

use App\Models\SalesRepVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesRepVisitController extends Controller
{
    /**
     * Admin report and monitoring of all sales reps field visits, outcomes, and GPS locations.
     */
    public function index(Request $request): View
    {
        $today = now()->format('Y-m-d');
        $selectedDate = $request->input('date', $today);

        $query = SalesRepVisit::with(['salesRep', 'customer', 'invoice']);

        if ($selectedDate !== 'all') {
            $query->whereDate('visit_date', $selectedDate);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', (int) $request->input('sales_rep_id'));
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->input('outcome'));
        }

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->input('purpose'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->whereHas('customer', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $visits = $query->latest('check_in_at')->paginate(20)->withQueryString();

        $todayVisitsCount = SalesRepVisit::whereDate('visit_date', $today)->count();
        $todayOrdersPlacedCount = SalesRepVisit::whereDate('visit_date', $today)
            ->where('outcome', SalesRepVisit::OUTCOME_ORDER_PLACED)
            ->count();
        $activeVisitsCount = SalesRepVisit::whereNull('check_out_at')->count();

        $salesReps = User::salesReps()->active()->orderBy('name')->get();

        return view('sales-reps.visits.index', [
            'visits' => $visits,
            'selectedDate' => $selectedDate,
            'salesReps' => $salesReps,
            'selectedRep' => $request->input('sales_rep_id'),
            'selectedOutcome' => $request->input('outcome'),
            'selectedPurpose' => $request->input('purpose'),
            'currentSearch' => $request->input('search'),
            'todayVisitsCount' => $todayVisitsCount,
            'todayOrdersPlacedCount' => $todayOrdersPlacedCount,
            'activeVisitsCount' => $activeVisitsCount,
            'purposes' => SalesRepVisit::PURPOSES,
            'outcomes' => SalesRepVisit::OUTCOMES,
        ]);
    }
}
