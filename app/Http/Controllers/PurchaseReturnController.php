<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\InventoryBatch;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\Printing\PythonPrintEngineService;
use App\Services\PrintTemplateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request): View
    {
        $query = PurchaseReturn::with(['supplier', 'warehouse', 'purchaseInvoice', 'creator', 'items.product']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('return_number', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('purchaseInvoice', fn ($iq) => $iq->where('invoice_number', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        $returns = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $metrics = [
            'total_count' => PurchaseReturn::count(),
            'posted_total' => (float) PurchaseReturn::where('status', PurchaseReturn::STATUS_POSTED)->sum('total_amount'),
            'draft_count' => PurchaseReturn::where('status', PurchaseReturn::STATUS_DRAFT)->count(),
        ];

        return view('purchases.returns.index', [
            'title' => 'مرتجعات المشتريات والتوريد',
            'moduleName' => 'المشتريات والموردين',
            'returns' => $returns,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'currentSearch' => $request->input('search'),
            'currentStatus' => $request->input('status'),
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentSupplierId' => $request->input('supplier_id'),
        ]);
    }

    /**
     * Show the form for creating a new purchase return.
     */
    public function create(Request $request): View
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $defaultWarehouse = Warehouse::where('is_default', true)->first() ?? $warehouses->first();
        $products = Product::active()->with('stockBalances')->orderBy('name')->get();
        $units = class_exists(Unit::class) ? Unit::active()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();
        $suggestedNumber = PurchaseReturn::generateReturnNumber();

        $selectedInvoice = null;
        if ($request->filled('purchase_invoice_id') || $request->filled('purchase_id')) {
            $invId = (int) ($request->input('purchase_invoice_id') ?: $request->input('purchase_id'));
            $selectedInvoice = PurchaseInvoice::with(['supplier', 'warehouse', 'items.product', 'items.unit', 'batches'])
                ->find($invId);
        }

        $productsJson = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => (string) ($p->barcode ?? ''),
            'cost_price' => (float) $p->cost_price,
            'unit' => $p->unit ?? 'قطعة',
            'unit_factor' => (float) ($p->unit_factor ?? 1.0),
            'package_factor' => (float) ($p->package_factor ?? 12.0),
            'pieces_per_package' => (int) ($p->pieces_per_package ?: (int) round($p->package_factor ?? 12.0)),
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

        return view('purchases.returns.create', [
            'title' => 'تسجيل مرتجع مشتريات جديد',
            'moduleName' => 'المشتريات والموردين',
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'defaultWarehouse' => $defaultWarehouse,
            'products' => $products,
            'units' => $units,
            'treasuries' => $treasuries,
            'productsJson' => $productsJson,
            'unitsJson' => $unitsJson,
            'suggestedNumber' => $suggestedNumber,
            'selectedInvoice' => $selectedInvoice,
        ]);
    }

    /**
     * Store a newly created purchase return.
     */
    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
            'return_date' => ['required', 'date'],
            'settlement_type' => ['required', 'string', 'in:deduct_balance,cash_refund'],
            'treasury_id' => ['nullable', 'required_if:settlement_type,cash_refund', 'exists:treasuries,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'items.*.purchase_invoice_item_id' => ['nullable', 'exists:purchase_invoice_items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'supplier_id.required' => 'يرجى اختيار المورد.',
            'supplier_id.exists' => 'المورد المختار غير موجود في النظام.',
            'warehouse_id.required' => 'يرجى تحديد مستودع الصرف / الإرجاع.',
            'warehouse_id.exists' => 'المستودع المختار غير موجود في النظام.',
            'return_date.required' => 'تاريخ المرتجع مطلوب.',
            'settlement_type.required' => 'يرجى تحديد طريقة التسوية المالية.',
            'treasury_id.required_if' => 'يرجى اختيار الخزينة المستلمة للمبلغ المسترد.',
            'items.required' => 'يجب إضافة صنف واحد على الأقل في المرتجع.',
            'items.min' => 'يجب إضافة صنف واحد على الأقل في المرتجع.',
            'items.*.product_id.required' => 'يرجى اختيار الصنف.',
            'items.*.quantity.required' => 'الكمية مطلوبة.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من الصفر.',
            'items.*.unit_cost.required' => 'سعر التكلفة مطلوب.',
        ]);

        try {
            $return = DB::transaction(function () use ($validated, $request, $inventoryService) {
                $totalAmount = 0.00;
                $itemsData = [];

                foreach ($validated['items'] as $itemInput) {
                    $qty = (float) $itemInput['quantity'];
                    $cost = (float) $itemInput['unit_cost'];
                    $lineTotal = round($qty * $cost, 2);
                    $totalAmount += $lineTotal;

                    $prodId = (int) $itemInput['product_id'];
                    $product = Product::find($prodId);
                    $unitId = ! empty($itemInput['unit_id']) ? (int) $itemInput['unit_id'] : null;
                    $factor = 1.0000;
                    if (! empty($itemInput['conversion_factor']) && (float) $itemInput['conversion_factor'] > 0) {
                        $factor = (float) $itemInput['conversion_factor'];
                    } elseif ($unitId) {
                        $unit = Unit::find($unitId);
                        if ($unit && (float) $unit->conversion_factor > 0) {
                            $factor = (float) $unit->conversion_factor;
                        }
                    } elseif ($product && (float) $product->unit_factor > 1) {
                        $factor = (float) $product->unit_factor;
                    }

                    $baseQty = round($qty * $factor, 4);
                    $unitCostBase = $baseQty > 0 ? round($lineTotal / $baseQty, 4) : $cost;

                    $itemsData[] = [
                        'purchase_invoice_item_id' => ! empty($itemInput['purchase_invoice_item_id']) ? (int) $itemInput['purchase_invoice_item_id'] : null,
                        'inventory_batch_id' => ! empty($itemInput['inventory_batch_id']) ? (int) $itemInput['inventory_batch_id'] : null,
                        'product_id' => (int) $itemInput['product_id'],
                        'unit_id' => $unitId,
                        'conversion_factor' => $factor,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_cost' => $cost,
                        'unit_cost_base' => $unitCostBase,
                        'total_cost' => $lineTotal,
                        'notes' => $itemInput['notes'] ?? null,
                    ];
                }

                $purchaseReturn = PurchaseReturn::create([
                    'return_number' => PurchaseReturn::generateReturnNumber(),
                    'supplier_id' => (int) $validated['supplier_id'],
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'purchase_invoice_id' => ! empty($validated['purchase_invoice_id']) ? (int) $validated['purchase_invoice_id'] : null,
                    'return_date' => $validated['return_date'],
                    'status' => PurchaseReturn::STATUS_DRAFT,
                    'settlement_type' => $validated['settlement_type'],
                    'treasury_id' => ! empty($validated['treasury_id']) ? (int) $validated['treasury_id'] : null,
                    'total_amount' => $totalAmount,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($itemsData as $row) {
                    $row['purchase_return_id'] = $purchaseReturn->id;
                    PurchaseReturnItem::create($row);
                }

                if ($request->input('action') === 'post') {
                    $this->executePosting($purchaseReturn, $inventoryService);
                }

                return $purchaseReturn;
            });
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ المرتجع: '.$e->getMessage());
        }

        $message = $return->status === PurchaseReturn::STATUS_POSTED
            ? 'تم حفظ وترحيل مرتجع المشتريات وخصم الكميات من المخزون بنجاح.'
            : 'تم حفظ مسودة مرتجع المشتريات بنجاح.';

        return redirect()->route('purchase-returns.show', $return)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Display the specified purchase return.
     */
    public function show(PurchaseReturn $purchase_return): View
    {
        $purchase_return->load([
            'supplier',
            'warehouse',
            'purchaseInvoice',
            'treasury',
            'creator',
            'poster',
            'items.product.category',
            'items.unit',
            'items.inventoryBatch',
        ]);

        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();

        return view('purchases.returns.show', [
            'title' => 'مرتجع مشتريات: '.$purchase_return->return_number,
            'moduleName' => 'المشتريات والموردين',
            'return' => $purchase_return,
            'treasuries' => $treasuries,
        ]);
    }

    /**
     * Post a draft purchase return and atomically deduct items from the warehouse.
     */
    public function post(Request $request, PurchaseReturn $purchase_return, InventoryService $inventoryService): RedirectResponse
    {
        if ($purchase_return->status !== PurchaseReturn::STATUS_DRAFT) {
            return back()->with('error', 'لا يمكن ترحيل هذا المرتجع لأنه ليس في حالة مسودة.');
        }

        try {
            DB::transaction(function () use ($purchase_return, $inventoryService) {
                $this->executePosting($purchase_return, $inventoryService);
            });
        } catch (Exception $e) {
            return back()->with('error', 'فشل ترحيل المرتجع: '.$e->getMessage());
        }

        return redirect()->route('purchase-returns.show', $purchase_return)
            ->with('status', 'success')
            ->with('message', 'تم ترحيل مرتجع المشتريات وخصم الكميات بنجاح وتسوية حساب المورد.');
    }

    /**
     * Display a dedicated printable view of the purchase return.
     */
    public function print(Request $request, PurchaseReturn $purchase_return, PrintTemplateService $templateService): View
    {
        $purchase_return->loadMissing(['supplier', 'warehouse', 'purchaseInvoice', 'creator', 'items.product', 'items.unit']);

        $format = (string) $request->query('format', $request->query('paper_format', PrintTemplate::FORMAT_A4));
        if (! in_array($format, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $format = PrintTemplate::FORMAT_A4;
        }

        $template = $templateService->getEffectiveTemplate(PrintTemplate::DOC_PURCHASE_INVOICE, $format);
        $companySetting = CompanySetting::getSettings();

        return view('purchases.returns.print', [
            'return' => $purchase_return,
            'template' => $template,
            'companySetting' => $companySetting,
            'format' => $format,
            'autoPrint' => $request->boolean('autoprint', false),
        ]);
    }

    /**
     * Direct print purchase return receipt or A4/A5 document via the centralized Python printing engine.
     */
    public function directPrint(Request $request, PurchaseReturn $purchase_return, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'in:thermal_80mm,a4,a5'],
        ]);

        $printer = $validated['printer'] ?? null;
        $format = $validated['format'] ?? $request->query('format', 'thermal_80mm');
        $result = $printService->printPurchaseReturn($purchase_return, $format, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Internal atomic posting helper for purchase returns.
     */
    private function executePosting(PurchaseReturn $purchaseReturn, InventoryService $inventoryService): void
    {
        $purchaseReturn->loadMissing(['items.product', 'items.unit', 'items.inventoryBatch', 'supplier', 'purchaseInvoice']);

        $warehouseId = $purchaseReturn->warehouse_id;

        foreach ($purchaseReturn->items as $item) {
            $product = $item->product;
            $baseQty = (float) $item->base_quantity;

            if ($baseQty <= 0) {
                $factor = (float) ($item->conversion_factor > 0 ? $item->conversion_factor : 1.0);
                $baseQty = round((float) $item->quantity * $factor, 4);
            }

            // Check current stock balance
            $balance = StockBalance::where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $availableStock = $balance ? (float) $balance->quantity : 0.00;
            if ($availableStock < $baseQty) {
                throw new Exception("الرصيد المتاح للصنف ({$product->name}) في المستودع هو {$availableStock}، ولا يكفي لإرجاع كمية {$baseQty} قطعة أساسية.");
            }

            // If a specific batch was chosen and has enough quantity, deduct from it
            $batchDeducted = false;
            if ($item->inventory_batch_id) {
                $batch = InventoryBatch::where('id', $item->inventory_batch_id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if ($batch && (float) $batch->remaining_quantity >= $baseQty) {
                    $batch->decrement('remaining_quantity', $baseQty);
                    if ((float) $batch->remaining_quantity <= 0) {
                        $batch->update(['is_exhausted' => true]);
                    }

                    $balance->decrement('quantity', $baseQty);
                    $runningBalance = (float) StockBalance::where('product_id', $product->id)->sum('quantity');

                    StockMovement::create([
                        'movement_number' => StockMovement::generateMovementNumber(),
                        'type' => StockMovement::TYPE_OUT,
                        'product_id' => $product->id,
                        'from_warehouse_id' => $warehouseId,
                        'to_warehouse_id' => null,
                        'batch_id' => $batch->id,
                        'quantity' => $baseQty,
                        'running_balance' => $runningBalance,
                        'unit_cost' => (float) $item->unit_cost_base,
                        'reference_type' => 'purchase_return',
                        'reference_id' => $purchaseReturn->id,
                        'user_id' => auth()->id(),
                        'notes' => 'مرتجع مشتريات رقم: '.$purchaseReturn->return_number,
                    ]);

                    $batchDeducted = true;
                }
            }

            // If not deducted from specific batch, use standard FIFO stock deduction
            if (! $batchDeducted) {
                $inventoryService->deductStockFIFO(
                    productId: $product->id,
                    warehouseId: $warehouseId,
                    quantity: $baseQty,
                    notes: 'مرتجع مشتريات رقم: '.$purchaseReturn->return_number,
                    referenceType: 'purchase_return',
                    referenceId: $purchaseReturn->id,
                    userId: auth()->id()
                );
            }
        }

        // Financial settlement
        if ($purchaseReturn->settlement_type === PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE) {
            $supplier = Supplier::lockForUpdate()->find($purchaseReturn->supplier_id);
            if ($supplier) {
                $supplier->decrement('current_balance', (float) $purchaseReturn->total_amount);
            }

            if ($purchaseReturn->purchase_invoice_id && $purchaseReturn->purchaseInvoice) {
                $invoice = $purchaseReturn->purchaseInvoice;
                if ((float) $invoice->remaining_amount > 0) {
                    $deductFromInvoice = min((float) $invoice->remaining_amount, (float) $purchaseReturn->total_amount);
                    $invoice->decrement('remaining_amount', $deductFromInvoice);
                }
            }
        } elseif ($purchaseReturn->settlement_type === PurchaseReturn::SETTLEMENT_CASH_REFUND && $purchaseReturn->treasury_id) {
            $treasury = Treasury::findOrFail($purchaseReturn->treasury_id);
            $treasury->recordDeposit(
                amount: (float) $purchaseReturn->total_amount,
                sourceType: 'purchase_return',
                sourceId: $purchaseReturn->id,
                description: "استرداد نقدي لمرتجع مشتريات رقم {$purchaseReturn->return_number} من المورد {$purchaseReturn->supplier->name}",
                userId: auth()->id(),
                date: $purchaseReturn->return_date ? $purchaseReturn->return_date->toDateString() : now()->toDateString()
            );
        }

        $purchaseReturn->update([
            'status' => PurchaseReturn::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        // Record general ledger journal entry
        app(AccountingService::class)->recordPurchaseReturn($purchaseReturn->fresh());
    }
}
