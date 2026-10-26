<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\StocktakeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Inventory overview: stock balances, metrics, warehouse filters.
     */
    public function index(Request $request): View
    {
        $metrics = $this->inventoryService->getInventoryMetrics();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedCategoryId = $request->query('category_id');
        $searchTerm = $request->query('search');

        $query = Product::query()
            ->with(['category', 'stockBalances.warehouse'])
            ->withSum('stockBalances', 'quantity');

        if ($searchTerm) {
            $query->search($searchTerm);
        }

        if ($selectedCategoryId) {
            $query->byCategory((int) $selectedCategoryId);
        }

        if ($selectedWarehouseId) {
            $query->whereHas('stockBalances', function ($q) use ($selectedWarehouseId) {
                $q->where('warehouse_id', $selectedWarehouseId);
            });
        }

        $products = $query->paginate(20)->withQueryString();

        return view('inventory.index', [
            'title' => 'نظرة عامة على المخزون',
            'metrics' => $metrics,
            'products' => $products,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'selectedWarehouseId' => $selectedWarehouseId,
            'selectedCategoryId' => $selectedCategoryId,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * Stock movements audit trail.
     */
    public function movements(Request $request): View
    {
        $type = $request->query('type');
        $warehouseId = $request->query('warehouse_id');
        $searchTerm = $request->query('search');

        $query = StockMovement::with(['product.category', 'fromWarehouse', 'toWarehouse', 'batch'])
            ->latest();

        if ($type) {
            $query->where('type', $type);
        }

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                    ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('movement_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($pq) use ($searchTerm) {
                        $pq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('sku', 'like', "%{$searchTerm}%")
                            ->orWhere('barcode', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $movements = $query->paginate(25)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('inventory.movements', [
            'title' => 'سجل حركات المخزون',
            'movements' => $movements,
            'warehouses' => $warehouses,
            'selectedType' => $type,
            'selectedWarehouseId' => $warehouseId,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * Low stock items requiring replenishment.
     */
    public function lowStock(Request $request): View
    {
        $categories = Category::orderBy('name')->get();
        $categoryId = $request->query('category_id');

        $query = Product::with(['category', 'stockBalances.warehouse'])
            ->withSum('stockBalances', 'quantity')
            ->where(function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.product_id = products.id) <= products.min_stock_alert');
            });

        if ($categoryId) {
            $query->byCategory((int) $categoryId);
        }

        $products = $query->paginate(20)->withQueryString();

        return view('inventory.low-stock', [
            'title' => 'نواقص وتنبيهات المخزون',
            'products' => $products,
            'categories' => $categories,
            'selectedCategoryId' => $categoryId,
        ]);
    }

    /**
     * Print Low Stock Shortages report sheet.
     */
    public function printLowStock(Request $request): View
    {
        $categoryId = $request->query('category_id');

        $query = Product::with(['category', 'stockBalances.warehouse'])
            ->withSum('stockBalances', 'quantity')
            ->where(function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.product_id = products.id) <= products.min_stock_alert');
            });

        if ($categoryId) {
            $query->byCategory((int) $categoryId);
        }

        $products = $query->orderBy('name')->get();
        $company = CompanySetting::getSettings();

        return view('inventory.low-stock-print', [
            'products' => $products,
            'company' => $company,
            'selectedCategory' => $categoryId ? Category::find($categoryId) : null,
        ]);
    }

    /**
     * Direct print Low Stock Shortages report via the centralized Python printing engine.
     */
    public function directPrintLowStock(Request $request, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $categoryId = $validated['category_id'] ?? $request->query('category_id');

        $query = Product::with(['category', 'stockBalances.warehouse'])
            ->withSum('stockBalances', 'quantity')
            ->where(function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.product_id = products.id) <= products.min_stock_alert');
            });

        if ($categoryId) {
            $query->byCategory((int) $categoryId);
        }

        $products = $query->orderBy('name')->get();
        $selectedCategory = $categoryId ? Category::find($categoryId) : null;
        $printer = $validated['printer'] ?? null;

        $result = $printService->printLowStockReport(
            $products,
            $selectedCategory?->name,
            $printer
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Store a manual movement (Receipt, Deduction, Transfer, or Adjustment).
     */
    public function storeMovement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'movement_type' => ['required', 'in:in,out,transfer,adjustment'],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required_unless:movement_type,transfer', 'nullable', 'exists:warehouses,id'],
            'from_warehouse_id' => ['required_if:movement_type,transfer', 'nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required_if:movement_type,transfer', 'nullable', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'movement_type.required' => 'نوع الحركة مطلوب.',
            'product_id.required' => 'يرجى اختيار الصنف.',
            'warehouse_id.required_unless' => 'يرجى اختيار المستودع.',
            'from_warehouse_id.required_if' => 'يرجى اختيار مستودع المصدر.',
            'to_warehouse_id.required_if' => 'يرجى اختيار مستودع الوجهة.',
            'to_warehouse_id.different' => 'مستودع الوجهة يجب أن يختلف عن مستودع المصدر.',
            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.min' => 'الكمية يجب أن تكون أكبر من الصفر.',
        ]);

        try {
            $productId = (int) $validated['product_id'];
            $qty = (float) $validated['quantity'];
            $notes = $validated['notes'] ?? null;
            $unitCost = isset($validated['unit_cost']) && $validated['unit_cost'] !== null ? (float) $validated['unit_cost'] : null;

            match ($validated['movement_type']) {
                'in' => $this->inventoryService->addStock(
                    $productId,
                    (int) $validated['warehouse_id'],
                    $qty,
                    $unitCost,
                    $notes,
                    'manual_in'
                ),
                'out' => $this->inventoryService->deductStockFIFO(
                    $productId,
                    (int) $validated['warehouse_id'],
                    $qty,
                    $notes,
                    'manual_out'
                ),
                'transfer' => $this->inventoryService->transferStock(
                    $productId,
                    (int) $validated['from_warehouse_id'],
                    (int) $validated['to_warehouse_id'],
                    $qty,
                    $notes
                ),
                'adjustment' => $this->inventoryService->adjustStock(
                    $productId,
                    (int) $validated['warehouse_id'],
                    $qty,
                    $notes ?? 'تسوية جردية يدوية'
                ),
            };

            return redirect()->back()->with('success', 'تم تسجيل حركة المخزون بنجاح.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Stocktake workspace and discrepancy review.
     */
    public function stocktake(Request $request): View
    {
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedWarehouse = $selectedWarehouseId
            ? Warehouse::find($selectedWarehouseId)
            : (Warehouse::where('is_default', true)->first() ?: $warehouses->first());

        return view('inventory.stocktake', [
            'title' => 'جرد المخزون والتسوية الدورية',
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
        ]);
    }

    /**
     * Download prefilled stocktake template for the selected warehouse.
     */
    public function downloadStocktakeTemplate(Request $request, StocktakeService $stocktakeService): Response|RedirectResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $warehouse = $warehouseId
            ? Warehouse::find($warehouseId)
            : (Warehouse::where('is_default', true)->first() ?: Warehouse::first());

        if (! $warehouse) {
            return redirect()->route('inventory.stocktake')->with('error', 'يرجى تحديد المستودع أولاً.');
        }

        $csvContent = $stocktakeService->generateStocktakeTemplate($warehouse);
        $filename = 'stocktake_'.($warehouse->code ?: 'WH').'_'.date('Y-m-d').'.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Upload and preview physical count against book balances.
     */
    public function previewStocktake(Request $request, StocktakeService $stocktakeService): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'stocktake_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], [
            'warehouse_id.required' => 'يرجى اختيار المستودع.',
            'stocktake_file.required' => 'يرجى اختيار ملف الجرد.',
            'stocktake_file.mimes' => 'صيغة الملف غير مدعومة. الصيغ المسموحة هي: .xlsx, .csv',
            'stocktake_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        try {
            $warehouse = Warehouse::findOrFail($request->input('warehouse_id'));
            $preview = $stocktakeService->parseAndValidateStocktake($request->file('stocktake_file'), $warehouse);

            if (! $preview['success']) {
                return redirect()->route('inventory.stocktake', ['warehouse_id' => $warehouse->id])
                    ->with('error', $preview['message']);
            }

            return redirect()->route('inventory.stocktake', ['warehouse_id' => $warehouse->id])
                ->with('stocktake_preview', $preview);
        } catch (\Throwable $e) {
            return redirect()->route('inventory.stocktake', ['warehouse_id' => $request->input('warehouse_id')])
                ->with('error', 'حدث خطأ أثناء فحص ملف الجرد: '.$e->getMessage());
        }
    }

    /**
     * Commit approved stocktake adjustments atomically.
     */
    public function reconcileStocktake(Request $request, StocktakeService $stocktakeService): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'discrepancies' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'warehouse_id.required' => 'يرجى اختيار المستودع.',
            'discrepancies.required' => 'بيانات الفروقات مطلوبة للاعتماد.',
        ]);

        $discrepancies = json_decode($validated['discrepancies'], true);
        if (! is_array($discrepancies) || empty($discrepancies)) {
            return redirect()->route('inventory.stocktake', ['warehouse_id' => $validated['warehouse_id']])
                ->with('error', 'لا توجد فروقات جردية صالحة للتسوية.');
        }

        try {
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $result = $stocktakeService->commitStocktakeAdjustments(
                $warehouse,
                $discrepancies,
                $validated['reason'] ?? null
            );

            return redirect()->route('inventory.stocktake', ['warehouse_id' => $warehouse->id])
                ->with('success', $result['message']);
        } catch (\Throwable $e) {
            return redirect()->route('inventory.stocktake', ['warehouse_id' => $validated['warehouse_id']])
                ->with('error', 'فشلت عملية تسوية الجرد: '.$e->getMessage());
        }
    }
}
