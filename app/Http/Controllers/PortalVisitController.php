<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesRepVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalVisitController extends Controller
{
    /**
     * Display field visits list for the logged-in sales rep.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $query = SalesRepVisit::with(['customer', 'invoice'])
            ->where('sales_rep_id', $user->id);

        if ($request->filled('date')) {
            $query->whereDate('visit_date', $request->input('date'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        $visits = $query->latest('check_in_at')->paginate(15)->withQueryString();

        $todayVisitsCount = SalesRepVisit::where('sales_rep_id', $user->id)
            ->whereDate('visit_date', $today)
            ->count();

        $todayOrdersCount = SalesRepVisit::where('sales_rep_id', $user->id)
            ->whereDate('visit_date', $today)
            ->where('outcome', SalesRepVisit::OUTCOME_ORDER_PLACED)
            ->count();

        $activeVisit = SalesRepVisit::where('sales_rep_id', $user->id)
            ->whereNull('check_out_at')
            ->latest('id')
            ->first();

        return view('portal.visits.index', [
            'visits' => $visits,
            'todayVisitsCount' => $todayVisitsCount,
            'todayOrdersCount' => $todayOrdersCount,
            'activeVisit' => $activeVisit,
        ]);
    }

    /**
     * Show mobile form to start a customer visit with GPS capture.
     */
    public function create(Request $request): View
    {
        $user = auth()->user();

        // Rep can only visit approved customers assigned to them
        $customers = Customer::active()
            ->approved()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('sales_rep_id', $user->id))
            ->orderBy('name')
            ->get();

        $selectedCustomerId = $request->input('customer_id');

        return view('portal.visits.create', [
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
            'purposes' => SalesRepVisit::PURPOSES,
        ]);
    }

    /**
     * Start and check-in to a new customer visit with GPS.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'purpose' => ['required', 'string', 'in:'.implode(',', array_keys(SalesRepVisit::PURPOSES))],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_id.required' => 'يرجى اختيار العميل المراد زيارته.',
            'purpose.required' => 'يرجى تحديد الغرض من الزيارة.',
        ]);

        // Verify customer belongs to rep
        $customer = Customer::findOrFail($validated['customer_id']);
        if (! $user->isAdmin() && $customer->sales_rep_id !== $user->id) {
            abort(403, 'غير مصرح لك بزيارة هذا العميل.');
        }

        $visit = SalesRepVisit::create([
            'sales_rep_id' => $user->id,
            'customer_id' => $customer->id,
            'visit_date' => now()->format('Y-m-d'),
            'check_in_at' => now(),
            'check_in_latitude' => $validated['latitude'] ?? null,
            'check_in_longitude' => $validated['longitude'] ?? null,
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('portal.visits.show', $visit)
            ->with('status', 'success')
            ->with('message', "تم تسجيل الوصول إلى «{$customer->name}» بنجاح والتقاط موقع الـ GPS.");
    }

    /**
     * Show active or completed visit detail screen.
     */
    public function show(SalesRepVisit $visit): View
    {
        $user = auth()->user();
        if (! $user->isAdmin() && $visit->sales_rep_id !== $user->id) {
            abort(403);
        }

        $visit->load(['customer', 'invoice']);

        return view('portal.visits.show', [
            'visit' => $visit,
            'outcomes' => SalesRepVisit::OUTCOMES,
        ]);
    }

    /**
     * Complete and check-out from a visit with outcome and optional photo.
     */
    public function complete(Request $request, SalesRepVisit $visit): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isAdmin() && $visit->sales_rep_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:'.implode(',', array_keys(SalesRepVisit::OUTCOMES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'visit_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'outcome.required' => 'يرجى تحديد نتيجة الزيارة.',
        ]);

        $photoPath = $visit->visit_image_path;
        if ($request->hasFile('visit_photo') && $request->file('visit_photo')->isValid()) {
            $filename = 'visit_'.$visit->id.'_'.time().'.'.$request->file('visit_photo')->getClientOriginalExtension();
            $photoPath = $request->file('visit_photo')->storeAs('visit_photos', $filename, 'public');
        }

        $checkOutAt = now();
        $durationMinutes = (int) $visit->check_in_at->diffInMinutes($checkOutAt);

        $visit->update([
            'check_out_at' => $checkOutAt,
            'duration_minutes' => $durationMinutes,
            'check_out_latitude' => $validated['latitude'] ?? null,
            'check_out_longitude' => $validated['longitude'] ?? null,
            'outcome' => $validated['outcome'],
            'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : $visit->notes,
            'visit_image_path' => $photoPath,
        ]);

        // If outcome is order placed, offer immediate order creation
        if ($validated['outcome'] === SalesRepVisit::OUTCOME_ORDER_PLACED) {
            return redirect()->route('portal.orders.create', ['customer_id' => $visit->customer_id])
                ->with('status', 'success')
                ->with('message', 'تم إنهاء الزيارة بنجاح. يمكنك الآن تحرير وتثبيت طلب البيع للعميل.');
        }

        return redirect()->route('portal.visits.index')
            ->with('status', 'success')
            ->with('message', "تم إنهاء الزيارة بنجاح وتوثيق مدتها ({$durationMinutes} دقيقة) وموقع المغادرة.");
    }
}
