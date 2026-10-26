<?php

namespace App\Http\Controllers;

use App\Models\BatchCostMovement;
use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\PrintTemplate;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
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

class SalesReturnController extends Controller
{
    /**
     * Display a listing of sales returns.
     */
    public function index(Request $request): View
    {
        $query = SalesReturn::with(['customer', 'warehouse', 'salesInvoice', 'creator', 'items.product']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('return_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('salesInvoice', fn ($iq) => $iq->where('invoice_number', 'like', "%{$term}%"));
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

        $returns = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $metrics = [
            'total_count' => SalesReturn::count(),
            'posted_total' => (float) SalesReturn::where('status', SalesReturn::STATUS_POSTED)->sum('total_amount'),
            'draft_count' => SalesReturn::where('status', SalesReturn::STATUS_DRAFT)->count(),
        ];

        return view('sales.returns.index', [
            'title' => 'مرتجعات فواتير المبيعات',
            'moduleName' => 'المبيعات والعملاء',
            'returns' => $returns,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'metrics' => $metrics,
            'currentSearch' => $request->input('search'),
            'currentStatus' => $request->input('status'),
            'currentWarehouseId' => $request->input('warehouse_id'),
            'currentCustomerId' => $request->input('customer_id'),
        ]);
    }

    /**
     * Show the form for creating a new sales return.
     */
    public function create(Request $request): View
    {
        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $suggestedNumber = SalesReturn::generateReturnNumber();

        $selectedInvoice = null;
        $returnableItems = [];

        if ($request->filled('sales_invoice_id') || $request->filled('invoice_id')) {
            $invId = (int) ($request->input('sales_invoice_id') ?: $request->input('invoice_id'));
            $selectedInvoice = SalesInvoice::with([
                'customer',
                'warehouse',
                'items.product',
                'items.unit',
                'items.batchAllocations.batch',
            ])->find($invId);

            if ($selectedInvoice) {
                // Calculate already returned quantities per item
                $priorReturnedMap = SalesReturnItem::whereHas('salesReturn', function ($q) use ($selectedInvoice) {
                    $q->where('sales_invoice_id', $selectedInvoice->id);
                })
                    ->groupBy('sales_invoice_item_id')
                    ->selectRaw('sales_invoice_item_id, SUM(quantity) as returned_qty')
                    ->pluck('returned_qty', 'sales_invoice_item_id')
                    ->toArray();

                foreach ($selectedInvoice->items as $item) {
                    $soldQty = (float) $item->quantity;
                    $alreadyReturned = (float) ($priorReturnedMap[$item->id] ?? 0);
                    $returnable = max(0, round($soldQty - $alreadyReturned, 4));

                    $returnableItems[] = [
                        'item' => $item,
                        'sold_quantity' => $soldQty,
                        'already_returned' => $alreadyReturned,
                        'returnable_quantity' => $returnable,
                    ];
                }
            }
        }

        // List posted invoices for fast invoice picker
        $invoices = SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)
            ->with(['customer', 'warehouse'])
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return view('sales.returns.create', [
            'title' => 'تحرير إذن مرتجع مبيعات جديد',
            'moduleName' => 'المبيعات والعملاء',
            'suggestedNumber' => $suggestedNumber,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'invoices' => $invoices,
            'selectedInvoice' => $selectedInvoice,
            'returnableItems' => $returnableItems,
        ]);
    }

    /**
     * Store a newly created sales return in storage.
     */
    public function store(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validate([
            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'return_number' => ['required', 'string', 'max:60', 'unique:sales_returns,return_number'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', 'in:draft,post'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_refund_price' => ['required', 'numeric', 'min:0'],
        ], [
            'sales_invoice_id.required' => 'يرجى اختيار فاتورة المبيعات المراد الإرجاع منها.',
            'return_number.required' => 'رقم إذن المرتجع مطلوب.',
            'return_number.unique' => 'رقم إذن المرتجع مسجل مسبقاً.',
            'items.required' => 'يجب إدراج صنف واحد على الأقل للمرتجع.',
        ]);

        $invoice = SalesInvoice::with(['customer', 'warehouse'])->findOrFail($validated['sales_invoice_id']);

        if ($invoice->status !== SalesInvoice::STATUS_POSTED) {
            return back()->withInput()->with('error', 'لا يمكن تحرير إذن مرتجع إلا لفاتورة مبيعات في حالة (مُرحلة).');
        }

        // Filter items that have quantity > 0
        $validItems = array_filter($validated['items'], fn ($i) => (float) ($i['quantity'] ?? 0) > 0);

        if (empty($validItems)) {
            return back()->withInput()->with('error', 'يرجى تحديد كمية أكبر من الصفر لصنف واحد على الأقل لإتمام المرتجع.');
        }

        try {
            $return = DB::transaction(function () use ($validated, $validItems, $invoice, $inventoryService) {
                // Calculate prior returned quantities
                $priorReturnedMap = SalesReturnItem::whereHas('salesReturn', function ($q) use ($invoice) {
                    $q->where('sales_invoice_id', $invoice->id);
                })
                    ->groupBy('sales_invoice_item_id')
                    ->selectRaw('sales_invoice_item_id, SUM(quantity) as returned_qty')
                    ->pluck('returned_qty', 'sales_invoice_item_id')
                    ->toArray();

                $totalAmount = 0.00;
                $itemsData = [];

                foreach ($validItems as $row) {
                    $invItem = SalesInvoiceItem::with(['product', 'batchAllocations.batch'])->findOrFail($row['sales_invoice_item_id']);
                    $qtyToReturn = (float) $row['quantity'];
                    $refundPrice = (float) $row['unit_refund_price'];

                    $soldQty = (float) $invItem->quantity;
                    $alreadyReturned = (float) ($priorReturnedMap[$invItem->id] ?? 0);
                    $maxReturnable = max(0, round($soldQty - $alreadyReturned, 4));

                    if ($qtyToReturn > $maxReturnable) {
                        throw new Exception("الكمية المطلوب إرجاعها للصنف ({$invItem->product->name}) هي {$qtyToReturn} وتتجاوز المتبقي القابل للإرجاع ({$maxReturnable}).");
                    }

                    $factor = (float) ($invItem->conversion_factor > 0 ? $invItem->conversion_factor : 1.0);
                    $baseQty = round($qtyToReturn * $factor, 4);
                    $lineRefund = round($qtyToReturn * $refundPrice, 2);
                    $totalAmount += $lineRefund;

                    // Estimate COGS base
                    $allocation = $invItem->batchAllocations->first();
                    $unitCostBase = $allocation ? (float) $allocation->unit_cost_base : (float) ($invItem->product->base_cost_price ?? $invItem->product->cost_price);
                    $lineCogsReversed = round($baseQty * $unitCostBase, 2);

                    $itemsData[] = [
                        'sales_invoice_item_id' => $invItem->id,
                        'inventory_batch_id' => $allocation?->inventory_batch_id,
                        'product_id' => $invItem->product_id,
                        'unit_id' => $invItem->unit_id,
                        'conversion_factor' => $factor,
                        'quantity' => $qtyToReturn,
                        'base_quantity' => $baseQty,
                        'unit_refund_price' => $refundPrice,
                        'unit_cost_base' => $unitCostBase,
                        'total_refund' => $lineRefund,
                        'total_cogs_reversed' => $lineCogsReversed,
                        'notes' => $row['notes'] ?? null,
                    ];
                }

                $salesReturn = SalesReturn::create([
                    'return_number' => $validated['return_number'],
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'warehouse_id' => $invoice->warehouse_id,
                    'return_date' => $validated['return_date'],
                    'status' => SalesReturn::STATUS_DRAFT,
                    'total_amount' => $totalAmount,
                    'total_cogs_returned' => 0.00,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($itemsData as $itemRow) {
                    $itemRow['sales_return_id'] = $salesReturn->id;
                    SalesReturnItem::create($itemRow);
                }

                if ($validated['action'] === 'post') {
                    $this->executePosting($salesReturn, $inventoryService);
                }

                return $salesReturn;
            });
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'تعذر حفظ مرتجع المبيعات: '.$e->getMessage());
        }

        $message = $return->status === SalesReturn::STATUS_POSTED
            ? 'تم حفظ وترحيل مرتجع المبيعات واسترجاع البضاعة للمخزن بنجاح وتسوية حساب العميل.'
            : 'تم حفظ مسودة مرتجع المبيعات بنجاح للاعتماد والترحيل.';

        return redirect()->route('sales-returns.show', $return)
            ->with('status', 'success')
            ->with('message', $message);
    }

    /**
     * Display the specified sales return.
     */
    public function show(SalesReturn $sales_return): View
    {
        $sales_return->loadMissing([
            'customer',
            'warehouse',
            'salesInvoice',
            'creator',
            'items.product',
            'items.unit',
            'items.batch',
        ]);

        return view('sales.returns.show', [
            'title' => 'إذن مرتجع مبيعات: '.$sales_return->return_number,
            'moduleName' => 'المبيعات والعملاء',
            'return' => $sales_return,
        ]);
    }

    /**
     * Post a draft sales return and atomically restore inventory to the warehouse.
     */
    public function post(Request $request, SalesReturn $sales_return, InventoryService $inventoryService): RedirectResponse
    {
        if ($sales_return->status !== SalesReturn::STATUS_DRAFT) {
            return back()->with('error', 'لا يمكن ترحيل هذا المرتجع لأنه ليس في حالة مسودة.');
        }

        try {
            DB::transaction(function () use ($sales_return, $inventoryService) {
                $this->executePosting($sales_return, $inventoryService);
            });
        } catch (Exception $e) {
            return back()->with('error', 'فشل ترحيل مرتجع المبيعات: '.$e->getMessage());
        }

        return redirect()->route('sales-returns.show', $sales_return)
            ->with('status', 'success')
            ->with('message', 'تم ترحيل مرتجع المبيعات واسترجاع الأصناف للمخزن وتسوية حساب العميل بنجاح.');
    }

    /**
     * Display a dedicated printable view of the sales return.
     */
    public function print(Request $request, SalesReturn $sales_return, PrintTemplateService $templateService): View
    {
        $sales_return->loadMissing(['customer', 'warehouse', 'salesInvoice', 'creator', 'items.product', 'items.unit']);

        $format = (string) $request->query('format', $request->query('paper_format', PrintTemplate::FORMAT_A4));
        if (! in_array($format, PrintTemplateService::SUPPORTED_FORMATS, true)) {
            $format = PrintTemplate::FORMAT_A4;
        }

        $template = $templateService->getEffectiveTemplate(PrintTemplate::DOC_SALES_INVOICE, $format);
        $companySetting = CompanySetting::getSettings();

        return view('sales.returns.print', [
            'return' => $sales_return,
            'template' => $template,
            'companySetting' => $companySetting,
            'format' => $format,
            'autoPrint' => $request->boolean('autoprint', false),
        ]);
    }

    /**
     * Direct print sales return receipt or A4/A5 document.
     */
    public function directPrint(Request $request, SalesReturn $sales_return, PythonPrintEngineService $printService): JsonResponse
    {
        $validated = $request->validate([
            'printer' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'in:thermal_80mm,a4,a5'],
        ]);

        $printer = $validated['printer'] ?? null;
        $format = $validated['format'] ?? $request->query('format', 'thermal_80mm');
        $result = $printService->printSalesReturn($sales_return, $format, $printer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Internal atomic posting helper for sales returns.
     */
    private function executePosting(SalesReturn $salesReturn, InventoryService $inventoryService): void
    {
        $salesReturn->loadMissing(['items.product', 'items.salesInvoiceItem.batchAllocations.batch', 'salesInvoice', 'customer']);

        $totalCogsReturned = 0.00;
        $warehouseId = $salesReturn->warehouse_id;

        foreach ($salesReturn->items as $item) {
            $baseQty = (float) $item->base_quantity;

            // Find batch to restore to:
            $batch = null;
            if ($item->inventory_batch_id) {
                $batch = InventoryBatch::find($item->inventory_batch_id);
            }
            if (! $batch && $item->salesInvoiceItem) {
                $allocation = $item->salesInvoiceItem->batchAllocations->first();
                if ($allocation) {
                    $batch = $allocation->batch;
                }
            }

            $effectiveCost = $batch ? (float) $batch->cost_price : (float) ($item->product->base_cost_price ?? $item->product->cost_price);
            $cogsForLine = round($baseQty * $effectiveCost, 2);
            $totalCogsReturned += $cogsForLine;

            // 1. Restore to batch if exists and in same warehouse, or create inward batch
            if ($batch && (int) $batch->warehouse_id === (int) $warehouseId) {
                $batch->remaining_quantity = round((float) $batch->remaining_quantity + $baseQty, 4);
                $batch->accumulated_cogs_deducted = max(0.00, round((float) $batch->accumulated_cogs_deducted - $cogsForLine, 2));
                $batch->is_exhausted = false;
                $batch->save();
            } else {
                $batch = InventoryBatch::create([
                    'batch_number' => InventoryBatch::generateBatchNumber(),
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'purchase_invoice_id' => null,
                    'initial_quantity' => $baseQty,
                    'remaining_quantity' => $baseQty,
                    'cost_price' => $effectiveCost,
                    'retail_price' => $item->product->retail_price,
                    'wholesale_price' => $item->product->wholesale_price,
                    'distributor_price' => $item->product->distributor_price,
                    'total_initial_cost' => $cogsForLine,
                    'accumulated_cogs_deducted' => 0.00,
                    'unit_id' => $item->unit_id,
                    'conversion_factor' => $item->conversion_factor,
                    'source_type' => 'sales_return',
                    'source_id' => $salesReturn->id,
                    'received_at' => now(),
                    'is_exhausted' => false,
                    'notes' => 'مرتجع مبيعات: '.$salesReturn->return_number,
                ]);
            }

            // 2. Increment StockBalance
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $item->product_id, 'warehouse_id' => $warehouseId],
                ['quantity' => 0.00, 'reserved_quantity' => 0.00]
            );
            $balance->increment('quantity', $baseQty);
            $runningBalance = (float) StockBalance::where('product_id', $item->product_id)->sum('quantity');

            // 3. Create StockMovement IN
            StockMovement::create([
                'movement_number' => StockMovement::generateMovementNumber(),
                'type' => StockMovement::TYPE_IN,
                'product_id' => $item->product_id,
                'from_warehouse_id' => null,
                'to_warehouse_id' => $warehouseId,
                'batch_id' => $batch?->id,
                'quantity' => $baseQty,
                'running_balance' => $runningBalance,
                'unit_cost' => $effectiveCost,
                'reference_type' => 'sales_return',
                'reference_id' => $salesReturn->id,
                'user_id' => auth()->id(),
                'customer_id' => $salesReturn->customer_id,
                'notes' => 'مرتجع مبيعات رقم: '.$salesReturn->return_number,
            ]);

            // 4. Create BatchCostMovement
            if ($batch) {
                $runningCostBalance = max(0.00, round((float) $batch->total_initial_cost - (float) $batch->accumulated_cogs_deducted, 2));
                BatchCostMovement::create([
                    'batch_id' => $batch->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => 'sale_return_in',
                    'reference_type' => 'sales_return',
                    'reference_id' => $salesReturn->id,
                    'quantity_base' => $baseQty,
                    'unit_cost_base' => $effectiveCost,
                    'total_cost' => $cogsForLine,
                    'running_batch_quantity' => $batch->remaining_quantity,
                    'running_batch_cost_balance' => $runningCostBalance,
                    'user_id' => auth()->id(),
                    'notes' => 'مرتجع مبيعات رقم: '.$salesReturn->return_number,
                ]);
            }

            $item->update([
                'inventory_batch_id' => $batch?->id,
                'unit_cost_base' => $effectiveCost,
                'total_cogs_reversed' => $cogsForLine,
            ]);
        }

        // 5. Adjust customer debt if customer exists
        if ($salesReturn->customer_id) {
            $customer = Customer::lockForUpdate()->find($salesReturn->customer_id);
            if ($customer) {
                $customer->decrement('current_balance', (float) $salesReturn->total_amount);
            }
        }

        // 6. Cash refund in active cashier shift if open
        $activeShift = CashierShift::getActiveShift(auth()->id(), (int) $warehouseId);
        if ($activeShift) {
            $activeShift->recordCashReturn((float) $salesReturn->total_amount, $salesReturn->id);
        }

        $salesReturn->update([
            'status' => SalesReturn::STATUS_POSTED,
            'posted_at' => now(),
            'total_cogs_returned' => $totalCogsReturned,
        ]);

        // Record general ledger journal entry
        app(AccountingService::class)->recordSalesReturn($salesReturn->fresh());
    }
}
