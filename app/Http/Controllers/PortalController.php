<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesRepAttendance;
use App\Models\SalesRepVisit;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\CommissionService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortalController extends Controller
{
    /**
     * Sales Rep Mobile Portal Dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth();

        $invoicesQuery = SalesInvoice::where(function ($q) use ($user) {
            $q->where('sales_rep_id', $user->id)
                ->orWhere('created_by', $user->id);
        });

        $todaySalesCount = (clone $invoicesQuery)->whereDate('invoice_date', $today)->count();
        $todaySalesTotal = (float) (clone $invoicesQuery)->whereDate('invoice_date', $today)->sum('total_amount');

        $monthCommissions = (float) SalesCommission::forSalesRep($user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->where('status', '!=', SalesCommission::STATUS_CANCELED)
            ->sum('commission_amount');

        $pendingCommissions = (float) SalesCommission::forSalesRep($user->id)
            ->where('status', SalesCommission::STATUS_PENDING)
            ->sum('commission_amount');

        $myCustomersCount = Customer::where('sales_rep_id', $user->id)->count();

        $pendingCustomerRequestsCount = Customer::where('sales_rep_id', $user->id)
            ->where('approval_status', Customer::APPROVAL_PENDING)
            ->count();

        $todayAttendance = SalesRepAttendance::where('sales_rep_id', $user->id)
            ->whereDate('date', $today)
            ->latest('id')
            ->first();

        $todayVisitsCount = SalesRepVisit::where('sales_rep_id', $user->id)
            ->whereDate('visit_date', $today)
            ->count();

        $recentOrders = (clone $invoicesQuery)
            ->with(['customer', 'commission'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentCustomers = Customer::where('sales_rep_id', $user->id)
            ->latest('id')
            ->take(5)
            ->get();

        return view('portal.index', [
            'user' => $user,
            'todaySalesCount' => $todaySalesCount,
            'todaySalesTotal' => $todaySalesTotal,
            'monthCommissions' => $monthCommissions,
            'pendingCommissions' => $pendingCommissions,
            'myCustomersCount' => $myCustomersCount,
            'pendingCustomerRequestsCount' => $pendingCustomerRequestsCount,
            'todayAttendance' => $todayAttendance,
            'todayVisitsCount' => $todayVisitsCount,
            'recentOrders' => $recentOrders,
            'recentCustomers' => $recentCustomers,
        ]);
    }

    /**
     * Mobile Showroom & Warehouse Products Catalog.
     */
    public function catalog(Request $request): View
    {
        $query = Product::active()->with(['category', 'stockBalances.warehouse']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->boolean('in_stock_only')) {
            $query->whereHas('stockBalances', function ($q) {
                $q->where('quantity', '>', 0);
            });
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();

        return view('portal.catalog', [
            'products' => $products,
            'categories' => $categories,
            'currentSearch' => $request->input('search'),
            'currentCategory' => $request->input('category_id'),
            'inStockOnly' => $request->boolean('in_stock_only'),
        ]);
    }

    /**
     * Mobile Customers Directory for Rep.
     */
    public function customers(Request $request): View
    {
        $user = auth()->user();
        $query = Customer::query();

        // Always strictly scope to customers belonging to this sales rep
        if (! $user->isAdmin()) {
            $query->where('sales_rep_id', $user->id);
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        $customers = $query->latest('id')->paginate(20)->withQueryString();

        $totalMyCustomers = Customer::where('sales_rep_id', $user->id)->count();
        $pendingCount = Customer::where('sales_rep_id', $user->id)->where('approval_status', Customer::APPROVAL_PENDING)->count();
        $approvedCount = Customer::where('sales_rep_id', $user->id)->where('approval_status', Customer::APPROVAL_APPROVED)->count();

        return view('portal.customers.index', [
            'customers' => $customers,
            'search' => $request->input('search'),
            'currentApprovalStatus' => $request->input('approval_status'),
            'totalMyCustomers' => $totalMyCustomers,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
        ]);
    }

    /**
     * Show mobile form to register a new customer in the field.
     */
    public function createCustomer(): View
    {
        $todayAttendance = auth()->user()->todayAttendance();

        return view('portal.customers.create', [
            'todayAttendance' => $todayAttendance,
        ]);
    }

    /**
     * Store new customer registered from field with GPS location and store images.
     */
    public function storeCustomer(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'phone_alt' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'store_images' => ['nullable', 'array', 'max:5'],
            'store_images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ], [
            'name.required' => 'اسم العميل مطلوب.',
            'phone.required' => 'رقم هاتف العميل مطلوب.',
            'store_images.*.image' => 'يجب أن يكون الملف المرفوع صورة صحيحة.',
            'store_images.*.max' => 'أقصى حجم مسموح به للصورة الواحدة هو 10 ميجابايت.',
        ]);

        $uploadedImages = [];
        if ($request->hasFile('store_images')) {
            foreach ($request->file('store_images') as $file) {
                if ($file->isValid()) {
                    $uploadedImages[] = $file->store('customer_stores', 'public');
                }
            }
        }

        $customer = Customer::create([
            'name' => trim($validated['name']),
            'company_name' => filled($validated['company_name'] ?? null) ? trim($validated['company_name']) : null,
            'phone' => trim($validated['phone']),
            'phone_alt' => filled($validated['phone_alt'] ?? null) ? trim($validated['phone_alt']) : null,
            'city' => filled($validated['city'] ?? null) ? trim($validated['city']) : null,
            'address' => filled($validated['address'] ?? null) ? trim($validated['address']) : null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'sales_rep_id' => $user->id,
            'customer_type' => 'wholesale',
            'pricing_tier' => 'wholesale',
            'credit_limit' => 0.00,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'approval_status' => Customer::APPROVAL_PENDING,
            'store_images' => $uploadedImages,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'is_active' => true,
        ]);

        return redirect()->route('portal.customers.index')
            ->with('status', 'success')
            ->with('message', "تم إرسال طلب تسجيل العميل «{$customer->name}» بنجاح وهو الآن قيد مراجعة واعتماد الإدارة.");
    }

    /**
     * Orders placed by sales representative.
     */
    public function orders(Request $request): View
    {
        $user = auth()->user();

        $query = SalesInvoice::with(['customer', 'warehouse', 'commission'])
            ->where(function ($q) use ($user) {
                $q->where('sales_rep_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();

        return view('portal.orders.index', [
            'orders' => $orders,
            'currentStatus' => $request->input('status'),
        ]);
    }

    /**
     * Mobile Order / Invoice Creation Screen.
     */
    public function createOrder(Request $request): View
    {
        $user = auth()->user();
        $isAdmin = $user && ($user->isAdmin() || $user->can('manage-inventory'));

        $customers = Customer::active()
            ->approved()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('sales_rep_id', $user->id))
            ->orderBy('name')
            ->get();

        $warehouses = $user->getAllowedWarehouses();
        $selectedCustomerId = $request->input('customer_id');
        $selectedProductId = $request->input('product_id');

        $products = Product::active()
            ->with(['category', 'stockBalances'])
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($isAdmin) {
                $totalStock = (float) $p->stockBalances->sum('quantity');
                $threshold = ($p->min_stock_alert && (float) $p->min_stock_alert > 0)
                    ? (float) $p->min_stock_alert
                    : 1.0;
                $isAvailable = $totalStock >= $threshold;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'image_url' => $p->image_url,
                    'wholesale_price' => (float) $p->wholesale_price,
                    'retail_price' => $isAdmin ? (float) $p->retail_price : (float) $p->wholesale_price,
                    'distributor_price' => $isAdmin ? (float) $p->distributor_price : (float) $p->wholesale_price,
                    'unit' => $p->unit ?? 'قطعة',
                    'pieces_per_package' => (int) ($p->pieces_per_package ?? 1),
                    'is_available' => $isAvailable,
                    'available_stock' => $isAdmin ? $totalStock : ($isAvailable ? 'متوفر للطلب' : 'غير متوفر'),
                    'stock_display' => $isAdmin
                        ? ($isAvailable ? "متوفر ({$totalStock} {$p->unit})" : "غير متوفر ({$totalStock} / حد أدنى {$threshold})")
                        : ($isAvailable ? 'متوفر للطلب' : 'غير متوفر حالياً'),
                    'min_stock_alert' => (float) $p->min_stock_alert,
                ];
            });

        $units = Unit::active()->orderBy('name')->get();

        return view('portal.orders.create', [
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProductId,
            'warehouses' => $warehouses,
            'productsJson' => $products->toJson(JSON_UNESCAPED_UNICODE),
            'units' => $units,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Store order/invoice submitted from mobile portal.
     */
    public function storeOrder(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'price_tier' => ['required', 'string', 'in:retail,wholesale,distributor'],
            'payment_type' => ['nullable', 'string', 'in:cash,credit,partial'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'action' => ['required', 'in:draft,post'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'customer_id.required' => 'يرجى اختيار العميل.',
            'warehouse_id.required' => 'يرجى اختيار المستودع.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل.',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        if (! $customer->isApproved()) {
            return redirect()->back()
                ->withInput()
                ->with('status', 'danger')
                ->with('message', 'لا يمكن تحرير طلب بيع لعميل ما زال قيد المراجعة أو مرفوض. يجب اعتماد العميل أولاً من إدارة النظام.');
        }

        try {
            $invoice = DB::transaction(function () use ($validated, $user, $inventoryService) {
                $subtotal = 0.00;
                $itemsData = [];

                foreach ($validated['items'] as $item) {
                    $qty = (float) $item['quantity'];
                    $price = (float) $item['unit_price'];
                    $discount = (float) ($item['line_discount'] ?? 0.00);
                    $lineTotal = max(0.00, round(($qty * $price) - $discount, 2));
                    $subtotal += $lineTotal;

                    $itemsData[] = [
                        'product_id' => (int) $item['product_id'],
                        'unit_id' => ! empty($item['unit_id']) ? (int) $item['unit_id'] : null,
                        'conversion_factor' => (float) ($item['conversion_factor'] ?? 1.0000),
                        'quantity' => $qty,
                        'base_quantity' => round($qty * (float) ($item['conversion_factor'] ?? 1.0000), 4),
                        'unit_price' => $price,
                        'line_discount' => $discount,
                        'subtotal' => $lineTotal,
                        'total_cogs' => 0.00,
                        'total_profit' => 0.00,
                    ];
                }

                $totalAmount = round($subtotal, 2);
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

                $customer = Customer::findOrFail((int) $validated['customer_id']);

                if (! $user->isAdmin() && (int) $customer->sales_rep_id !== (int) $user->id) {
                    abort(403, 'غير مصرح لك بإنشاء فواتير لعميل غير مسجل ومربوط باسمك.');
                }

                $invoice = SalesInvoice::create([
                    'invoice_number' => SalesInvoice::generateInvoiceNumber(),
                    'customer_id' => $customer->id,
                    'sales_rep_id' => $user->id,
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'invoice_date' => $validated['invoice_date'],
                    'price_tier' => $validated['price_tier'],
                    'payment_type' => $paymentType,
                    'status' => SalesInvoice::STATUS_DRAFT,
                    'subtotal' => $subtotal,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'total_cogs' => 0.00,
                    'total_profit' => 0.00,
                    'notes' => $validated['notes'] ?? 'طلب عبر تطبيق المندوب',
                    'created_by' => $user->id,
                ]);

                foreach ($itemsData as $row) {
                    $row['sales_invoice_id'] = $invoice->id;
                    SalesInvoiceItem::create($row);
                }

                // If rep has post permission or chooses to post directly
                if ($validated['action'] === 'post') {
                    $this->postInvoiceInternal($invoice, $inventoryService);
                }

                return $invoice;
            });

            $msg = $invoice->status === SalesInvoice::STATUS_POSTED
                ? 'تم إنشاء الفاتورة وترحيلها بنجاح واحتساب عمولتك.'
                : 'تم إرسال الطلب كمسودة معلقة بنجاح للاعتماد من إدارة المعرض.';

            return redirect()->route('portal.orders.show', $invoice)
                ->with('status', 'success')
                ->with('message', $msg);
        } catch (\Throwable $e) {
            Log::error('Portal store order error: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'تعذر إرسال الطلب: '.$e->getMessage());
        }
    }

    /**
     * Show Order details on mobile.
     */
    public function showOrder(SalesInvoice $invoice): View
    {
        $user = auth()->user();

        if (! $user->isAdmin() && (int) $invoice->sales_rep_id !== (int) $user->id && (int) $invoice->created_by !== (int) $user->id) {
            abort(403, 'غير مصرح لك باستعراض هذا الطلب.');
        }

        $invoice->loadMissing(['customer', 'warehouse', 'items.product', 'items.unit', 'commission']);

        return view('portal.orders.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * My Commissions Breakdown & Statement Screen.
     */
    public function commissions(): View
    {
        $user = auth()->user();

        $commissions = SalesCommission::forSalesRep($user->id)
            ->with(['salesInvoice', 'customer'])
            ->latest('id')
            ->paginate(20);

        $totalEarned = (float) SalesCommission::forSalesRep($user->id)
            ->where('status', '!=', SalesCommission::STATUS_CANCELED)
            ->sum('commission_amount');

        $pendingAmount = (float) SalesCommission::forSalesRep($user->id)
            ->where('status', SalesCommission::STATUS_PENDING)
            ->sum('commission_amount');

        $paidAmount = (float) SalesCommission::forSalesRep($user->id)
            ->where('status', SalesCommission::STATUS_PAID)
            ->sum('commission_amount');

        return view('portal.commissions', [
            'user' => $user,
            'commissions' => $commissions,
            'totalEarned' => $totalEarned,
            'pendingAmount' => $pendingAmount,
            'paidAmount' => $paidAmount,
        ]);
    }

    /**
     * Internal helper to post invoice and calculate commission.
     */
    private function postInvoiceInternal(SalesInvoice $invoice, InventoryService $inventoryService): void
    {
        $invoice->loadMissing('items.product');
        $totalCogs = 0.00;
        $totalProfit = 0.00;

        foreach ($invoice->items as $item) {
            $baseQty = (float) $item->base_quantity;
            $unitPriceBase = (float) $item->unit_price;

            $result = $inventoryService->deductStockFIFOWithPricing(
                productId: $item->product_id,
                warehouseId: $invoice->warehouse_id,
                baseQuantity: $baseQty,
                priceTier: $invoice->price_tier,
                overrideUnitPriceBase: $unitPriceBase,
                proratedLineDiscount: (float) $item->line_discount,
                notes: 'فاتورة مبيعات تطبيق المندوب: '.$invoice->invoice_number,
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

            $totalCogs += $itemCogs;
            $totalProfit += $itemProfit;
        }

        $invoice->update([
            'status' => SalesInvoice::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
            'total_cogs' => round($totalCogs, 2),
            'total_profit' => round($totalProfit, 2),
        ]);

        if ((float) $invoice->remaining_amount > 0 && $invoice->customer_id) {
            $customer = Customer::lockForUpdate()->find($invoice->customer_id);
            if ($customer) {
                $customer->increment('current_balance', (float) $invoice->remaining_amount);
            }
        }

        app(CommissionService::class)->recordInvoiceCommission($invoice);
    }
}
