<?php

namespace App\Http\Controllers;

use App\Models\CashierCashMovement;
use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\ExpenseCategory;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Printing\PythonPrintEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CashierShiftController extends Controller
{
    /**
     * Display a listing of cashier shifts.
     */
    public function index(Request $request): View
    {
        $query = CashierShift::with(['user', 'openedBy', 'warehouse', 'closedBy'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('opened_at', $request->input('date'));
        }

        $shifts = $query->paginate(15)->withQueryString();

        $warehouses = Warehouse::active()->orderBy('name')->get();
        $cashiers = User::orderBy('name')->get();
        $isManager = auth()->user()?->isAdmin() || auth()->user()?->can('close-shifts');
        $eligibleCashiers = User::active()->orderBy('name')->get();
        $expenseCategories = ExpenseCategory::active()->orderBy('name')->get();

        $metrics = [
            'open_shifts_count' => CashierShift::open()->count(),
            'total_open_expected_cash' => (float) CashierShift::open()->sum('expected_closing_balance'),
            'total_sales_today' => (float) CashierShift::whereDate('opened_at', today())->sum('cash_sales_amount'),
            'total_difference' => (float) CashierShift::closed()->whereDate('opened_at', today())->sum('difference'),
        ];

        $currentActiveShift = CashierShift::getActiveShift(auth()->id());

        return view('shifts.index', [
            'title' => 'ورديات وخزينة الكاشير',
            'moduleName' => 'ورديات الكاشير',
            'shifts' => $shifts,
            'warehouses' => $warehouses,
            'cashiers' => $cashiers,
            'eligibleCashiers' => $eligibleCashiers,
            'expenseCategories' => $expenseCategories,
            'isManager' => $isManager,
            'metrics' => $metrics,
            'currentActiveShift' => $currentActiveShift,
            'currentStatus' => $request->input('status'),
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentUserId' => $request->input('user_id'),
            'currentDate' => $request->input('date'),
        ]);
    }

    /**
     * Open a new shift for the authenticated cashier or on behalf of an eligible cashier by manager.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $auth = auth()->user();
        $isManager = $auth->isAdmin() || $auth->can('close-shifts');

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Determine target assigned cashier
        if (! empty($validated['user_id']) && (int) $validated['user_id'] !== (int) $auth->id) {
            if (! $isManager) {
                $msg = 'غير مصرح لك بفتح وردية نيابة عن كاشير آخر. هذه الصلاحية مقتصرة على مدراء النظام والمشرفين.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }

                return redirect()->back()->with('error', $msg);
            }
            $assignedUser = User::findOrFail((int) $validated['user_id']);
        } else {
            $assignedUser = $auth;
        }

        // Check if assigned cashier already has an active open shift
        $existing = CashierShift::getActiveShift($assignedUser->id);
        if ($existing) {
            $subject = $assignedUser->id === $auth->id ? 'لديك' : 'للكاشير ('.$assignedUser->name.')';
            $msg = $subject.' بالفعل وردية نشطة مفتوحة حالياً (رقم: '.$existing->shift_number.').';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        // Verify warehouse authorization for the assigned cashier
        if (! $assignedUser->canAccessWarehouse($validated['warehouse_id'])) {
            $msg = 'غير مصرح للكاشير المحدد بالعمل في هذا المستودع / الفرع.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            return redirect()->back()->with('error', $msg);
        }

        $openingBalance = round((float) $validated['opening_balance'], 2);

        $shift = DB::transaction(function () use ($assignedUser, $auth, $validated, $openingBalance) {
            $newShift = CashierShift::create([
                'shift_number' => CashierShift::generateShiftNumber(),
                'user_id' => $assignedUser->id,
                'opened_by' => $auth->id,
                'warehouse_id' => (int) $validated['warehouse_id'],
                'status' => CashierShift::STATUS_OPEN,
                'opened_at' => now(),
                'opening_balance' => $openingBalance,
                'cash_sales_amount' => 0.00,
                'cash_returns_amount' => 0.00,
                'cash_additions' => 0.00,
                'cash_expenses' => 0.00,
                'expected_closing_balance' => $openingBalance,
                'actual_closing_balance' => null,
                'difference' => 0.00,
                'opening_notes' => $validated['opening_notes'] ?? null,
            ]);

            // Record initial opening cash movement
            $movementNote = $assignedUser->id === $auth->id
                ? 'رصيد بداية عهدة الوردية'
                : 'رصيد بداية عهدة الوردية (تم الفتح بواسطة المشرف: '.$auth->name.')';

            $newShift->cashMovements()->create([
                'user_id' => $auth->id,
                'movement_type' => CashierCashMovement::TYPE_OPENING,
                'amount' => $openingBalance,
                'reference_id' => $newShift->id,
                'reference_type' => CashierShift::class,
                'notes' => $movementNote,
                'created_at' => now(),
            ]);

            return $newShift;
        });

        $successMsg = "تم فتح الوردية بنجاح برقم ({$shift->shift_number}) ورصيد بداية ".number_format($openingBalance, 2).' ج.م';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'shift' => $shift,
            ], 201);
        }

        if ($assignedUser->id !== $auth->id) {
            return redirect()->route('shifts.index')->with('status', 'success')->with('message', $successMsg);
        }

        return redirect()->route('sales.create')->with('status', 'success')->with('message', $successMsg);
    }

    /**
     * Show shift details, cash movements and sales.
     */
    public function show(CashierShift $shift): View
    {
        $shift->load(['user', 'warehouse', 'closedBy', 'cashMovements.user', 'salesInvoices.customer']);
        $soldItems = $this->getSoldItemsForShift($shift);

        return view('shifts.show', [
            'title' => "تفاصيل الوردية: {$shift->shift_number}",
            'moduleName' => 'ورديات الكاشير',
            'shift' => $shift,
            'soldItems' => $soldItems,
        ]);
    }

    /**
     * Record cash in or cash out during an active shift.
     */
    public function movement(Request $request, CashierShift $shift): RedirectResponse|JsonResponse
    {
        if ($shift->status !== CashierShift::STATUS_OPEN) {
            $msg = 'لا يمكن تسجيل حركات نقدية على وردية مغلقة.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        $validated = $request->validate([
            'movement_type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $type = $validated['movement_type'];
        $notes = trim($validated['notes']);

        // Check if cash out exceeds expected balance
        if ($type === 'cash_out' && $amount > $shift->expected_closing_balance) {
            $msg = 'المبلغ المراد سحبه أكبر من الرصيد النقدي المتوفر حالياً بالدرج ('.number_format($shift->expected_closing_balance, 2).' ج.م).';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        $shift->recordCashMovement(
            $type === 'cash_in' ? CashierCashMovement::TYPE_CASH_IN : CashierCashMovement::TYPE_CASH_OUT,
            $amount,
            $notes
        );

        $typeLabel = $type === 'cash_in' ? 'إيداع نقدية إضافية' : 'سحب نقدية / مصروفات';
        $msg = "تم تسجيل حركة ({$typeLabel}) بنجاح بمبلغ ".number_format($amount, 2).' ج.م';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'expected_balance' => $shift->expected_closing_balance])
            : redirect()->back()->with('status', 'success')->with('message', $msg);
    }

    /**
     * Close a cashier shift and perform cash drawer reconciliation.
     */
    public function close(Request $request, CashierShift $shift): RedirectResponse|JsonResponse
    {
        if ($shift->status !== CashierShift::STATUS_OPEN) {
            $msg = 'هذه الوردية مغلقة بالفعل مسبقاً.';

            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        $user = auth()->user();
        $closingUserId = $user?->id;

        // Check if current user has permission to close shifts
        if (! Gate::allows('close-shifts')) {
            $supervisorEmail = $request->input('supervisor_email');
            $supervisorPass = $request->input('supervisor_password');

            if ($supervisorEmail && $supervisorPass) {
                $supervisor = User::where('email', $supervisorEmail)->first();
                if ($supervisor && Hash::check($supervisorPass, $supervisor->password) && ($supervisor->isAdmin() || $supervisor->hasPermission('close-shifts'))) {
                    $closingUserId = $supervisor->id;
                } else {
                    $msg = 'بيانات اعتماد المشرف/المدير غير صحيحة أو لا يملك صلاحية إغلاق الوردية.';

                    return $request->wantsJson()
                        ? response()->json(['success' => false, 'message' => $msg], 403)
                        : redirect()->back()->with('error', $msg);
                }
            } else {
                $msg = 'غير مصرح لك بإغلاق وردية الكاشير. إغلاق الوردية وجرد الدرج يتطلب اعتماد المدير أو إدخال بيانات المشرف المصرح له.';

                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 403)
                    : redirect()->back()->with('error', $msg);
            }
        }

        $validated = $request->validate([
            'actual_closing_balance' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $actualBalance = round((float) $validated['actual_closing_balance'], 2);

        $shift->closeShift($actualBalance, $validated['closing_notes'] ?? null, $closingUserId);

        $diffText = '';
        if ($shift->difference > 0) {
            $diffText = ' (يوجد زيادة نقدية بالدرج بقيمة +'.number_format($shift->difference, 2).' ج.م)';
        } elseif ($shift->difference < 0) {
            $diffText = ' (يوجد عجز نقدي بالدرج بقيمة '.number_format($shift->difference, 2).' ج.م)';
        } else {
            $diffText = ' (الدرج مطابق تماماً بدون عجز أو زيادة)';
        }

        $msg = "تم إغلاق وردية الكاشير ({$shift->shift_number}) بنجاح.{$diffText}";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'difference' => $shift->difference,
                'redirect_url' => route('shifts.show', $shift),
            ]);
        }

        return redirect()->route('shifts.show', $shift)->with('status', 'success')->with('message', $msg);
    }

    /**
     * Thermal / A4 Print summary of the shift.
     */
    public function print(Request $request, CashierShift $shift): View
    {
        $shift->load(['user', 'warehouse', 'closedBy', 'cashMovements.user', 'salesInvoices.customer']);
        $company = CompanySetting::getSettings();
        $format = (string) $request->query('format', 'thermal_80mm');
        $soldItems = $this->getSoldItemsForShift($shift);

        return view('shifts.print', [
            'shift' => $shift,
            'company' => $company,
            'format' => $format,
            'soldItems' => $soldItems,
        ]);
    }

    /**
     * Direct print cashier shift receipt via the centralized Python printing engine.
     */
    public function directPrint(Request $request, CashierShift $shift, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:255'],
        ]);

        $printer = $validated['printer'] ?? null;
        $result = $printService->printCashierShift($shift, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get aggregated sold products for this shift across all posted sales invoices.
     */
    protected function getSoldItemsForShift(CashierShift $shift)
    {
        return DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'sales_invoice_items.unit_id')
            ->where('sales_invoices.cashier_shift_id', $shift->id)
            ->where('sales_invoices.status', SalesInvoice::STATUS_POSTED)
            ->select(
                'products.name as product_name',
                'products.sku as product_sku',
                'units.name as unit_name',
                DB::raw('SUM(sales_invoice_items.quantity) as total_qty'),
                DB::raw('SUM(sales_invoice_items.subtotal) as total_amount')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'units.name')
            ->orderByDesc('total_qty')
            ->get();
    }
}
