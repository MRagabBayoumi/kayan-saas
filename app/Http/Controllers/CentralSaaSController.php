<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SaasModule;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Services\TenantManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CentralSaaSController extends Controller
{
    public function __construct(
        protected TenantManagerService $tenantManager
    ) {}

    /**
     * Show SaaS Super Admin dashboard with Odoo 17/18 enterprise styling.
     */
    public function index(): View
    {
        $tenants = Tenant::with('plan')->latest()->get();
        $plans = Plan::where('is_active', true)->get();

        $totalTenants = $tenants->count();
        $activeTenants = $tenants->where('is_active', true)->filter(fn ($t) => ! $t->isExpired())->count();
        $expiredTenants = $tenants->filter(fn ($t) => $t->isExpired())->count();
        $suspendedTenants = $tenants->where('is_active', false)->count();

        // Calculate Monthly Recurring Revenue (MRR)
        $mrr = $tenants->where('is_active', true)->sum(function ($t) {
            if ($t->billing_cycle === 'yearly') {
                return ($t->subscription_price ?: 0) / 12;
            }

            return $t->subscription_price ?: 0;
        });

        return view('saas.dashboard', [
            'tenants' => $tenants,
            'plans' => $plans,
            'availableModules' => SaasModule::ordered()->get(),
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'expiredTenants' => $expiredTenants,
            'suspendedTenants' => $suspendedTenants,
            'mrr' => $mrr,
        ]);
    }

    /**
     * Show comprehensive profile page for a specific tenant (like customer dossier).
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['plan', 'payments']);
        $plans = Plan::where('is_active', true)->get();

        $totalPaid = $tenant->payments->sum('amount');

        // Safely inspect tenant's current active users count
        $userCount = 0;
        try {
            $userCount = DB::table("{$tenant->database_name}.users")->count();
        } catch (\Throwable $e) {
            $userCount = 1;
        }

        // Host calculation for link
        $host = request()->getHost();
        $baseHost = str_replace(['www.', 'saas.'], '', $host);
        if ($baseHost === '127.0.0.1') {
            $baseHost = 'localhost';
        }
        $port = request()->getPort();
        $portSuffix = ($port && ! in_array($port, [80, 443])) ? ':'.$port : '';
        $tenantUrl = request()->getScheme().'://'.$tenant->subdomain.'.'.$baseHost.$portSuffix;

        return view('saas.tenants.show', [
            'tenant' => $tenant,
            'plans' => $plans,
            'availableModules' => SaasModule::ordered()->get(),
            'totalPaid' => $totalPaid,
            'userCount' => $userCount,
            'tenantUrl' => $tenantUrl,
            'baseHost' => $baseHost,
            'portSuffix' => $portSuffix,
        ]);
    }

    /**
     * Record a new subscription payment/receipt for a tenant.
     */
    public function recordPayment(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,vodafone_cash,instapay,visa,other'],
            'billing_cycle' => ['nullable', 'string'],
            'months_covered' => ['nullable', 'integer', 'min:1', 'max:60'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'auto_extend' => ['nullable', 'boolean'],
        ]);

        $paymentNumber = 'PAY-'.date('Ym').'-'.str_pad((string) (TenantPayment::count() + 1), 4, '0', STR_PAD_LEFT);

        $months = (int) ($validated['months_covered'] ?? 1);
        $periodStarts = ($tenant->expires_at && $tenant->expires_at->isFuture())
            ? $tenant->expires_at->copy()
            : Carbon::parse($validated['payment_date']);
        $periodEnds = $periodStarts->copy()->addMonths($months);

        $payment = TenantPayment::create([
            'tenant_id' => $tenant->id,
            'payment_number' => $paymentNumber,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'billing_cycle' => $validated['billing_cycle'] ?? $tenant->billing_cycle,
            'months_covered' => $months,
            'period_starts_at' => $periodStarts,
            'period_ends_at' => $periodEnds,
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => 'مدير المنصة',
        ]);

        if ($request->boolean('auto_extend')) {
            $tenant->update([
                'expires_at' => $periodEnds,
                'is_active' => true,
            ]);
        }

        return back()->with('status', 'success')
            ->with('message', "تم تسجيل دفعة سداد بقيمة ({$payment->amount} ج.م) برقم إيصال ({$payment->payment_number}) بنجاح.");
    }

    /**
     * Show Plans and Pricing management page.
     */
    public function plans(): View
    {
        $plans = Plan::withCount('tenants')->orderBy('price_monthly')->get();

        return view('saas.plans', [
            'plans' => $plans,
            'availableModules' => SaasModule::active()->ordered()->get(),
            'totalPlans' => $plans->count(),
            'activePlans' => $plans->where('is_active', true)->count(),
        ]);
    }

    /**
     * Show SaaS Modules & App Store management page.
     */
    public function modules(): View
    {
        $modules = SaasModule::ordered()->get();

        $metrics = [
            'total' => $modules->count(),
            'active' => $modules->where('is_active', true)->count(),
            'core' => $modules->where('is_core', true)->count(),
            'monthly_sum' => $modules->where('is_active', true)->sum('price_monthly'),
            'yearly_sum' => $modules->where('is_active', true)->sum('price_yearly'),
        ];

        return view('saas.modules.index', [
            'modules' => $modules,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Store a newly created SaaS module in the catalog.
     */
    public function storeModule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:central.saas_modules,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_core' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $features = [];
        if (! empty($validated['features'])) {
            $features = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $validated['features'])))));
        }

        $yearlyPrice = $request->filled('price_yearly')
            ? (float) $validated['price_yearly']
            : ((float) $validated['price_monthly'] * 10);

        SaasModule::create([
            'code' => strtolower($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'features' => $features,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $yearlyPrice,
            'icon' => $validated['icon'] ?: 'box',
            'color' => $validated['color'] ?: '#714B67',
            'is_core' => $request->boolean('is_core'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('saas.modules.index')
            ->with('status', 'success')
            ->with('message', "تم إضافة موديول ({$validated['name']}) الجديد إلى متجر تطبيقات النظام بنجاح.");
    }

    /**
     * Update an existing module's details and pricing.
     */
    public function updateModule(Request $request, SaasModule $module): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_core' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $features = [];
        if (! empty($validated['features'])) {
            $features = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $validated['features'])))));
        }

        $module->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'features' => $features,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'icon' => $validated['icon'] ?: $module->icon,
            'color' => $validated['color'] ?: $module->color,
            'is_core' => $request->boolean('is_core'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $module->sort_order,
        ]);

        return back()
            ->with('status', 'success')
            ->with('message', "تم حفظ تعديلات وأسعار موديول ({$module->name}) بنجاح.");
    }

    /**
     * Toggle module active status.
     */
    public function toggleModuleStatus(SaasModule $module): RedirectResponse
    {
        $module->update(['is_active' => ! $module->is_active]);

        $state = $module->is_active ? 'تفعيل' : 'إيقاف';

        return back()
            ->with('status', 'success')
            ->with('message', "تم {$state} بيع وعرض موديول ({$module->name}) بنجاح.");
    }

    /**
     * Delete an optional module from the catalog.
     */
    public function destroyModule(SaasModule $module): RedirectResponse
    {
        if ($module->is_core) {
            return back()->with('error', 'لا يمكن حذف موديول أساسي من النظام.');
        }

        $name = $module->name;
        $module->delete();

        return redirect()->route('saas.modules.index')
            ->with('status', 'success')
            ->with('message', "تم حذف موديول ({$name}) من الكتالوج بنجاح.");
    }

    /**
     * Store a newly created pricing plan.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:central.plans,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1', 'max:1000'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'is_featured' => ['nullable', 'boolean'],
            'has_accounting' => ['nullable', 'boolean'],
            'has_sales_reps' => ['nullable', 'boolean'],
            'has_installments' => ['nullable', 'boolean'],
            'has_ecommerce' => ['nullable', 'boolean'],
        ]);

        $modules = [
            'accounting' => $request->boolean('has_accounting'),
            'sales_reps' => $request->boolean('has_sales_reps'),
            'installments' => $request->boolean('has_installments'),
            'ecommerce' => $request->boolean('has_ecommerce'),
        ];

        Plan::create([
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'max_users' => $validated['max_users'],
            'badge_text' => $validated['badge_text'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => true,
            'modules' => $modules,
        ]);

        return redirect()->route('saas.plans.index')
            ->with('status', 'success')
            ->with('message', 'تم إضافة باقة الاشتراك الجديدة بنجاح.');
    }

    /**
     * Update an existing pricing plan.
     */
    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1', 'max:1000'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'is_featured' => ['nullable', 'boolean'],
            'has_accounting' => ['nullable', 'boolean'],
            'has_sales_reps' => ['nullable', 'boolean'],
            'has_installments' => ['nullable', 'boolean'],
            'has_ecommerce' => ['nullable', 'boolean'],
        ]);

        $modules = [
            'accounting' => $request->boolean('has_accounting'),
            'sales_reps' => $request->boolean('has_sales_reps'),
            'installments' => $request->boolean('has_installments'),
            'ecommerce' => $request->boolean('has_ecommerce'),
        ];

        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'max_users' => $validated['max_users'],
            'badge_text' => $validated['badge_text'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'modules' => $modules,
        ]);

        return redirect()->route('saas.plans.index')
            ->with('status', 'success')
            ->with('message', "تم تحديث باقة ({$plan->name}) بنجاح.");
    }

    /**
     * Toggle plan active/inactive status.
     */
    public function togglePlanStatus(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);
        $state = $plan->is_active ? 'تفعيل' : 'تعطيل';

        return back()->with('status', 'success')->with('message', "تم {$state} الباقة بنجاح.");
    }

    /**
     * Show Subscriptions Management page.
     */
    public function subscriptions(): View
    {
        $tenants = Tenant::with('plan')->latest()->paginate(20);
        $plans = Plan::where('is_active', true)->get();

        return view('saas.subscriptions', [
            'tenants' => $tenants,
            'plans' => $plans,
            'totalSubscriptions' => Tenant::count(),
            'activeSubscriptions' => Tenant::where('is_active', true)->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'expiringSoon' => Tenant::where('is_active', true)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
        ]);
    }

    /**
     * Renew or extend tenant subscription.
     */
    public function renewSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $months = (int) $request->input('months', 1);
        if ($months < 1) {
            $months = 1;
        }

        $baseDate = ($tenant->expires_at && $tenant->expires_at->isFuture())
            ? $tenant->expires_at
            : now();

        $tenant->update([
            'expires_at' => $baseDate->copy()->addMonths($months),
            'is_active' => true,
        ]);

        return back()->with('status', 'success')
            ->with('message', "تم تجديد اشتراك ({$tenant->name}) لمدة {$months} شهر بنجاح (الصلاحية حتى {$tenant->expires_at->format('Y-m-d')}).");
    }

    /**
     * Change tenant subscription plan (Upgrade / Downgrade).
     */
    public function changePlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:central.plans,id'],
        ]);

        $plan = Plan::findOrFail($request->input('plan_id'));

        $tenant->update([
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'max_users' => $plan->max_users,
            'modules' => $plan->modules,
            'subscription_price' => $tenant->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
        ]);

        return back()->with('status', 'success')
            ->with('message', "تم ترقية/تعديل باقة المشترك ({$tenant->name}) إلى ({$plan->name}) بنجاح.");
    }

    /**
     * Create a new tenant, physical DB, migrate and seed admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'subdomain' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:central.tenants,subdomain'],
            'plan_id' => ['nullable', 'exists:central.plans,id'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,semi_annual,yearly,trial,custom'],
            'custom_expires_at' => ['nullable', 'date'],
            'admin_name' => ['nullable', 'string', 'max:100'],
            'admin_username' => ['required', 'string', 'max:50', 'alpha_dash'],
            'admin_email' => ['required', 'email', 'max:100'],
            'admin_password' => ['required', 'string', 'min:6'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = null;
        if (! empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
        }

        $tenant = $this->tenantManager->createTenant([
            'name' => $validated['name'],
            'subdomain' => strtolower($validated['subdomain']),
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name ?? 'باقة مخصصة',
            'billing_cycle' => $validated['billing_cycle'],
            'expires_at' => $validated['custom_expires_at'] ?? null,
            'max_users' => $plan?->max_users ?? 3,
            'modules' => $plan?->modules ?? [
                'accounting' => true,
                'sales_reps' => true,
                'installments' => true,
                'ecommerce' => true,
            ],
            'admin_name' => $validated['admin_name'] ?? 'مدير النظام',
            'admin_username' => strtolower($validated['admin_username']),
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('saas.index')
            ->with('status', 'success')
            ->with('message', "تم إنشاء المشترك ({$tenant->name}) بنجاح مع اسم المستخدم ({$validated['admin_username']}) وقاعدة بيانات مستقلة ({$tenant->database_name}).");
    }

    /**
     * Update tenant subscription details (billing cycle, plan, max users, expiry date, price).
     */
    public function updateSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'exists:central.plans,id'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,semi_annual,yearly,trial,custom'],
            'max_users' => ['required', 'integer', 'min:1', 'max:1000'],
            'subscription_price' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = null;
        if (! empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
        }

        $tenant->update([
            'plan_id' => $plan?->id ?? $tenant->plan_id,
            'plan_name' => $plan?->name ?? $tenant->plan_name,
            'billing_cycle' => $validated['billing_cycle'],
            'max_users' => $validated['max_users'],
            'subscription_price' => $validated['subscription_price'] ?? $tenant->subscription_price,
            'expires_at' => Carbon::parse($validated['expires_at']),
            'is_active' => $request->boolean('is_active'),
            'notes' => $validated['notes'] ?? $tenant->notes,
        ]);

        return back()->with('status', 'success')
            ->with('message', "تم تحديث دورة الفاتورة وتفاصيل اشتراك ({$tenant->name}) بنجاح (الصلاحية حتى {$tenant->expires_at->format('Y-m-d')}).");
    }

    /**
     * Toggle a specific module for a tenant (e.g. accounting, sales_reps, ecommerce).
     */
    public function toggleModule(Request $request, Tenant $tenant): RedirectResponse
    {
        $moduleCode = $request->input('module');
        $module = SaasModule::where('code', $moduleCode)->first();
        if (! $module) {
            return back()->with('error', 'موديول غير صالح.');
        }

        $modules = $tenant->modules ?? [];
        $modules[$moduleCode] = ! ($modules[$moduleCode] ?? false);

        $tenant->update(['modules' => $modules]);

        $stateText = $modules[$moduleCode] ? 'تفعيل' : 'إغلاق';

        return back()->with('status', 'success')->with('message', "تم {$stateText} موديول ({$module->name}) للمشترك بنجاح.");
    }

    /**
     * Toggle tenant active/suspended status.
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        $msg = $tenant->is_active ? 'تم تفعيل حساب المشترك بنجاح.' : 'تم إيقاف/تجميد حساب المشترك.';

        return back()->with('status', 'success')->with('message', $msg);
    }

    /**
     * Generate a secure one-time SSO token and redirect the SaaS Super Admin directly
     * into the tenant's ERP instance as an authenticated SuperUser.
     */
    public function impersonate(Request $request, Tenant $tenant): RedirectResponse
    {
        $token = Str::random(60);

        cache()->put("sso_impersonate_{$token}", [
            'tenant_id' => $tenant->id,
            'subdomain' => $tenant->subdomain,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()?->name ?? 'مدير SaaS العام',
            'admin_email' => auth()->user()?->email ?? 'admin@kayan-saas.com',
            'created_at' => now()->timestamp,
        ], 120);

        $host = $request->getHost();
        $baseHost = str_replace(['www.', 'saas.'], '', $host);
        if ($baseHost === '127.0.0.1') {
            $baseHost = 'localhost';
        }
        $port = $request->getPort();
        $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';
        $scheme = $request->getScheme();

        $targetUrl = "{$scheme}://{$tenant->subdomain}.{$baseHost}{$portSuffix}/saas/sso-login?token={$token}";

        return redirect()->away($targetUrl);
    }
}
