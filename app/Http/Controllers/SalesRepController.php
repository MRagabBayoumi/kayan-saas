<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Role;
use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesRepController extends Controller
{
    /**
     * Display a listing of sales representatives and aggregate performance KPIs.
     */
    public function index(Request $request): View
    {
        $query = User::salesReps()->with(['warehouse', 'roleRelation']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $salesReps = $query->withCount([
            'assignedCustomers',
            'salesInvoices as posted_invoices_count' => fn ($q) => $q->where('status', SalesInvoice::STATUS_POSTED),
        ])->withSum([
            'salesInvoices as total_sales_volume' => fn ($q) => $q->where('status', SalesInvoice::STATUS_POSTED),
        ], 'total_amount')
            ->withSum('commissions as total_commissions_earned', 'commission_amount')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Overall Global Reps Metrics
        $totalReps = User::salesReps()->count();
        $activeReps = User::salesReps()->where('is_active', true)->count();
        $totalSalesVolume = (float) SalesInvoice::whereNotNull('sales_rep_id')
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->sum('total_amount');
        $totalInvoicesCount = SalesInvoice::whereNotNull('sales_rep_id')
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->count();
        $totalCommissions = (float) SalesCommission::sum('commission_amount');
        $pendingCommissions = (float) SalesCommission::pending()->sum('commission_amount');
        $paidCommissions = (float) SalesCommission::where('status', SalesCommission::STATUS_PAID)->sum('commission_amount');
        $pendingCustomerRequestsCount = Customer::whereNotNull('sales_rep_id')
            ->where('approval_status', Customer::APPROVAL_PENDING)
            ->count();

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('sales-reps.index', [
            'title' => 'إدارة وتحليل أداء مناديب المبيعات',
            'salesReps' => $salesReps,
            'warehouses' => $warehouses,
            'metrics' => [
                'total_reps' => $totalReps,
                'active_reps' => $activeReps,
                'total_sales_volume' => $totalSalesVolume,
                'total_invoices_count' => $totalInvoicesCount,
                'total_commissions' => $totalCommissions,
                'pending_commissions' => $pendingCommissions,
                'paid_commissions' => $paidCommissions,
                'pending_customer_requests' => $pendingCustomerRequestsCount,
            ],
            'currentSearch' => $request->input('search'),
            'currentStatus' => $request->input('status'),
        ]);
    }

    /**
     * Show the form for creating a new sales representative.
     */
    public function create(): View|RedirectResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant && ! $tenant->canAddUser(User::count())) {
            return redirect()->route('sales-reps.index')
                ->with('error', "عفواً، لقد بلغت الحد الأقصى للمستخدمين والمناديب المسموح به في باقتك الحالية ({$tenant->max_users} مستخدمين). يرجى ترقية باقة الاشتراك لإضافة مناديب جدد.");
        }

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('sales-reps.create', [
            'title' => 'إضافة مندوب مبيعات جديد',
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created sales representative in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant && ! $tenant->canAddUser(User::count())) {
            return redirect()->route('sales-reps.index')
                ->with('error', "عفواً، لقد بلغت الحد الأقصى للمستخدمين والمناديب المسموح به في باقتك الحالية ({$tenant->max_users} مستخدمين). يرجى ترقية باقة الاشتراك لإضافة مناديب جدد.");
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'commission_rate' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'in:percentage,fixed'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم المندوب مطلوب.',
            'username.required' => 'اسم المستخدم مطلوب لتسجيل الدخول.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقاً، يرجى اختيار اسم آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمستخدم آخر.',
            'commission_rate.required' => 'نسبة أو قيمة العمولة مطلوبة.',
        ]);

        // Find or create the sales_rep role
        $role = Role::firstOrCreate(
            ['slug' => User::ROLE_SALES_REP],
            [
                'name' => 'مندوب مبيعات',
                'description' => 'مندوب مبيعات ميداني للطلبيات والعملاء',
                'is_system' => true,
            ]
        );

        User::create([
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => filled($validated['email'] ?? null) ? strtolower(trim($validated['email'])) : null,
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'role' => User::ROLE_SALES_REP,
            'commission_rate' => (float) $validated['commission_rate'],
            'commission_type' => $validated['commission_type'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'can_access_all_warehouses' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('sales-reps.index')
            ->with('success', 'تمت إضافة مندوب المبيعات الجديد بنجاح، ويمكنه الآن تسجيل الدخول باسم المستخدم وكلمة المرور.');
    }

    /**
     * Display deep performance analytics and history for a specific sales representative.
     */
    public function show(User $rep, Request $request): View
    {
        $rep->loadMissing(['warehouse', 'roleRelation']);

        // Date range filter
        $range = $request->input('range', 'all');
        $startDate = null;
        $endDate = null;

        if ($range === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($range === 'this_week') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($range === 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($range === 'custom' && $request->filled('from_date') && $request->filled('to_date')) {
            $startDate = Carbon::parse($request->input('from_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('to_date'))->endOfDay();
        }

        // Rep Invoices Query
        $invoicesQuery = SalesInvoice::where('sales_rep_id', $rep->id)
            ->with(['customer', 'warehouse', 'commission']);

        if ($startDate && $endDate) {
            $invoicesQuery->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        $invoices = (clone $invoicesQuery)
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15, ['*'], 'invoices_page')
            ->withQueryString();

        // Rep Commissions Query
        $commissionsQuery = SalesCommission::where('sales_rep_id', $rep->id)
            ->with(['customer', 'salesInvoice']);

        if ($startDate && $endDate) {
            $commissionsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $commissions = (clone $commissionsQuery)
            ->latest()
            ->paginate(15, ['*'], 'commissions_page')
            ->withQueryString();

        // Assigned Customers Query
        $customers = Customer::where('sales_rep_id', $rep->id)
            ->withCount('salesInvoices')
            ->orderBy('name')
            ->paginate(15, ['*'], 'customers_page')
            ->withQueryString();

        // Analytics Calculations for this rep
        $postedInvoicesQuery = SalesInvoice::where('sales_rep_id', $rep->id)
            ->where('status', SalesInvoice::STATUS_POSTED);

        if ($startDate && $endDate) {
            $postedInvoicesQuery->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        $totalSales = (float) $postedInvoicesQuery->sum('total_amount');
        $invoicesCount = (int) $postedInvoicesQuery->count();
        $avgOrderValue = $invoicesCount > 0 ? ($totalSales / $invoicesCount) : 0.0;
        $totalPaidByCustomers = (float) $postedInvoicesQuery->sum('paid_amount');
        $totalRemainingOnInvoices = (float) $postedInvoicesQuery->sum('remaining_amount');

        $totalCommissions = (float) (clone $commissionsQuery)->sum('commission_amount');
        $paidCommissions = (float) (clone $commissionsQuery)->where('status', SalesCommission::STATUS_PAID)->sum('commission_amount');
        $pendingCommissions = (float) (clone $commissionsQuery)->where('status', SalesCommission::STATUS_PENDING)->sum('commission_amount');

        $assignedCustomersCount = Customer::where('sales_rep_id', $rep->id)->count();
        $assignedCustomersTotalDebt = (float) Customer::where('sales_rep_id', $rep->id)->where('current_balance', '>', 0)->sum('current_balance');

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('sales-reps.show', [
            'title' => 'تحليل أداء المندوب: '.$rep->name,
            'rep' => $rep,
            'invoices' => $invoices,
            'commissions' => $commissions,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'currentRange' => $range,
            'fromDate' => $request->input('from_date'),
            'toDate' => $request->input('to_date'),
            'activeTab' => $request->input('tab', 'invoices'),
            'analytics' => [
                'total_sales' => $totalSales,
                'invoices_count' => $invoicesCount,
                'avg_order_value' => $avgOrderValue,
                'total_paid' => $totalPaidByCustomers,
                'total_remaining' => $totalRemainingOnInvoices,
                'total_commissions' => $totalCommissions,
                'paid_commissions' => $paidCommissions,
                'pending_commissions' => $pendingCommissions,
                'customers_count' => $assignedCustomersCount,
                'customers_debt' => $assignedCustomersTotalDebt,
            ],
        ]);
    }

    /**
     * Update the sales representative information and commission rates.
     */
    public function update(Request $request, User $rep): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($rep->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($rep->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'commission_rate' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'in:percentage,fixed'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم المندوب مطلوب.',
            'username.required' => 'اسم المستخدم مطلوب لتسجيل الدخول.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقاً، يرجى اختيار اسم آخر.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمستخدم آخر.',
            'commission_rate.required' => 'نسبة أو قيمة العمولة مطلوبة.',
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => filled($validated['email'] ?? null) ? strtolower(trim($validated['email'])) : null,
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'commission_rate' => (float) $validated['commission_rate'],
            'commission_type' => $validated['commission_type'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ];

        if (filled($validated['password'] ?? null)) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $rep->update($updateData);

        return redirect()->back()
            ->with('success', 'تم تحديث بيانات المندوب ونسبة عمولته بنجاح.');
    }

    /**
     * Mark a commission as paid to the sales representative.
     */
    public function payCommission(Request $request, SalesCommission $commission): RedirectResponse
    {
        if ($commission->status === SalesCommission::STATUS_PAID) {
            return redirect()->back()->with('error', 'هذه العمولة مدفوعة ومصروفة مسبقاً.');
        }

        $commission->update([
            'status' => SalesCommission::STATUS_PAID,
            'paid_at' => now(),
            'notes' => filled($request->input('notes')) ? trim($request->input('notes')) : $commission->notes,
        ]);

        return redirect()->back()
            ->with('success', 'تم تسجيل صرف ودفع العمولة للمندوب بقيمة '.number_format($commission->commission_amount, 2).' ج.م بنجاح.');
    }
}
