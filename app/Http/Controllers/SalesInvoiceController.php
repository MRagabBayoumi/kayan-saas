<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\CommissionService;
use App\Services\InventoryService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    /**
     * Display a paginated listing of sales invoices.
     */
    public function index(Request $request): View
    {
        $query = SalesInvoice::with(['customer', 'warehouse', 'creator', 'items.product']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->input('customer_id'));
        }

        if ($request->filled('price_tier')) {
            $query->where('price_tier', $request->input('price_tier'));
        }

        if ($request->filled('payment_status')) {
            $pStatus = $request->input('payment_status');
            if ($pStatus === 'paid') {
                $query->where('remaining_amount', '<=', 0.001);
            } elseif ($pStatus === 'unpaid') {
                $query->where('remaining_amount', '>', 0.001);
            }
        }

        $invoices = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $metrics = [
            'total_count' => SalesInvoice::count(),
            'posted_total' => (float) SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)->sum('total_amount'),
            'draft_count' => SalesInvoice::where('status', SalesInvoice::STATUS_DRAFT)->count(),
            'total_profit' => (float) SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)->sum('total_profit'),
            'total_remaining' => (float) SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)->sum('remaining_amount'),
        ];

        return view('sales.index', [
            'title' => 'فواتير المبيعات والعملاء',
            'moduleName' => 'المبيعات والعملاء',
            'invoices' => $invoices,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'currentSearch' => $request->input('search'),
            'currentStatus' => $request->input('status'),
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentCustomerId' => $request->input('customer_id'),
            'currentPriceTier' => $request->input('price_tier'),
            'currentPaymentStatus' => $request->input('payment_status'),
            'canViewCogs' => auth()->user()?->isAdmin() || Gate::allows('view-cogs'),
        ]);
    }

    /**
     * Show the form for creating a new sales invoice.
     */
    public function create(): View
    {
        $cashCustomer = Customer::firstOrCreate(
            ['name' => 'عميل نقدي'],
            [
                'phone' => '0000000000',
                'customer_type' => 'retail',
                'pricing_tier' => 'retail',
                'is_active' => true,
            ]
        );

        $customers = Customer::active()->orderBy('name')->get();
        $defaultCustomer = $customers->firstWhere('name', 'عميل نقدي') ?? $cashCustomer;

        $user = auth()->user();
        $warehouses = $user?->getAllowedWarehouses() ?? Warehouse::active()->orderBy('name')->get();
        if ($warehouses->isEmpty()) {
            $warehouses = Warehouse::active()->orderBy('name')->get();
        }
        $defaultWarehouse = ($user?->warehouse_id ? $warehouses->firstWhere('id', $user->warehouse_id) : null)
            ?? Warehouse::where('is_default', true)->first()
            ?? $warehouses->first();
        $products = Product::with('stockBalances')->active()->orderBy('name')->get();
        $units = class_exists(Unit::class) ? Unit::active()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();
        $suggestedNumber = SalesInvoice::generateInvoiceNumber();
        $canEditPrice = auth()->user()?->isAdmin() || Gate::allows('edit-product-prices');

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => (string) ($p->barcode ?? ''),
            'retail_price' => (float) $p->retail_price,
            'wholesale_price' => (float) $p->wholesale_price,
            'distributor_price' => (float) ($p->distributor_price ?? $p->wholesale_price),
            'cost_price' => (float) $p->cost_price,
            'unit' => $p->unit ?? 'قطعة',
            'unit_factor' => (float) $p->unit_factor,
            'package_factor' => (float) $p->package_factor,
            'pieces_per_package' => (int) ($p->pieces_per_package ?: (int) round($p->package_factor)),
            'stocks' => $p->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
            'total_stock' => (float) $p->total_stock,
            'formatted_stock' => $p->formatStockBreakdown(),
        ])->values();

        $unitsJson = $units->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'code' => $u->code,
            'factor' => (float) ($u->conversion_factor ?? 1.0),
            'is_default' => (bool) ($u->is_default ?? false),
        ])->values();

        $customersJson = $customers->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'pricing_tier' => $c->pricing_tier ?: 'retail',
            'phone' => $c->phone ?? '',
            'credit_limit' => (float) $c->credit_limit,
            'current_balance' => (float) $c->current_balance,
        ])->values();

        $activeShift = $user ? CashierShift::getActiveShift($user->id) : null;
        if (! $activeShift && $user && $user->isAdmin()) {
            $activeShift = CashierShift::open()
                ->where('warehouse_id', $user->warehouse_id ?? $defaultWarehouse?->id ?? Warehouse::first()?->id)
                ->latest('id')
                ->first();
        }

        return view('sales.create', [
            'title' => 'شاشة نقاط البيع وفاتورة المبيعات (POS)',
            'moduleName' => 'المبيعات والعملاء',
            'customers' => $customers,
            'defaultCustomer' => $defaultCustomer,
            'customersJson' => $customersJson,
            'warehouses' => $warehouses,
            'defaultWarehouse' => $defaultWarehouse,
            'products' => $products,
            'units' => $units,
            'productsJson' => $productsJson,
            'unitsJson' => $unitsJson,
            'suggestedNumber' => $suggestedNumber,
            'canEditPrice' => $canEditPrice,
            'activeShift' => $activeShift,
        ]);
    }

    /**
     * Store a newly created sales invoice in storage.
     */
    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'price_tier' => ['required', 'string', 'in:retail,wholesale,distributor'],
            'payment_type' => ['nullable', 'string', 'in:cash,credit,partial'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sales_rep_id' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'customer_id.required' => 'يرجى اختيار العميل.',
            'customer_id.exists' => 'العميل المختار غير موجود في النظام.',
            'warehouse_id.required' => 'يرجى تحديد مستودع الصرف.',
            'warehouse_id.exists' => 'المستودع المختار غير موجود في النظام.',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب.',
            'price_tier.required' => 'يرجى تحديد فئة السعر.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.product_id.required' => 'يرجى اختيار الصنف.',
            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب.',
        ]);

        $user = auth()->user();
        if ($user && ! $user->canAccessWarehouse($validated['warehouse_id'])) {
            return back()->withInput()->withErrors([
                'warehouse_id' => 'غير مصرح لك بإجراء مبيعات أو الصرف من هذا المستودع.',
            ]);
        }

        $activeShift = $user ? CashierShift::getActiveShift($user->id, (int) $validated['warehouse_id']) : null;
        if (! $activeShift && $user) {
            $activeShift = CashierShift::getActiveShift($user->id);
        }
        if (! $activeShift) {
            $activeShift = CashierShift::open()->where('warehouse_id', (int) $validated['warehouse_id'])->latest('id')->first();
        }
        if (! $activeShift && $user && $user->isAdmin()) {
            $activeShift = CashierShift::open()->latest('id')->first();
        }

        if ($user && $user->isCashier() && ! $activeShift) {
            return back()->withInput()->withErrors([
                'shift' => 'لا يمكن إتمام البيع بدون وجود وردية كاشير مفتوحة. يرجى فتح الوردية وإدخال رصيد بداية العهدة للبدء في البيع.',
            ]);
        }

        $shippingCost = (float) ($validated['shipping_cost'] ?? 0.00);
        $discountType = $validated['discount_type'] ?? 'fixed';
        $discountValue = (float) ($validated['discount_value'] ?? 0.00);

        try {
            $invoice = DB::transaction(function () use ($validated, $shippingCost, $discountType, $discountValue, $request, $inventoryService, $activeShift) {
                $subtotal = 0.00;
                $itemsData = [];
                $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();
                $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($validated['items'] as $itemInput) {
                    $prodId = (int) $itemInput['product_id'];
                    $product = $productsMap->get($prodId);

                    $qty = (float) $itemInput['quantity'];
                    $price = (float) $itemInput['unit_price'];
                    $lineDiscount = (float) ($itemInput['line_discount'] ?? 0.00);
                    $selectedFactor = (float) ($itemInput['conversion_factor'] ?? 1.0000);
                    if ($selectedFactor <= 0) {
                        $selectedFactor = 1.0;
                    }

                    // Total equivalent base pieces to deduct from warehouse FIFO stock
                    $baseQty = round($qty * $selectedFactor, 4);

                    $lineSubtotal = max(0.00, round(($qty * $price) - $lineDiscount, 2));
                    $subtotal += $lineSubtotal;

                    $itemsData[] = [
                        'product_id' => $prodId,
                        'unit_id' => ! empty($itemInput['unit_id']) ? (int) $itemInput['unit_id'] : null,
                        'conversion_factor' => $selectedFactor,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $price,
                        'line_discount' => $lineDiscount,
                        'subtotal' => $lineSubtotal,
                        'total_cogs' => 0.00,
                        'total_profit' => 0.00,
                        'notes' => $itemInput['notes'] ?? null,
                    ];
                }

                $discountAmount = 0.00;
                if ($discountValue > 0) {
                    if ($discountType === 'percentage') {
                        $discountAmount = round(($subtotal * min(100, $discountValue)) / 100, 2);
                    } else {
                        $discountAmount = min($subtotal, $discountValue);
                    }
                }

                $totalAmount = max(0, round($subtotal - $discountAmount + $shippingCost, 2));

                $paymentType = $validated['payment_type'] ?? SalesInvoice::PAYMENT_CASH;
                if ($paymentType === SalesInvoice::PAYMENT_CASH) {
                    $paidAmount = $totalAmount;
                    $remainingAmount = 0.00;
                } elseif ($paymentType === SalesInvoice::PAYMENT_CREDIT) {
                    $paidAmount = 0.00;
                    $remainingAmount = $totalAmount;
                } else {
                    $paidAmount = min($totalAmount, max(0.00, (float) ($validated['paid_amount'] ?? 0.00)));
                    $remainingAmount = max(0.00, round($totalAmount - $paidAmount, 2));
                }
                $dueDate = filled($validated['due_date'] ?? null) ? $validated['due_date'] : null;

                $customer = Customer::findOrFail((int) $validated['customer_id']);
                $salesRepId = $validated['sales_rep_id'] ?? (auth()->user()?->isSalesRep() ? auth()->id() : $customer->sales_rep_id);

                if ($remainingAmount > 0) {
                    if ($customer->name === 'عميل نقدي') {
                        throw new \InvalidArgumentException('لا يمكن إجراء بيع آجل أو تسجيل مديونية على "العميل النقدي". يرجى اختيار عميل مسجل أو إضافة عميل جديد.');
                    }
                    if ((float) $customer->credit_limit > 0 && ((float) $customer->current_balance + $remainingAmount) > (float) $customer->credit_limit) {
                        $exceeded = round(((float) $customer->current_balance + $remainingAmount) - (float) $customer->credit_limit, 2);
                        throw new \InvalidArgumentException("لا يمكن إتمام البيع الآجل: تم تجاوز السقف الائتماني للعميل بمقدار {$exceeded} ج.م (الحد المسموح: {$customer->credit_limit} ج.م، الرصيد بعد العملية: ".((float) $customer->current_balance + $remainingAmount).' ج.م).');
                    }
                }

                $invoice = SalesInvoice::create([
                    'invoice_number' => SalesInvoice::generateInvoiceNumber(),
                    'customer_id' => (int) $validated['customer_id'],
                    'sales_rep_id' => $salesRepId,
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'cashier_shift_id' => $activeShift?->id,
                    'invoice_date' => $validated['invoice_date'],
                    'price_tier' => $validated['price_tier'],
                    'payment_type' => $paymentType,
                    'status' => SalesInvoice::STATUS_DRAFT,
                    'subtotal' => $subtotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'due_date' => $dueDate,
                    'total_cogs' => 0.00,
                    'total_profit' => 0.00,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($itemsData as $row) {
                    $row['sales_invoice_id'] = $invoice->id;
                    SalesInvoiceItem::create($row);
                }

                // Direct posting if requested
                if ($request->input('action') === 'post') {
                    $this->executePosting($invoice, $inventoryService);
                    if ($activeShift && (float) $invoice->paid_amount > 0) {
                        $activeShift->recordCashSale((float) $invoice->paid_amount, $invoice->id);
                    }
                }

                return $invoice;
            });
        } catch (\Throwable $e) {
            Log::error('SalesInvoice store error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->withInput()->withErrors([
                'error' => $e->getMessage(),
            ])->with('error', $e->getMessage());
        }

        $message = $invoice->status === SalesInvoice::STATUS_POSTED
            ? 'تم حفظ فاتورة المبيعات وترحيلها وخصم الدفعات المخزنية بنجاح.'
            : 'تم حفظ مسودة فاتورة المبيعات بنجاح دون أي تأثير على رصيد المخزن.';

        return redirect()->route('sales.show', $invoice)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Unpost a posted sales invoice, reverse stock/shift, and redirect to edit page.
     */
    public function unpostAndEdit(SalesInvoice $sale, InventoryService $inventoryService): RedirectResponse
    {
        $user = auth()->user();
        if ($user && ! $user->canAccessWarehouse($sale->warehouse_id)) {
            return back()->with('error', 'غير مصرح لك بإجراء تعديلات على فواتير هذا المستودع.');
        }

        if ($sale->status === SalesInvoice::STATUS_POSTED) {
            try {
                $inventoryService->unpostSalesInvoice($sale, auth()->id(), 'فك ترحيل للتعديل');
            } catch (\Throwable $e) {
                Log::error('Unpost sales invoice error: '.$e->getMessage());

                return back()->with('error', 'تعذر فك ترحيل الفاتورة للتعديل: '.$e->getMessage());
            }

            return redirect()->route('sales.edit', $sale)
                ->with('status', 'success')
                ->with('message', 'تم فك ترحيل الفاتورة واسترجاع أرصدة المخزن بنجاح. البنود جاهزة الآن للتعديل في الجدول.');
        }

        if ($sale->status === SalesInvoice::STATUS_DRAFT) {
            return redirect()->route('sales.edit', $sale);
        }

        return back()->with('error', 'لا يمكن تعديل هذه الفاتورة لأنها في حالة: '.$sale->status_label);
    }

    /**
     * Show the form for editing the specified sales invoice.
     */
    public function edit(SalesInvoice $sale, InventoryService $inventoryService): View|RedirectResponse
    {
        $user = auth()->user();
        if ($user && ! $user->canAccessWarehouse($sale->warehouse_id)) {
            return redirect()->route('sales.show', $sale)->with('error', 'غير مصرح لك بتعديل فواتير هذا المستودع.');
        }

        // If the invoice is posted, unpost it first so stock is returned
        if ($sale->status === SalesInvoice::STATUS_POSTED) {
            try {
                $inventoryService->unpostSalesInvoice($sale, auth()->id(), 'فك ترحيل تلقائي لبدء التعديل');
            } catch (\Throwable $e) {
                return redirect()->route('sales.show', $sale)->with('error', 'تعذر فك ترحيل الفاتورة للتعديل: '.$e->getMessage());
            }
        }

        if ($sale->status !== SalesInvoice::STATUS_DRAFT) {
            return redirect()->route('sales.show', $sale)->with('error', 'لا يمكن تعديل هذه الفاتورة لأنها في حالة: '.$sale->status_label);
        }

        $sale->loadMissing(['customer', 'warehouse', 'items.product', 'items.unit']);

        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = $user?->getAllowedWarehouses() ?? Warehouse::active()->orderBy('name')->get();
        if ($warehouses->isEmpty()) {
            $warehouses = Warehouse::active()->orderBy('name')->get();
        }

        $products = Product::with('stockBalances')->active()->orderBy('name')->get();
        $units = class_exists(Unit::class) ? Unit::active()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();
        $canEditPrice = auth()->user()?->isAdmin() || Gate::allows('edit-product-prices');

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => (string) ($p->barcode ?? ''),
            'retail_price' => (float) $p->retail_price,
            'wholesale_price' => (float) $p->wholesale_price,
            'distributor_price' => (float) ($p->distributor_price ?? $p->wholesale_price),
            'cost_price' => (float) $p->cost_price,
            'unit' => $p->unit ?? 'قطعة',
            'unit_factor' => (float) $p->unit_factor,
            'package_factor' => (float) $p->package_factor,
            'pieces_per_package' => (int) ($p->pieces_per_package ?: (int) round($p->package_factor)),
            'stocks' => $p->stockBalances->pluck('quantity', 'warehouse_id')->map(fn ($q) => (float) $q)->toArray(),
            'total_stock' => (float) $p->total_stock,
            'formatted_stock' => $p->formatStockBreakdown(),
        ])->values();

        $unitsJson = $units->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'code' => $u->code,
            'factor' => (float) ($u->conversion_factor ?? 1.0),
            'is_default' => (bool) ($u->is_default ?? false),
        ])->values();

        $customersJson = $customers->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'pricing_tier' => $c->pricing_tier ?: 'retail',
            'phone' => $c->phone ?? '',
            'credit_limit' => (float) $c->credit_limit,
            'current_balance' => (float) $c->current_balance,
        ])->values();

        $activeShift = $user ? CashierShift::getActiveShift($user->id, (int) $sale->warehouse_id) : null;
        if (! $activeShift && $user && $user->isAdmin()) {
            $activeShift = CashierShift::open()
                ->where('warehouse_id', $sale->warehouse_id)
                ->latest('id')
                ->first();
        }

        return view('sales.edit', [
            'title' => 'تعديل فاتورة مبيعات: '.$sale->invoice_number,
            'moduleName' => 'المبيعات والعملاء',
            'invoice' => $sale,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'products' => $products,
            'units' => $units,
            'productsJson' => $productsJson,
            'unitsJson' => $unitsJson,
            'customersJson' => $customersJson,
            'canEditPrice' => $canEditPrice,
            'activeShift' => $activeShift,
        ]);
    }

    /**
     * Update the specified sales invoice in storage.
     */
    public function update(Request $request, SalesInvoice $sale, InventoryService $inventoryService): RedirectResponse
    {
        $user = auth()->user();
        if ($user && ! $user->canAccessWarehouse($sale->warehouse_id)) {
            return back()->withInput()->withErrors([
                'warehouse_id' => 'غير مصرح لك بإجراء تعديلات في هذا المستودع.',
            ]);
        }

        if ($sale->status === SalesInvoice::STATUS_POSTED) {
            try {
                $inventoryService->unpostSalesInvoice($sale, auth()->id(), 'فك ترحيل قبل حفظ التعديل');
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['error' => 'تعذر فك ترحيل الفاتورة: '.$e->getMessage()]);
            }
        }

        if ($sale->status !== SalesInvoice::STATUS_DRAFT) {
            return redirect()->route('sales.show', $sale)->with('error', 'لا يمكن تعديل هذه الفاتورة.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'price_tier' => ['required', 'string', 'in:retail,wholesale,distributor'],
            'payment_type' => ['nullable', 'string', 'in:cash,credit,partial'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sales_rep_id' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'customer_id.required' => 'يرجى اختيار العميل.',
            'warehouse_id.required' => 'يرجى تحديد مستودع الصرف.',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب.',
            'price_tier.required' => 'يرجى تحديد فئة السعر.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.product_id.required' => 'يرجى اختيار الصنف.',
            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب.',
        ]);

        $activeShift = $user ? CashierShift::getActiveShift($user->id, (int) $validated['warehouse_id']) : null;
        if (! $activeShift && $user) {
            $activeShift = CashierShift::getActiveShift($user->id);
        }
        if (! $activeShift) {
            $activeShift = CashierShift::open()->where('warehouse_id', (int) $validated['warehouse_id'])->latest('id')->first();
        }
        if (! $activeShift && $user && $user->isAdmin()) {
            $activeShift = CashierShift::open()->latest('id')->first();
        }

        if ($user && $user->isCashier() && ! $activeShift) {
            return back()->withInput()->withErrors([
                'shift' => 'لا يمكن إتمام البيع بدون وجود وردية كاشير مفتوحة.',
            ]);
        }

        $shippingCost = (float) ($validated['shipping_cost'] ?? 0.00);
        $discountType = $validated['discount_type'] ?? 'fixed';
        $discountValue = (float) ($validated['discount_value'] ?? 0.00);

        try {
            DB::transaction(function () use ($sale, $validated, $shippingCost, $discountType, $discountValue, $request, $inventoryService, $activeShift) {
                $subtotal = 0.00;
                $itemsData = [];
                $productIds = collect($validated['items'])->pluck('product_id')->unique()->toArray();
                $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($validated['items'] as $itemInput) {
                    $prodId = (int) $itemInput['product_id'];
                    $product = $productsMap->get($prodId);

                    $qty = (float) $itemInput['quantity'];
                    $price = (float) $itemInput['unit_price'];
                    $lineDiscount = (float) ($itemInput['line_discount'] ?? 0.00);
                    $selectedFactor = (float) ($itemInput['conversion_factor'] ?? 1.0000);
                    if ($selectedFactor <= 0) {
                        $selectedFactor = 1.0;
                    }

                    // Total equivalent base pieces to deduct from warehouse FIFO stock
                    $baseQty = round($qty * $selectedFactor, 4);

                    $lineSubtotal = max(0.00, round(($qty * $price) - $lineDiscount, 2));
                    $subtotal += $lineSubtotal;

                    $itemsData[] = [
                        'sales_invoice_id' => $sale->id,
                        'product_id' => $prodId,
                        'unit_id' => ! empty($itemInput['unit_id']) ? (int) $itemInput['unit_id'] : null,
                        'conversion_factor' => $selectedFactor,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $price,
                        'line_discount' => $lineDiscount,
                        'subtotal' => $lineSubtotal,
                        'total_cogs' => 0.00,
                        'total_profit' => 0.00,
                        'notes' => $itemInput['notes'] ?? null,
                    ];
                }

                $discountAmount = 0.00;
                if ($discountValue > 0) {
                    if ($discountType === 'percentage') {
                        $discountAmount = round(($subtotal * min(100, $discountValue)) / 100, 2);
                    } else {
                        $discountAmount = min($subtotal, $discountValue);
                    }
                }

                $totalAmount = max(0, round($subtotal - $discountAmount + $shippingCost, 2));

                $paymentType = $validated['payment_type'] ?? SalesInvoice::PAYMENT_CASH;
                if ($paymentType === SalesInvoice::PAYMENT_CASH) {
                    $paidAmount = $totalAmount;
                    $remainingAmount = 0.00;
                } elseif ($paymentType === SalesInvoice::PAYMENT_CREDIT) {
                    $paidAmount = 0.00;
                    $remainingAmount = $totalAmount;
                } else {
                    $paidAmount = min($totalAmount, max(0.00, (float) ($validated['paid_amount'] ?? 0.00)));
                    $remainingAmount = max(0.00, round($totalAmount - $paidAmount, 2));
                }
                $dueDate = filled($validated['due_date'] ?? null) ? $validated['due_date'] : null;

                $customer = Customer::findOrFail((int) $validated['customer_id']);
                if ($remainingAmount > 0) {
                    if ($customer->name === 'عميل نقدي') {
                        throw new \InvalidArgumentException('لا يمكن إجراء بيع آجل أو تسجيل مديونية على "العميل النقدي". يرجى اختيار عميل مسجل أو إضافة عميل جديد.');
                    }
                    if ((float) $customer->credit_limit > 0 && ((float) $customer->current_balance + $remainingAmount) > (float) $customer->credit_limit) {
                        $exceeded = round(((float) $customer->current_balance + $remainingAmount) - (float) $customer->credit_limit, 2);
                        throw new \InvalidArgumentException("لا يمكن إتمام البيع الآجل: تم تجاوز السقف الائتماني للعميل بمقدار {$exceeded} ج.م (الحد المسموح: {$customer->credit_limit} ج.م، الرصيد بعد العملية: ".((float) $customer->current_balance + $remainingAmount).' ج.م).');
                    }
                }

                // 1. Update invoice header
                $sale->update([
                    'customer_id' => (int) $validated['customer_id'],
                    'sales_rep_id' => array_key_exists('sales_rep_id', $validated) ? $validated['sales_rep_id'] : $sale->sales_rep_id,
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'cashier_shift_id' => $activeShift?->id ?? $sale->cashier_shift_id,
                    'invoice_date' => $validated['invoice_date'],
                    'price_tier' => $validated['price_tier'],
                    'payment_type' => $paymentType,
                    'subtotal' => $subtotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'due_date' => $dueDate,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 2. Replace items atomically
                $sale->items()->delete();
                foreach ($itemsData as $row) {
                    SalesInvoiceItem::create($row);
                }

                // 3. Post if requested
                if ($request->input('action') === 'post') {
                    $this->executePosting($sale, $inventoryService);
                    if ($activeShift && (float) $sale->paid_amount > 0) {
                        $activeShift->recordCashSale((float) $sale->paid_amount, $sale->id);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('SalesInvoice update error: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->withErrors(['error' => $e->getMessage()])->with('error', $e->getMessage());
        }

        $message = $sale->fresh()->status === SalesInvoice::STATUS_POSTED
            ? 'تم حفظ وتحديث فاتورة المبيعات وترحيلها وخصم الكميات بنجاح.'
            : 'تم حفظ تعديلات فاتورة المبيعات كمسودة بنجاح دون أي تأثير على رصيد المخزن.';

        return redirect()->route('sales.show', $sale)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Display the specified sales invoice.
     */
    public function show(SalesInvoice $sale): View
    {
        $sale->load([
            'customer',
            'warehouse',
            'creator',
            'poster',
            'voider',
            'items.product',
            'items.unit',
            'items.batchAllocations.batch',
            'payments.creator',
        ]);

        $canViewCogs = auth()->user()?->isAdmin() || Gate::allows('view-cogs');

        return view('sales.show', [
            'title' => 'فاتورة مبيعات: '.$sale->invoice_number,
            'moduleName' => 'المبيعات والعملاء',
            'invoice' => $sale,
            'canViewCogs' => $canViewCogs,
        ]);
    }

    /**
     * Post a draft sales invoice atomically using FIFO deduction.
     */
    public function post(SalesInvoice $sale, InventoryService $inventoryService): RedirectResponse
    {
        if ($sale->status !== SalesInvoice::STATUS_DRAFT) {
            return back()->with('error', 'لا يمكن ترحيل هذه الفاتورة لأنها ليست في حالة مسودة.');
        }

        if ((float) $sale->remaining_amount > 0) {
            $customer = Customer::find($sale->customer_id);
            if ($customer && $customer->name === 'عميل نقدي') {
                return back()->with('error', 'لا يمكن ترحيل بيع آجل لـ "عميل نقدي". يرجى تعديل الفاتورة واختيار عميل مسجل.');
            }
            if ($customer && (float) $customer->credit_limit > 0 && ((float) $customer->current_balance + (float) $sale->remaining_amount) > (float) $customer->credit_limit) {
                $exceeded = round(((float) $customer->current_balance + (float) $sale->remaining_amount) - (float) $customer->credit_limit, 2);

                return back()->with('error', "لا يمكن ترحيل الفاتورة: تم تجاوز الحد الائتماني للعميل بمقدار {$exceeded} ج.م (الحد: {$customer->credit_limit} ج.م).");
            }
        }

        try {
            DB::transaction(function () use ($sale, $inventoryService) {
                $this->executePosting($sale, $inventoryService);

                $shift = $sale->cashier_shift_id
                    ? CashierShift::find($sale->cashier_shift_id)
                    : CashierShift::open()->where('warehouse_id', $sale->warehouse_id)->latest('id')->first();

                if ($shift && $shift->status === CashierShift::STATUS_OPEN && (float) $sale->paid_amount > 0) {
                    if (! $sale->cashier_shift_id) {
                        $sale->update(['cashier_shift_id' => $shift->id]);
                    }
                    $shift->recordCashSale((float) $sale->paid_amount, $sale->id);
                }
            });
        } catch (\Throwable $e) {
            Log::error('SalesInvoice post error: '.$e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('status', 'success')
            ->with('message', 'تم ترحيل الفاتورة بنجاح وصرف الكميات وفق سياسة الوارد أولاً يصرف أولاً (FIFO).');
    }

    /**
     * Void / Cancel a sales invoice (posted or draft).
     */
    public function void(Request $request, SalesInvoice $sale, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $validated['void_reason'] ?? 'إلغاء الفاتورة من قبل الإدارة';

        if ($sale->status === SalesInvoice::STATUS_VOIDED) {
            return back()->with('error', 'هذه الفاتورة ملغاة بالفعل.');
        }

        DB::transaction(function () use ($sale, $inventoryService, $reason) {
            if ($sale->status === SalesInvoice::STATUS_POSTED) {
                $inventoryService->voidSalesInvoice($sale, auth()->id(), $reason);

                // Revert customer credit balance if debt was added
                if ((float) $sale->remaining_amount > 0 && $sale->customer_id) {
                    $customer = Customer::lockForUpdate()->find($sale->customer_id);
                    if ($customer) {
                        $customer->decrement('current_balance', (float) $sale->remaining_amount);
                    }
                }

                // Reverse shift cash if cash was received
                if ($sale->cashier_shift_id && (float) $sale->paid_amount > 0) {
                    $shift = CashierShift::find($sale->cashier_shift_id);
                    if ($shift && $shift->status === CashierShift::STATUS_OPEN) {
                        $shift->recordCashReturn((float) $sale->paid_amount, $sale->id);
                    }
                }

                // Reverse general ledger journal entry
                app(AccountingService::class)->voidEntry(
                    JournalEntry::REF_SALES_INVOICE,
                    $sale->id,
                    $reason,
                    auth()->id()
                );
            } else {
                // If it was draft, just mark voided
                $sale->update([
                    'status' => SalesInvoice::STATUS_VOIDED,
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'void_reason' => $reason,
                ]);
            }
        });

        return back()
            ->with('status', 'success')
            ->with('message', 'تم إلغاء فاتورة المبيعات بنجاح واسترجاع رصيد الدفعات المخزنية.');
    }

    /**
     * Delete/wipe the specified sales invoice from storage.
     */
    public function destroy(SalesInvoice $sale, InventoryService $inventoryService): RedirectResponse
    {
        if ($sale->returns()->exists()) {
            return back()->with('status', 'error')
                ->with('message', 'لا يمكن مسح هذه الفاتورة لوجود مرتجعات مبيعات محررة عليها. يرجى مراجعة المرتجعات أولاً.');
        }

        DB::transaction(function () use ($sale, $inventoryService) {
            // 1. If invoice was posted, void it first to restore inventory and balances
            if ($sale->status === SalesInvoice::STATUS_POSTED) {
                $inventoryService->voidSalesInvoice($sale, auth()->id(), 'حذف الفاتورة من قبل الإدارة');

                if ((float) $sale->remaining_amount > 0 && $sale->customer_id) {
                    $customer = Customer::lockForUpdate()->find($sale->customer_id);
                    if ($customer) {
                        $customer->decrement('current_balance', (float) $sale->remaining_amount);
                    }
                }

                if ($sale->cashier_shift_id && (float) $sale->paid_amount > 0) {
                    $shift = CashierShift::find($sale->cashier_shift_id);
                    if ($shift && $shift->status === CashierShift::STATUS_OPEN) {
                        $shift->recordCashReturn((float) $sale->paid_amount, $sale->id);
                    }
                }
            }

            // 2. Delete customer payments linked to this invoice
            CustomerPayment::where('sales_invoice_id', $sale->id)->delete();

            // 3. Delete batch allocations
            foreach ($sale->items as $item) {
                $item->batchAllocations()->delete();
            }

            // 4. Delete items
            $sale->items()->delete();

            // 5. Delete invoice
            $sale->delete();
        });

        return redirect()->route('sales.index')
            ->with('status', 'success')
            ->with('message', 'تم مسح فاتورة المبيعات نهائياً بنجاح.');
    }

    /**
     * Dedicated printable view of the sales invoice.
     */
    public function print(Request $request, SalesInvoice $sale, PrintTemplateService $templateService): View
    {
        $sale->loadMissing(['customer', 'warehouse', 'creator', 'items.product', 'items.unit']);

        $format = (string) $request->query('format', PrintTemplate::FORMAT_A4);
        if (! in_array($format, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $format = PrintTemplate::FORMAT_A4;
        }

        $template = $templateService->getEffectiveTemplate(PrintTemplate::DOC_SALES_INVOICE, $format);
        $companySetting = CompanySetting::getSettings();

        $colorHex = $template->getSetting('general.primary_color', '#1e293b');
        $colorClass = match ($colorHex) {
            '#059669', '#10b981' => 'theme-emerald',
            '#2563eb', '#3b82f6' => 'theme-blue',
            '#7c3aed', '#8b5cf6' => 'theme-purple',
            '#e11d48', '#f43f5e' => 'theme-rose',
            '#d97706', '#f59e0b' => 'theme-amber',
            '#000000' => 'theme-black',
            default => 'theme-navy',
        };

        return view('sales.print', [
            'title' => 'طباعة فاتورة مبيعات: '.$sale->invoice_number,
            'invoice' => $sale,
            'template' => $template,
            'format' => $format,
            'company' => $companySetting,
            'colorClass' => $colorClass,
        ]);
    }

    /**
     * Direct print sales invoice receipt or A4/A5 document via the centralized Python printing engine.
     */
    public function directPrint(Request $request, SalesInvoice $sale, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'in:thermal_80mm,a4,a5'],
        ]);

        $printer = $validated['printer'] ?? null;
        $format = $validated['format'] ?? $request->query('format', 'thermal_80mm');
        $result = $printService->printSalesInvoice($sale, $format, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Internal atomic posting helper for sales invoices.
     */
    private function executePosting(SalesInvoice $invoice, InventoryService $inventoryService): void
    {
        $invoice->loadMissing('items.product');

        $totalInvoiceCogs = 0.00;
        $totalInvoiceProfit = 0.00;

        foreach ($invoice->items as $item) {
            $baseQty = (float) $item->base_quantity;
            $selectedFactor = (float) ($item->conversion_factor > 0 ? $item->conversion_factor : 1.0);
            $unitPriceBase = (float) $item->unit_price / $selectedFactor;

            $result = $inventoryService->deductStockFIFOWithPricing(
                productId: $item->product_id,
                warehouseId: $invoice->warehouse_id,
                baseQuantity: $baseQty,
                priceTier: $invoice->price_tier,
                overrideUnitPriceBase: $unitPriceBase,
                proratedLineDiscount: (float) $item->line_discount,
                notes: 'فاتورة مبيعات رقم: '.$invoice->invoice_number,
                referenceType: 'sales_invoice',
                referenceId: $invoice->id,
                salesInvoiceItemId: $item->id,
                userId: auth()->id(),
                customerId: $invoice->customer_id,
                allowUnpricedBatches: true
            );

            $itemCogs = (float) $result['total_cogs'];
            $itemProfit = round((float) $item->subtotal - $itemCogs, 2);

            $item->update([
                'total_cogs' => $itemCogs,
                'total_profit' => $itemProfit,
            ]);

            $totalInvoiceCogs += $itemCogs;
            $totalInvoiceProfit += $itemProfit;
        }

        $invoice->update([
            'status' => SalesInvoice::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
            'total_cogs' => round($totalInvoiceCogs, 2),
            'total_profit' => round($totalInvoiceProfit - (float) $invoice->discount_amount, 2),
        ]);

        if ((float) $invoice->remaining_amount > 0 && $invoice->customer_id) {
            $customer = Customer::lockForUpdate()->find($invoice->customer_id);
            if ($customer) {
                $customer->increment('current_balance', (float) $invoice->remaining_amount);
            }
        }

        // Calculate and record sales rep commission upon posting
        app(CommissionService::class)->recordInvoiceCommission($invoice);

        // Record general ledger journal entry
        app(AccountingService::class)->recordSalesInvoice($invoice->fresh());
    }
}
