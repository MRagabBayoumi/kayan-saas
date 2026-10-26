<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Services\StatementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected StatementService $statementService
    ) {}

    public function index(Request $request): View
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('type') && in_array($request->input('type'), ['china_factory', 'local_supplier', 'shipping_agent'], true)) {
            $query->where('supplier_type', $request->input('type'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }

        $suppliers = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_suppliers' => Supplier::count(),
            'china_factories_count' => Supplier::where('supplier_type', 'china_factory')->count(),
            'local_suppliers_count' => Supplier::where('supplier_type', 'local_supplier')->count(),
            'shipping_agents_count' => Supplier::where('supplier_type', 'shipping_agent')->count(),
            'total_payables' => (float) Supplier::where('current_balance', '>', 0)->sum('current_balance'),
        ];

        $countries = Supplier::whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country')
            ->sort()
            ->values();

        return view('suppliers.index', [
            'title' => 'الموردون ومصانع الصين',
            'suppliers' => $suppliers,
            'metrics' => $metrics,
            'countries' => $countries,
            'treasuries' => Treasury::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->input('supplier_type') === 'local_supplier') {
            if (! $request->filled('currency')) {
                $request->merge(['currency' => 'EGP']);
            }
            if (! $request->filled('country')) {
                $request->merge(['country' => 'مصر']);
            }
        } elseif ($request->input('supplier_type') === 'china_factory') {
            if (! $request->filled('currency')) {
                $request->merge(['currency' => 'CNY']);
            }
            if (! $request->filled('country')) {
                $request->merge(['country' => 'China']);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'supplier_type' => ['required', 'in:china_factory,local_supplier,shipping_agent'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'wechat_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'in:CNY,EGP,USD'],
            'opening_balance' => ['nullable', 'numeric'],
            'current_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم المورد أو المصنع مطلوب.',
            'supplier_type.required' => 'نوع المورد مطلوب.',
            'currency.required' => 'عملة التعامل مطلوبة.',
        ]);

        $openingBal = (float) ($validated['opening_balance'] ?? $validated['current_balance'] ?? 0.00);

        $supplier = Supplier::create([
            'name' => trim($validated['name']),
            'supplier_type' => $validated['supplier_type'],
            'contact_person' => filled($validated['contact_person'] ?? null) ? trim($validated['contact_person']) : null,
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'wechat_id' => $validated['supplier_type'] === 'china_factory' && filled($validated['wechat_id'] ?? null) ? trim($validated['wechat_id']) : null,
            'email' => filled($validated['email'] ?? null) ? trim($validated['email']) : null,
            'country' => filled($validated['country'] ?? null) ? trim($validated['country']) : ($validated['supplier_type'] === 'china_factory' ? 'China' : 'مصر'),
            'city' => filled($validated['city'] ?? null) ? trim($validated['city']) : null,
            'address' => filled($validated['address'] ?? null) ? trim($validated['address']) : null,
            'currency' => $validated['currency'],
            'opening_balance' => $openingBal,
            'current_balance' => $openingBal,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المورد بنجاح.',
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                    'country' => $supplier->country,
                    'currency' => $supplier->currency,
                    'supplier_type' => $supplier->supplier_type,
                    'current_balance' => (float) $supplier->current_balance,
                ],
            ]);
        }

        return redirect()->route('suppliers.index')->with('success', 'تم تسجيل المورد بنجاح.');
    }

    public function show(Supplier $supplier): View|JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'supplier' => $supplier,
            ]);
        }

        $supplier->loadMissing(['purchaseInvoices' => function ($q) {
            $q->with('warehouse')->latest('invoice_date')->latest('id');
        }]);

        $statement = $this->statementService->getSupplierStatement($supplier);

        $unpaidInvoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->where('status', PurchaseInvoice::STATUS_POSTED)
            ->where('remaining_amount', '>', 0)
            ->orderBy('invoice_date')
            ->get();

        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();

        $installments = $supplier->installments()->with(['purchaseInvoice', 'creator'])->get();

        return view('suppliers.show', [
            'title' => $supplier->name,
            'supplier' => $supplier,
            'purchaseInvoices' => $supplier->purchaseInvoices,
            'statement' => $statement,
            'unpaidInvoices' => $unpaidInvoices,
            'treasuries' => $treasuries,
            'installments' => $installments,
        ]);
    }

    /**
     * Display dedicated supplier account statement page.
     */
    public function statement(Supplier $supplier, Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $statement = $this->statementService->getSupplierStatement($supplier, $fromDate, $toDate);

        return view('suppliers.statement', [
            'title' => 'كشف حساب المورد: '.$supplier->name,
            'supplier' => $supplier,
            'statement' => $statement,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    /**
     * Dedicated printable supplier statement supporting A4, A5, and Thermal 80mm.
     */
    public function printStatement(Supplier $supplier, Request $request): View
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $format = $request->input('format', 'a4');
        if (! in_array($format, ['a4', 'a5', 'thermal_80mm', 'thermal'], true)) {
            $format = 'a4';
        }

        $statement = $this->statementService->getSupplierStatement($supplier, $fromDate, $toDate);
        $company = CompanySetting::getSettings();

        return view('suppliers.statement-print', [
            'title' => 'كشف حساب مورد: '.$supplier->name,
            'supplier' => $supplier,
            'statement' => $statement,
            'format' => $format,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }

    /**
     * Display Supplier Balance Due report across all suppliers.
     */
    public function balanceDueReport(Request $request): View
    {
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;
        $currency = $request->input('currency');

        $report = $this->statementService->getSupplierBalanceDueReport($supplierId, $currency);
        $allSuppliers = Supplier::active()->orderBy('name')->get();

        return view('suppliers.balance-due-report', [
            'title' => 'تقرير أرصدة ومستحقات الموردين (Balance Due)',
            'report' => $report,
            'allSuppliers' => $allSuppliers,
            'supplierId' => $supplierId,
            'currency' => $currency,
        ]);
    }

    /**
     * Dedicated printable Supplier Balance Due report supporting A4, A5, and Thermal 80mm.
     */
    public function printBalanceDueReport(Request $request): View
    {
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;
        $currency = $request->input('currency');
        $format = $request->input('format', 'a4');
        if (! in_array($format, ['a4', 'a5', 'thermal_80mm', 'thermal'], true)) {
            $format = 'a4';
        }

        $report = $this->statementService->getSupplierBalanceDueReport($supplierId, $currency);
        $company = CompanySetting::getSettings();

        return view('suppliers.balance-due-report-print', [
            'title' => 'تقرير أرصدة الموردين المستحقة',
            'report' => $report,
            'format' => $format,
            'currency' => $currency,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if ($request->input('supplier_type') === 'local_supplier' && ! $request->filled('currency')) {
            $request->merge(['currency' => 'EGP']);
        } elseif ($request->input('supplier_type') === 'china_factory' && ! $request->filled('currency')) {
            $request->merge(['currency' => 'CNY']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'supplier_type' => ['required', 'in:china_factory,local_supplier,shipping_agent'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'wechat_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'in:CNY,EGP,USD'],
            'opening_balance' => ['nullable', 'numeric'],
            'current_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم المورد أو المصنع مطلوب.',
            'supplier_type.required' => 'نوع المورد مطلوب.',
            'currency.required' => 'عملة التعامل مطلوبة.',
        ]);

        $hasTransactions = $supplier->purchaseInvoices()->exists();
        $openingBalance = $supplier->opening_balance !== null ? (float) $supplier->opening_balance : 0.00;
        $currentBalance = (float) $supplier->current_balance;

        if (array_key_exists('opening_balance', $validated) && $validated['opening_balance'] !== null) {
            $newOpening = (float) $validated['opening_balance'];
            if (! $hasTransactions) {
                $openingBalance = $newOpening;
                $currentBalance = array_key_exists('current_balance', $validated) && $validated['current_balance'] !== null
                    ? (float) $validated['current_balance']
                    : $newOpening;
            } else {
                $diff = $newOpening - $openingBalance;
                $openingBalance = $newOpening;
                $currentBalance = round($currentBalance + $diff, 2);
            }
        } elseif (array_key_exists('current_balance', $validated) && $validated['current_balance'] !== null) {
            $newCurrent = (float) $validated['current_balance'];
            if (! $hasTransactions) {
                $openingBalance = $newCurrent;
                $currentBalance = $newCurrent;
            } else {
                $currentBalance = $newCurrent;
            }
        }

        $supplier->update([
            'name' => trim($validated['name']),
            'supplier_type' => $validated['supplier_type'],
            'contact_person' => filled($validated['contact_person'] ?? null) ? trim($validated['contact_person']) : null,
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'wechat_id' => $validated['supplier_type'] === 'china_factory' && filled($validated['wechat_id'] ?? null) ? trim($validated['wechat_id']) : null,
            'email' => filled($validated['email'] ?? null) ? trim($validated['email']) : null,
            'country' => filled($validated['country'] ?? null) ? trim($validated['country']) : $supplier->country,
            'city' => filled($validated['city'] ?? null) ? trim($validated['city']) : null,
            'address' => filled($validated['address'] ?? null) ? trim($validated['address']) : null,
            'currency' => $validated['currency'],
            'opening_balance' => $openingBalance,
            'current_balance' => $currentBalance,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $supplier->is_active,
        ]);

        return redirect()->route('suppliers.index')->with('success', 'تم تحديث بيانات المورد بنجاح.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ((float) $supplier->current_balance != 0.0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'لا يمكن حذف المورد لوجود مستحقات مالية معلقة.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'تم حذف المورد بنجاح.');
    }

    public function lookup(Request $request): JsonResponse
    {
        $term = trim($request->input('q', ''));

        $suppliers = Supplier::query()
            ->active()
            ->search($term)
            ->limit(20)
            ->get(['id', 'name', 'supplier_type', 'contact_person', 'phone', 'wechat_id', 'country', 'currency', 'opening_balance', 'current_balance']);

        return response()->json([
            'success' => true,
            'results' => $suppliers,
        ]);
    }
}
