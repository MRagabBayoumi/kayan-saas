<?php

namespace App\Services;

use App\Models\BatchCostMovement;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItemBatch;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Add stock to a specific warehouse (Purchase, Opening Balance, or Return).
     * Creates an inventory batch for FIFO tracking and a stock movement record.
     *
     * @return array{batch: InventoryBatch, movement: StockMovement, balance: StockBalance}
     */
    public function addStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?float $costPrice = null,
        ?string $notes = null,
        string $referenceType = 'manual_in',
        ?int $referenceId = null,
        ?int $userId = null,
        ?Carbon $receivedAt = null,
        ?int $supplierId = null,
        ?float $retailPrice = null,
        ?float $wholesalePrice = null,
        ?float $distributorPrice = null,
        ?float $totalInitialCost = null,
        ?int $unitId = null,
        float $conversionFactor = 1.0000,
        string $sourceType = 'purchase_invoice',
        ?int $sourceId = null
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('الكمية المضافة يجب أن تكون أكبر من الصفر.');
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $costPrice,
            $notes,
            $referenceType,
            $referenceId,
            $userId,
            $receivedAt,
            $supplierId,
            $retailPrice,
            $wholesalePrice,
            $distributorPrice,
            $totalInitialCost,
            $unitId,
            $conversionFactor,
            $sourceType,
            $sourceId
        ) {
            $product = Product::findOrFail($productId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            $effectiveCost = $costPrice !== null ? (float) $costPrice : (float) ($product->base_cost_price ?? $product->cost_price);
            $effectiveInitialCost = $totalInitialCost !== null ? $totalInitialCost : round($quantity * $effectiveCost, 2);

            // 1. Create FIFO Inventory Batch with pricing tiers and cost conservation
            $batch = InventoryBatch::create([
                'batch_number' => InventoryBatch::generateBatchNumber(),
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'purchase_invoice_id' => $referenceType === 'purchase_invoice' ? $referenceId : null,
                'initial_quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'cost_price' => $effectiveCost,
                'retail_price' => $retailPrice ?? (! is_null($product->retail_price) ? (float) $product->retail_price : null),
                'wholesale_price' => $wholesalePrice ?? (! is_null($product->wholesale_price) ? (float) $product->wholesale_price : null),
                'distributor_price' => $distributorPrice ?? (! is_null($product->distributor_price) ? (float) $product->distributor_price : (! is_null($product->wholesale_price) ? (float) $product->wholesale_price : null)),
                'total_initial_cost' => $effectiveInitialCost,
                'accumulated_cogs_deducted' => 0.00,
                'unit_id' => $unitId,
                'conversion_factor' => $conversionFactor,
                'source_type' => $sourceType,
                'source_id' => $sourceId ?? $referenceId,
                'received_at' => $receivedAt ?? now(),
                'is_exhausted' => false,
                'notes' => $notes,
            ]);

            // 2. Update Warehouse Stock Balance
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => 0.00, 'reserved_quantity' => 0.00]
            );

            $balance->increment('quantity', $quantity);

            $runningBalance = (float) StockBalance::where('product_id', $product->id)->sum('quantity');

            // 3. Create Audit Stock Movement
            $movement = StockMovement::create([
                'movement_number' => StockMovement::generateMovementNumber(),
                'type' => StockMovement::TYPE_IN,
                'product_id' => $product->id,
                'from_warehouse_id' => null,
                'to_warehouse_id' => $warehouse->id,
                'batch_id' => $batch->id,
                'quantity' => $quantity,
                'running_balance' => $runningBalance,
                'unit_cost' => $effectiveCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $userId,
                'supplier_id' => $supplierId,
                'notes' => $notes,
            ]);

            // 4. Record in Physical Batch Cost Movements Ledger
            $batchCostMovementType = match ($referenceType) {
                'opening_balance' => 'opening_in',
                'sales_return' => 'sale_return_in',
                default => 'purchase_in',
            };

            BatchCostMovement::create([
                'batch_id' => $batch->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'movement_type' => $batchCostMovementType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity_base' => $quantity,
                'unit_cost_base' => $effectiveCost,
                'total_cost' => $effectiveInitialCost,
                'running_batch_quantity' => $quantity,
                'running_batch_cost_balance' => $effectiveInitialCost,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            return [
                'batch' => $batch,
                'movement' => $movement,
                'balance' => $balance->fresh(),
            ];
        });
    }

    /**
     * Deduct stock using FIFO (First-In, First-Out) logic.
     * Consumes older batches first and records granular movement lines.
     *
     * @return array{movements: list<StockMovement>, total_deducted: float, total_cogs: float}
     */
    public function deductStockFIFO(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?string $notes = null,
        string $referenceType = 'manual_out',
        ?int $referenceId = null,
        ?int $userId = null,
        ?int $customerId = null
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('الكمية المطلوب صرفها يجب أن تكون أكبر من الصفر.');
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $notes,
            $referenceType,
            $referenceId,
            $userId,
            $customerId
        ) {
            $product = Product::findOrFail($productId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            $balance = StockBalance::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            $currentStock = $balance ? (float) $balance->quantity : 0.00;

            if ($currentStock < $quantity) {
                throw new InvalidArgumentException(
                    "الرصيد المتاح للصنف ({$product->name}) في مستودع ({$warehouse->name}) هو {$currentStock} ولا يكفي لصرف {$quantity}."
                );
            }

            // Retrieve available batches in strict FIFO order
            $batches = InventoryBatch::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->availableFIFO()
                ->lockForUpdate()
                ->get();

            $remainingToDeduct = $quantity;
            $movements = [];
            $totalCogs = 0.00;

            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                $batchAvailable = (float) $batch->remaining_quantity;
                $deductFromThisBatch = min($remainingToDeduct, $batchAvailable);

                $batch->remaining_quantity = $batchAvailable - $deductFromThisBatch;
                if ($batch->remaining_quantity <= 0) {
                    $batch->is_exhausted = true;
                }
                $batch->save();

                $batchCogs = $deductFromThisBatch * (float) $batch->cost_price;
                $totalCogs += $batchCogs;

                $balance->decrement('quantity', $deductFromThisBatch);
                $runningBalance = (float) StockBalance::where('product_id', $product->id)->sum('quantity');

                // Create movement per consumed batch chunk for perfect auditability
                $movement = StockMovement::create([
                    'movement_number' => StockMovement::generateMovementNumber(),
                    'type' => StockMovement::TYPE_OUT,
                    'product_id' => $product->id,
                    'from_warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => null,
                    'batch_id' => $batch->id,
                    'quantity' => $deductFromThisBatch,
                    'running_balance' => $runningBalance,
                    'unit_cost' => (float) $batch->cost_price,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'user_id' => $userId,
                    'customer_id' => $customerId,
                    'notes' => $notes,
                ]);

                $movements[] = $movement;
                $remainingToDeduct -= $deductFromThisBatch;
            }

            // If there were unbatched historical stocks, handle remaining
            if ($remainingToDeduct > 0) {
                $balance->decrement('quantity', $remainingToDeduct);
                $runningBalance = (float) StockBalance::where('product_id', $product->id)->sum('quantity');

                $movement = StockMovement::create([
                    'movement_number' => StockMovement::generateMovementNumber(),
                    'type' => StockMovement::TYPE_OUT,
                    'product_id' => $product->id,
                    'from_warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => null,
                    'batch_id' => null,
                    'quantity' => $remainingToDeduct,
                    'running_balance' => $runningBalance,
                    'unit_cost' => (float) ($product->base_cost_price ?? $product->cost_price),
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'user_id' => $userId,
                    'customer_id' => $customerId,
                    'notes' => $notes,
                ]);
                $movements[] = $movement;
                $totalCogs += $remainingToDeduct * (float) ($product->base_cost_price ?? $product->cost_price);
            }

            return [
                'movements' => $movements,
                'total_deducted' => $quantity,
                'total_cogs' => $totalCogs,
            ];
        });
    }

    /**
     * Transfer stock between warehouses atomically.
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        ?string $notes = null,
        ?int $userId = null
    ): array {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('المستودع المحول منه والمحول إليه يجب أن يكونا مختلفين.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('كمية التحويل يجب أن تكون أكبر من الصفر.');
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use (
            $productId,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            $notes,
            $userId
        ) {
            $deduction = $this->deductStockFIFO(
                $productId,
                $fromWarehouseId,
                $quantity,
                'تحويل صادر: '.$notes,
                'stock_transfer',
                null,
                $userId
            );

            // Add stock to destination warehouse
            $addition = $this->addStock(
                $productId,
                $toWarehouseId,
                $quantity,
                $deduction['total_cogs'] / $quantity,
                'تحويل وارد: '.$notes,
                'stock_transfer',
                null,
                $userId
            );

            // Create transfer movement linking both warehouses
            $transferMovement = StockMovement::create([
                'movement_number' => StockMovement::generateMovementNumber(),
                'type' => StockMovement::TYPE_TRANSFER,
                'product_id' => $productId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'batch_id' => $addition['batch']->id,
                'quantity' => $quantity,
                'running_balance' => (float) StockBalance::where('product_id', $productId)->sum('quantity'),
                'unit_cost' => $deduction['total_cogs'] / $quantity,
                'reference_type' => 'stock_transfer',
                'reference_id' => null,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            return [
                'transfer_movement' => $transferMovement,
                'deduction' => $deduction,
                'addition' => $addition,
            ];
        });
    }

    /**
     * Perform inventory count adjustment (تسوية جردية).
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        float $actualQuantity,
        string $reason,
        ?int $userId = null
    ): array {
        if ($actualQuantity < 0) {
            throw new InvalidArgumentException('الرصيد الفعلي لا يمكن أن يكون سالباً.');
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($productId, $warehouseId, $actualQuantity, $reason, $userId) {
            $balance = StockBalance::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0.00, 'reserved_quantity' => 0.00]
            );

            $currentQuantity = (float) $balance->quantity;
            $diff = $actualQuantity - $currentQuantity;

            if ($diff == 0) {
                return ['status' => 'unchanged', 'balance' => $balance];
            }

            if ($diff > 0) {
                // Shortage correction / surplus: Add stock
                $result = $this->addStock(
                    $productId,
                    $warehouseId,
                    $diff,
                    null,
                    'تسوية جردية بالزيادة: '.$reason,
                    'inventory_adjustment',
                    null,
                    $userId
                );

                return ['status' => 'increased', 'diff' => $diff, 'result' => $result];
            }

            // Deficit: Deduct stock via FIFO
            $result = $this->deductStockFIFO(
                $productId,
                $warehouseId,
                abs($diff),
                'تسوية جردية بالعجز: '.$reason,
                'inventory_adjustment',
                null,
                $userId
            );

            return ['status' => 'decreased', 'diff' => $diff, 'result' => $result];
        });
    }

    /**
     * Get aggregate inventory metrics.
     *
     * @return array<string, mixed>
     */
    public function getInventoryMetrics(): array
    {
        $totalProducts = Product::count();
        $totalStockUnits = (float) StockBalance::sum('quantity');

        // Total valuation based on FIFO remaining batches
        $batchValuation = (float) InventoryBatch::where('is_exhausted', false)
            ->sum(DB::raw('remaining_quantity * cost_price'));

        // If some items don't have batches yet, calculate from products and stock_balances
        if ($batchValuation <= 0 && $totalStockUnits > 0) {
            $batchValuation = (float) Product::with('stockBalances')->get()->sum(function (Product $p) {
                return $p->total_stock * $p->base_cost_price;
            });
        }

        $lowStockCount = Product::whereHas('stockBalances')
            ->withSum('stockBalances', 'quantity')
            ->get()
            ->filter(fn (Product $p) => ((float) $p->stock_balances_sum_quantity) <= (float) $p->min_stock_alert && ((float) $p->stock_balances_sum_quantity) > 0)
            ->count();

        $outOfStockCount = Product::withSum('stockBalances', 'quantity')
            ->get()
            ->filter(fn (Product $p) => ((float) $p->stock_balances_sum_quantity) <= 0)
            ->count();

        return [
            'total_products' => $totalProducts,
            'total_stock_units' => $totalStockUnits,
            'total_inventory_value' => $batchValuation,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];
    }

    /**
     * Deduct stock using FIFO logic with batch-specific price tiers and granular allocation recording.
     *
     * @return array{
     *   allocations: list<SalesInvoiceItemBatch>,
     *   total_base_deducted: float,
     *   total_cogs: float,
     *   total_revenue: float,
     *   total_profit: float
     * }
     */
    public function deductStockFIFOWithPricing(
        int $productId,
        int $warehouseId,
        float $baseQuantity,
        string $priceTier = 'retail',
        ?float $overrideUnitPriceBase = null,
        float $proratedLineDiscount = 0.00,
        ?string $notes = null,
        string $referenceType = 'sales_invoice',
        ?int $referenceId = null,
        ?int $salesInvoiceItemId = null,
        ?int $userId = null,
        ?int $customerId = null,
        bool $allowUnpricedBatches = false
    ): array {
        if ($baseQuantity <= 0) {
            throw new InvalidArgumentException('الكمية المطلوب صرفها يجب أن تكون أكبر من الصفر.');
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $baseQuantity,
            $priceTier,
            $overrideUnitPriceBase,
            $notes,
            $referenceType,
            $referenceId,
            $salesInvoiceItemId,
            $userId,
            $customerId,
            $allowUnpricedBatches
        ) {
            $product = Product::findOrFail($productId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            $balance = StockBalance::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            $currentStock = $balance ? (float) $balance->quantity : 0.00;

            if ($currentStock < $baseQuantity) {
                throw new InvalidArgumentException(
                    "الرصيد المتاح للصنف ({$product->name}) في مستودع ({$warehouse->name}) هو {$currentStock} ولا يكفي لصرف {$baseQuantity}."
                );
            }

            // Retrieve available batches in strict FIFO order
            $batches = InventoryBatch::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->availableFIFO()
                ->lockForUpdate()
                ->get();

            // Total available across active batches
            $totalBatchAvailable = (float) $batches->sum('remaining_quantity');
            if ($totalBatchAvailable < $baseQuantity) {
                throw new InvalidArgumentException(
                    "إجمالي رصيد الدفعات المتاحة للصنف ({$product->name}) هو {$totalBatchAvailable} ولا يكفي لصرف {$baseQuantity}."
                );
            }

            // Enforce Rule 1 & Option A: Ensure pricing is available or auto-populate from product master prices
            $accumulated = 0.0;
            foreach ($batches as $checkBatch) {
                $accumulated += (float) $checkBatch->remaining_quantity;

                if (! $allowUnpricedBatches && ! $checkBatch->hasPricing() && is_null($overrideUnitPriceBase)) {
                    throw new InvalidArgumentException(
                        "الدفعة رقم ({$checkBatch->batch_number}) للصنف ({$product->name}) ليس لها أسعار بيع معتمدة؛ يرجى اعتماد تسعيرة الدفعة أولاً قبل الصرف."
                    );
                }

                // If batch lacks explicit price tiers, auto-populate from product master prices
                if (is_null($checkBatch->retail_price) || is_null($checkBatch->wholesale_price)) {
                    if (! is_null($product->retail_price) || ! is_null($product->wholesale_price)) {
                        $checkBatch->retail_price = $checkBatch->retail_price ?? (! is_null($product->retail_price) ? (float) $product->retail_price : null);
                        $checkBatch->wholesale_price = $checkBatch->wholesale_price ?? (! is_null($product->wholesale_price) ? (float) $product->wholesale_price : null);
                        $checkBatch->distributor_price = $checkBatch->distributor_price ?? (! is_null($product->distributor_price) ? (float) $product->distributor_price : $checkBatch->wholesale_price);
                        $checkBatch->save();
                    }
                }

                if ($accumulated >= $baseQuantity) {
                    break;
                }
            }

            $remainingToDeduct = $baseQuantity;
            $allocations = [];
            $totalCogs = 0.00;
            $totalRevenue = 0.00;

            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                $batchAvailable = (float) $batch->remaining_quantity;
                $deductFromThisBatch = min($remainingToDeduct, $batchAvailable);

                // Determine base price tier
                $standardTierPrice = $batch->getPriceForTier($priceTier);
                if ($standardTierPrice === null) {
                    $standardTierPrice = match ($priceTier) {
                        'wholesale' => (float) $product->wholesale_price,
                        'distributor' => (float) ($product->distributor_price ?? $product->wholesale_price),
                        default => (float) $product->retail_price,
                    };
                }

                // Determine actual selling price
                $actualPriceBase = $overrideUnitPriceBase !== null ? $overrideUnitPriceBase : $standardTierPrice;
                $subtotalPortion = round($deductFromThisBatch * $actualPriceBase, 2);

                // Exact COGS calculation with penny reconciliation on final unit depletion
                $isFinal = ($batchAvailable - $deductFromThisBatch) <= 0.0001;
                $batchCogs = $batch->calculateExactCogs($deductFromThisBatch, $isFinal);

                // Update batch remaining quantity and accumulated COGS
                $batch->remaining_quantity = round($batchAvailable - $deductFromThisBatch, 4);
                $batch->accumulated_cogs_deducted = round((float) $batch->accumulated_cogs_deducted + $batchCogs, 2);
                if ($batch->remaining_quantity <= 0.0001) {
                    $batch->is_exhausted = true;
                }
                $batch->save();

                // Update Warehouse Balance
                $balance->decrement('quantity', $deductFromThisBatch);
                $runningBalance = (float) StockBalance::where('product_id', $product->id)->sum('quantity');

                // Audit Stock Movement
                StockMovement::create([
                    'movement_number' => StockMovement::generateMovementNumber(),
                    'type' => StockMovement::TYPE_OUT,
                    'product_id' => $product->id,
                    'from_warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => null,
                    'batch_id' => $batch->id,
                    'quantity' => $deductFromThisBatch,
                    'running_balance' => $runningBalance,
                    'unit_cost' => (float) $batch->cost_price,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'user_id' => $userId,
                    'customer_id' => $customerId,
                    'notes' => $notes,
                ]);

                // Physical Batch Cost Movements Ledger
                $runningCostBalance = max(0.00, round((float) $batch->total_initial_cost - (float) $batch->accumulated_cogs_deducted, 2));

                BatchCostMovement::create([
                    'batch_id' => $batch->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'movement_type' => 'sale_out',
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'quantity_base' => -$deductFromThisBatch,
                    'unit_cost_base' => (float) $batch->cost_price,
                    'total_cost' => -$batchCogs,
                    'running_batch_quantity' => $batch->remaining_quantity,
                    'running_batch_cost_balance' => $runningCostBalance,
                    'user_id' => $userId,
                    'notes' => $notes,
                ]);

                // Create Sales Allocation Record if sales invoice item exists
                if ($salesInvoiceItemId) {
                    $profitMargin = round($subtotalPortion - $batchCogs, 2);
                    $allocation = SalesInvoiceItemBatch::create([
                        'sales_invoice_item_id' => $salesInvoiceItemId,
                        'inventory_batch_id' => $batch->id,
                        'quantity_base' => $deductFromThisBatch,
                        'unit_cost_base' => (float) $batch->cost_price,
                        'total_cogs' => $batchCogs,
                        'batch_tier_price_base' => $standardTierPrice,
                        'actual_selling_price_base' => $actualPriceBase,
                        'subtotal_sold' => $subtotalPortion,
                        'profit_margin' => $profitMargin,
                        'is_reversed' => false,
                    ]);

                    $allocations[] = $allocation;
                }
                $totalCogs += $batchCogs;
                $totalRevenue += $subtotalPortion;
                $remainingToDeduct = round($remainingToDeduct - $deductFromThisBatch, 4);
            }

            return [
                'allocations' => $allocations,
                'total_base_deducted' => $baseQuantity,
                'total_cogs' => round($totalCogs, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_profit' => round($totalRevenue - $totalCogs, 2),
            ];
        });
    }

    /**
     * Void / Reverse a posted sales invoice atomically with double-reversal safeguards.
     */
    public function voidSalesInvoice(SalesInvoice $invoice, ?int $userId = null, ?string $reason = null): void
    {
        if ($invoice->status !== SalesInvoice::STATUS_POSTED) {
            throw new InvalidArgumentException('يمكن فقط إلغاء الفواتير التي في حالة مرحلة (posted).');
        }

        $userId = $userId ?? auth()->id();

        DB::transaction(function () use ($invoice, $userId, $reason) {
            $invoice->loadMissing(['items.batchAllocations.batch', 'warehouse']);

            foreach ($invoice->items as $item) {
                foreach ($item->batchAllocations as $allocation) {
                    if ($allocation->is_reversed) {
                        throw new InvalidArgumentException(
                            "هذا التوزيع للدفعة #{$allocation->inventory_batch_id} تم إلغاؤه مسبقاً لمنع الإلغاء المزدوج."
                        );
                    }

                    $batch = $allocation->batch;
                    $restoredQty = (float) $allocation->quantity_base;
                    $restoredCogs = (float) $allocation->total_cogs;

                    // 1. Restore batch remaining quantity and accumulated COGS
                    $batch->remaining_quantity = round((float) $batch->remaining_quantity + $restoredQty, 4);
                    $batch->accumulated_cogs_deducted = max(0.00, round((float) $batch->accumulated_cogs_deducted - $restoredCogs, 2));
                    $batch->is_exhausted = false;
                    $batch->save();

                    // 2. Restore Warehouse Stock Balance
                    $balance = StockBalance::where('product_id', $item->product_id)
                        ->where('warehouse_id', $invoice->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $balance->increment('quantity', $restoredQty);
                    }

                    $runningBalance = (float) StockBalance::where('product_id', $item->product_id)->sum('quantity');

                    // 3. Compensating Stock Movement (Inward)
                    StockMovement::create([
                        'movement_number' => StockMovement::generateMovementNumber(),
                        'type' => StockMovement::TYPE_IN,
                        'product_id' => $item->product_id,
                        'from_warehouse_id' => null,
                        'to_warehouse_id' => $invoice->warehouse_id,
                        'batch_id' => $batch->id,
                        'quantity' => $restoredQty,
                        'running_balance' => $runningBalance,
                        'unit_cost' => (float) $allocation->unit_cost_base,
                        'reference_type' => 'sales_invoice_void',
                        'reference_id' => $invoice->id,
                        'user_id' => $userId,
                        'customer_id' => $invoice->customer_id,
                        'notes' => 'تسوية عكسية لإلغاء فاتورة البيع رقم: '.$invoice->invoice_number,
                    ]);

                    // 4. Compensating Batch Cost Movement Ledger Entry
                    $runningCostBalance = max(0.00, round((float) $batch->total_initial_cost - (float) $batch->accumulated_cogs_deducted, 2));

                    BatchCostMovement::create([
                        'batch_id' => $batch->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'movement_type' => 'sale_cancellation_reversal',
                        'reference_type' => 'sales_invoice',
                        'reference_id' => $invoice->id,
                        'quantity_base' => $restoredQty,
                        'unit_cost_base' => (float) $allocation->unit_cost_base,
                        'total_cost' => $restoredCogs,
                        'running_batch_quantity' => $batch->remaining_quantity,
                        'running_batch_cost_balance' => $runningCostBalance,
                        'user_id' => $userId,
                        'notes' => 'إلغاء فاتورة بيع: '.$invoice->invoice_number.' | '.$reason,
                    ]);

                    // 5. Mark allocation as reversed with guard
                    $allocation->update([
                        'is_reversed' => true,
                        'reversed_at' => now(),
                    ]);
                }
            }

            // Cancel sales rep commission
            app(CommissionService::class)->cancelInvoiceCommission($invoice);

            // Mark invoice as VOIDED
            $invoice->update([
                'status' => SalesInvoice::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);
        });
    }

    /**
     * Unpost a posted sales invoice back to draft mode.
     * Reverses stock allocations and cashier shift receipts so the invoice can be edited in the table and re-saved.
     */
    public function unpostSalesInvoice(SalesInvoice $invoice, ?int $userId = null, ?string $reason = null): void
    {
        if ($invoice->status !== SalesInvoice::STATUS_POSTED) {
            throw new InvalidArgumentException('يمكن فقط فك ترحيل الفواتير التي في حالة مرحلة (posted).');
        }

        $userId = $userId ?? auth()->id();

        DB::transaction(function () use ($invoice, $userId, $reason) {
            $invoice->loadMissing(['items.batchAllocations.batch', 'warehouse']);

            foreach ($invoice->items as $item) {
                foreach ($item->batchAllocations as $allocation) {
                    if ($allocation->is_reversed) {
                        continue;
                    }

                    $batch = $allocation->batch;
                    $restoredQty = (float) $allocation->quantity_base;
                    $restoredCogs = (float) $allocation->total_cogs;

                    // 1. Restore batch remaining quantity and accumulated COGS
                    $batch->remaining_quantity = round((float) $batch->remaining_quantity + $restoredQty, 4);
                    $batch->accumulated_cogs_deducted = max(0.00, round((float) $batch->accumulated_cogs_deducted - $restoredCogs, 2));
                    $batch->is_exhausted = false;
                    $batch->save();

                    // 2. Restore Warehouse Stock Balance
                    $balance = StockBalance::where('product_id', $item->product_id)
                        ->where('warehouse_id', $invoice->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $balance->increment('quantity', $restoredQty);
                    }

                    $runningBalance = (float) StockBalance::where('product_id', $item->product_id)->sum('quantity');

                    // 3. Compensating Stock Movement (Inward)
                    StockMovement::create([
                        'movement_number' => StockMovement::generateMovementNumber(),
                        'type' => StockMovement::TYPE_IN,
                        'product_id' => $item->product_id,
                        'from_warehouse_id' => null,
                        'to_warehouse_id' => $invoice->warehouse_id,
                        'batch_id' => $batch->id,
                        'quantity' => $restoredQty,
                        'running_balance' => $runningBalance,
                        'unit_cost' => (float) $allocation->unit_cost_base,
                        'reference_type' => 'sales_invoice_unpost',
                        'reference_id' => $invoice->id,
                        'user_id' => $userId,
                        'customer_id' => $invoice->customer_id,
                        'notes' => 'تسوية فك ترحيل للتعديل للفاتورة: '.$invoice->invoice_number,
                    ]);

                    // 4. Compensating Batch Cost Movement Ledger Entry
                    $runningCostBalance = max(0.00, round((float) $batch->total_initial_cost - (float) $batch->accumulated_cogs_deducted, 2));

                    BatchCostMovement::create([
                        'batch_id' => $batch->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $invoice->warehouse_id,
                        'movement_type' => 'sale_cancellation_reversal',
                        'reference_type' => 'sales_invoice',
                        'reference_id' => $invoice->id,
                        'quantity_base' => $restoredQty,
                        'unit_cost_base' => (float) $allocation->unit_cost_base,
                        'total_cost' => $restoredCogs,
                        'running_batch_quantity' => $batch->remaining_quantity,
                        'running_batch_cost_balance' => $runningCostBalance,
                        'user_id' => $userId,
                        'notes' => 'فك ترحيل فاتورة البيع للتعديل: '.$invoice->invoice_number.($reason ? ' | '.$reason : ''),
                    ]);

                    // 5. Delete allocation so clean slate for re-posting
                    $allocation->delete();
                }

                $item->update([
                    'total_cogs' => 0.00,
                    'total_profit' => 0.00,
                ]);
            }

            // 6. Reverse cashier shift cash receipt if shift is open
            if ($invoice->cashier_shift_id && (float) $invoice->paid_amount > 0) {
                $shift = CashierShift::find($invoice->cashier_shift_id);
                if ($shift && $shift->status === CashierShift::STATUS_OPEN) {
                    $shift->recordCashReturn((float) $invoice->paid_amount, $invoice->id);
                }
            }

            // 6.1 Revert customer credit balance if remaining_amount was posted
            if ((float) $invoice->remaining_amount > 0 && $invoice->customer_id) {
                $customer = Customer::lockForUpdate()->find($invoice->customer_id);
                if ($customer) {
                    $customer->decrement('current_balance', (float) $invoice->remaining_amount);
                }
            }

            // 6.2 Cancel sales rep commission
            app(CommissionService::class)->cancelInvoiceCommission($invoice);

            // 6.3 Reverse general ledger journal entry linked to this invoice
            app(AccountingService::class)->voidEntry(
                JournalEntry::REF_SALES_INVOICE,
                $invoice->id,
                'فك ترحيل فاتورة البيع للتعديل: '.$invoice->invoice_number.($reason ? ' | '.$reason : ''),
                $userId
            );

            // 7. Mark invoice as DRAFT
            $invoice->update([
                'status' => SalesInvoice::STATUS_DRAFT,
                'posted_at' => null,
                'posted_by' => null,
                'total_cogs' => 0.00,
                'total_profit' => 0.00,
            ]);
        });
    }

    /**
     * Reconcile warehouse stock balance with batch remaining quantities.
     * Generates a detailed audit of matching vs discrepant products.
     *
     * @return array{
     *   warehouse_id: int,
     *   total_products_checked: int,
     *   discrepancies_count: int,
     *   discrepancies: list<array<string, mixed>>
     * }
     */
    public function reconcileWarehouseInventory(int $warehouseId, ?int $productId = null): array
    {
        $query = StockBalance::where('warehouse_id', $warehouseId);
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $balances = $query->with('product')->get();
        $discrepancies = [];

        foreach ($balances as $balance) {
            $stockQty = (float) $balance->quantity;
            $batchQty = (float) InventoryBatch::where('product_id', $balance->product_id)
                ->where('warehouse_id', $warehouseId)
                ->sum('remaining_quantity');

            $diff = round($stockQty - $batchQty, 4);

            if (abs($diff) > 0.0001) {
                $discrepancies[] = [
                    'product_id' => $balance->product_id,
                    'product_name' => $balance->product?->name ?? 'غير معروف',
                    'stock_balance_qty' => $stockQty,
                    'batch_remaining_sum' => $batchQty,
                    'difference' => $diff,
                ];
            }
        }

        return [
            'warehouse_id' => $warehouseId,
            'total_products_checked' => $balances->count(),
            'discrepancies_count' => count($discrepancies),
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Recalculate and update the running balance of all stock movements for a product chronologically.
     */
    public function recalculateProductRunningBalances(int $productId): void
    {
        $movements = StockMovement::where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $running = 0.00;
        foreach ($movements as $m) {
            if ($m->type === StockMovement::TYPE_IN) {
                $running += (float) $m->quantity;
            } elseif ($m->type === StockMovement::TYPE_OUT) {
                $running -= (float) $m->quantity;
            }
            $m->running_balance = round($running, 4);
            $m->save();
        }
    }
}
