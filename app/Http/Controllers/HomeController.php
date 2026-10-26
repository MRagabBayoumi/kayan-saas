<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Treasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the official BayoSoft ERP Applications Hub (Home Launcher).
     */
    public function home(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSalesRep()) {
            return redirect()->route('portal.index');
        }

        $activeShift = CashierShift::getActiveShift($user->id);

        return view('home', [
            'title' => 'تطبيقات BayoSoft ERP',
            'user' => $user,
            'activeShift' => $activeShift,
        ]);
    }

    /**
     * Display the Administrative KPIs Dashboard.
     * Cashiers are strictly blocked and redirected to Home.
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSalesRep()) {
            return redirect()->route('portal.index');
        }

        if ($user->isCashier() || ! Gate::allows('access-dashboard')) {
            return redirect()->route('home')->with('error', 'لوحة التحكم الإحصائية مخصصة للإدارة فقط.');
        }

        // 1. إجمالي رصيد المخزن (Warehouse Inventory Balance & Valuation)
        $stockQuantity = (float) rescue(fn () => StockBalance::sum('quantity'), 0.0, false);

        // Exact FIFO cost valuation from active inventory batches (matching InventoryService)
        $stockCostValue = (float) rescue(fn () => InventoryBatch::where('is_exhausted', false)
            ->sum(DB::raw('remaining_quantity * cost_price')), 0.0, false);

        if ($stockCostValue <= 0 && $stockQuantity > 0) {
            $stockCostValue = (float) rescue(fn () => Product::with('stockBalances')->get()->sum(function (Product $p) {
                return $p->total_stock * $p->base_cost_price;
            }), 0.0, false);
        }

        // Retail valuation based on base unit selling price
        $stockRetailValue = (float) rescue(function () {
            return InventoryBatch::with('product')
                ->where('is_exhausted', false)
                ->where('remaining_quantity', '>', 0)
                ->get()
                ->sum(function ($batch) {
                    $qty = (float) $batch->remaining_quantity;
                    if ($batch->retail_price !== null && $batch->retail_price > 0) {
                        return $qty * (float) $batch->retail_price;
                    }
                    $product = $batch->product;
                    if ($product) {
                        $factor = (float) ($product->unit_factor > 0 ? $product->unit_factor : 1.0);

                        return $qty * ((float) $product->retail_price / $factor);
                    }

                    return 0.0;
                });
        }, 0.0, false);

        $productsCount = rescue(fn () => Product::count(), 0, false);
        $lowStockCount = rescue(fn () => Product::whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.product_id = products.id) <= products.min_stock_alert')->count(), 0, false);

        // 2. إجمالي فواتير المشتريات (Purchases Invoices)
        $purchasesCount = rescue(fn () => PurchaseInvoice::count(), 0, false);
        $purchasesPostedCount = rescue(fn () => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->count(), 0, false);
        $purchasesPostedTotal = (float) rescue(fn () => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->sum('total_amount'), 0.0, false);
        $purchasesAllTotal = (float) rescue(fn () => PurchaseInvoice::sum('total_amount'), 0.0, false);
        $purchasesPaid = (float) rescue(fn () => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->sum('paid_amount'), 0.0, false);
        $purchasesRemaining = (float) rescue(fn () => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->sum('remaining_amount'), 0.0, false);

        // 3. إجمالي فواتير المبيعات (Sales Invoices)
        $salesPostedQuery = SalesInvoice::where('status', SalesInvoice::STATUS_POSTED);
        $salesCount = rescue(fn () => (clone $salesPostedQuery)->count(), 0, false);
        $salesTotal = (float) rescue(fn () => (clone $salesPostedQuery)->sum('total_amount'), 0.0, false);
        $salesPaid = (float) rescue(fn () => (clone $salesPostedQuery)->sum('paid_amount'), 0.0, false);
        $salesRemaining = (float) rescue(fn () => (clone $salesPostedQuery)->sum('remaining_amount'), 0.0, false);
        $salesToday = (float) rescue(fn () => SalesInvoice::whereDate('invoice_date', today())
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->sum('total_amount'), 0.0, false);

        // 4. الأرباح المتوقعة قبل المصاريف (Expected Gross Profit: Sales Markup Rate on Cost applied to Warehouse Stock)
        $realizedGrossProfit = (float) rescue(fn () => (clone $salesPostedQuery)->sum('total_profit'), 0.0, false);
        $salesCogs = (float) rescue(fn () => (clone $salesPostedQuery)->sum('total_cogs'), 0.0, false);

        // حساب نسبة هامش الربح المحقق = إجمالي أرباح المبيعات ÷ إجمالي تكلفة البضاعة المباعة
        if ($salesCogs > 0) {
            $profitMarkupRate = $realizedGrossProfit / $salesCogs;
        } elseif ($stockCostValue > 0 && $stockRetailValue > $stockCostValue) {
            $profitMarkupRate = ($stockRetailValue - $stockCostValue) / $stockCostValue;
        } else {
            $profitMarkupRate = 0.0;
        }

        $profitMarkupPercent = round($profitMarkupRate * 100, 2);
        // الربح المتوقع على رصيد المخزن = تكلفة المخزن × نسبة هامش الربح
        $expectedProfitOnStock = max(0.0, $stockCostValue * $profitMarkupRate);
        $expectedProfitBeforeExpenses = $expectedProfitOnStock;

        // 5. مؤشرات الخزينة والعملاء والموردين
        $customersCount = rescue(fn () => Customer::count(), 0, false);
        $suppliersCount = rescue(fn () => Supplier::count(), 0, false);
        $openShiftsCount = rescue(fn () => CashierShift::open()->count(), 0, false);
        $totalCustomerDue = (float) rescue(fn () => Customer::sum('current_balance'), 0.0, false);
        $totalSupplierDue = (float) rescue(fn () => Supplier::sum('current_balance'), 0.0, false);
        $totalTreasuriesBalance = (float) rescue(fn () => Treasury::sum('current_balance'), 0.0, false);

        // 6. سجل آخر عمليات للمدير (Manager Recent Operations Log)
        $recentSales = rescue(fn () => SalesInvoice::with(['customer', 'creator'])
            ->latest('id')
            ->take(6)
            ->get(), collect());

        $recentMovements = rescue(fn () => StockMovement::with(['product', 'fromWarehouse', 'toWarehouse', 'user'])
            ->latest('id')
            ->take(6)
            ->get(), collect());

        $recentPurchases = rescue(fn () => PurchaseInvoice::with(['supplier', 'creator'])
            ->latest('id')
            ->take(5)
            ->get(), collect());

        // 7. الأصناف الأكثر مبيعاً
        $topSellingProducts = rescue(fn () => SalesInvoiceItem::select(
            'sales_invoice_items.product_id',
            DB::raw('SUM(sales_invoice_items.quantity) as total_qty'),
            DB::raw('SUM(sales_invoice_items.subtotal) as total_sales'),
            DB::raw('SUM(sales_invoice_items.total_profit) as total_profit')
        )
            ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.status', SalesInvoice::STATUS_POSTED)
            ->groupBy('sales_invoice_items.product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('product')
            ->get(), collect());

        return view('dashboard', [
            'title' => 'لوحة التحكم والمؤشرات العامة',
            'stockMetrics' => [
                'quantity' => $stockQuantity,
                'cost_value' => $stockCostValue,
                'retail_value' => $stockRetailValue,
                'products_count' => $productsCount,
                'low_stock_count' => $lowStockCount,
            ],
            'purchaseMetrics' => [
                'total' => $purchasesPostedTotal,
                'posted_total' => $purchasesPostedTotal,
                'all_total' => $purchasesAllTotal,
                'count' => $purchasesPostedCount,
                'posted_count' => $purchasesPostedCount,
                'cancelled_count' => rescue(fn () => PurchaseInvoice::where('status', PurchaseInvoice::STATUS_CANCELLED)->count(), 0, false),
                'paid' => $purchasesPaid,
                'remaining' => $purchasesRemaining,
            ],
            'salesMetrics' => [
                'total' => $salesTotal,
                'count' => $salesCount,
                'paid' => $salesPaid,
                'remaining' => $salesRemaining,
                'today' => $salesToday,
            ],
            'profitMetrics' => [
                'expected_total' => $expectedProfitBeforeExpenses,
                'expected_stock_profit' => $expectedProfitOnStock,
                'realized_profit' => $realizedGrossProfit,
                'sales_cogs' => $salesCogs,
                'margin_percent' => $profitMarkupPercent,
                'markup_rate' => $profitMarkupRate,
                'stock_cost' => $stockCostValue,
            ],
            'financialSummary' => [
                'treasuries_balance' => $totalTreasuriesBalance,
                'customer_due' => $totalCustomerDue,
                'supplier_due' => $totalSupplierDue,
            ],
            'metrics' => [
                'products_count' => $productsCount,
                'customers_count' => $customersCount,
                'suppliers_count' => $suppliersCount,
                'sales_today' => $salesToday,
                'open_shifts_count' => $openShiftsCount,
            ],
            'recentSales' => $recentSales,
            'recentMovements' => $recentMovements,
            'recentPurchases' => $recentPurchases,
            'topSellingProducts' => $topSellingProducts,
        ]);
    }
}
