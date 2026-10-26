<?php

use App\Models\Account;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfills legacy opening batches that were saved in package counts instead of base piece units,
     * corrects their cost prices, recalculates running balances, and fixes COGS for related sales.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $affectedProductIds = [38, 219, 283, 284, 286, 287];

            foreach ($affectedProductIds as $productId) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                $factor = (float) ($product->unit_factor > 0 ? $product->unit_factor : 12.0);
                $expectedBaseQty = round((float) $product->opening_quantity * $factor, 4);
                $expectedBaseCost = round((float) $product->opening_cost / $factor, 4);

                // 1. Fix the opening InventoryBatch
                $batch = InventoryBatch::where('product_id', $productId)
                    ->where('source_type', 'opening_balance')
                    ->first();

                if ($batch) {
                    $consumed = (float) $batch->initial_quantity - (float) $batch->remaining_quantity;
                    $batch->initial_quantity = $expectedBaseQty;
                    $batch->remaining_quantity = max(0, round($expectedBaseQty - $consumed, 4));
                    $batch->cost_price = $expectedBaseCost;
                    $batch->total_initial_cost = round($expectedBaseQty * $expectedBaseCost, 2);
                    $batch->accumulated_cogs_deducted = round($consumed * $expectedBaseCost, 2);
                    $batch->save();
                }

                // 2. Fix the opening StockMovement
                $openingMovement = StockMovement::where('product_id', $productId)
                    ->where('type', 'in')
                    ->where('reference_type', 'opening_balance')
                    ->first();

                if ($openingMovement) {
                    $openingMovement->quantity = $expectedBaseQty;
                    $openingMovement->running_balance = $expectedBaseQty;
                    $openingMovement->unit_cost = $expectedBaseCost;
                    $openingMovement->save();
                }

                // 3. Recalculate subsequent movements and running balances
                $movements = StockMovement::where('product_id', $productId)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $running = 0.0;
                foreach ($movements as $m) {
                    if ($m->type === 'in') {
                        $running += (float) $m->quantity;
                    } elseif ($m->type === 'out') {
                        $running -= (float) $m->quantity;
                        // If this was invoice 38 for product 219, fix unit_cost
                        if ($productId === 219 && $m->reference_type === 'sales_invoice' && (int) $m->reference_id === 38) {
                            $m->unit_cost = $expectedBaseCost;
                        }
                    }
                    $m->running_balance = $running;
                    $m->save();
                }

                // 4. Fix StockBalance
                $warehouseId = $batch?->warehouse_id ?? 1;
                $balance = StockBalance::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                if ($balance) {
                    $balance->quantity = $running;
                    $balance->save();
                }
            }

            // 5. Fix SalesInvoiceItem for Product 219 on SalesInvoice 38
            $invoiceItem = SalesInvoiceItem::where('sales_invoice_id', 38)
                ->where('product_id', 219)
                ->first();

            if ($invoiceItem) {
                $baseCost = 21.50; // 258 / 12
                $newCogs = round(3.0 * $baseCost, 2); // 64.50
                $newProfit = round((float) $invoiceItem->subtotal - $newCogs, 2); // 72 - 64.50 = 7.50

                $oldCogs = (float) $invoiceItem->total_cogs; // 774.00
                $cogsDifference = $oldCogs - $newCogs; // 709.50

                $invoiceItem->total_cogs = $newCogs;
                $invoiceItem->total_profit = $newProfit;
                $invoiceItem->save();

                // 6. Fix SalesInvoice #38 totals
                $invoice = SalesInvoice::find(38);
                if ($invoice) {
                    $invoice->total_cogs = max(0, round((float) $invoice->total_cogs - $cogsDifference, 2)); // 934.50 - 709.50 = 225.00
                    $invoice->total_profit = round((float) $invoice->total_amount - (float) $invoice->total_cogs, 2); // 267 - 225 = 42.00
                    $invoice->save();

                    // 7. Fix Accounting Journal Entry #28 for Invoice #38 if it exists
                    $entry = JournalEntry::where('reference_type', 'sales_invoice')
                        ->where('reference_id', 38)
                        ->first();

                    if ($entry) {
                        // Line for Cost of Goods Sold (Account 41)
                        $cogsLine = JournalEntryLine::where('journal_entry_id', $entry->id)
                            ->where('account_id', 41)
                            ->first();
                        if ($cogsLine) {
                            $cogsLine->debit = $invoice->total_cogs;
                            $cogsLine->save();
                        }

                        // Line for Inventory (Account 12)
                        $inventoryLine = JournalEntryLine::where('journal_entry_id', $entry->id)
                            ->where('account_id', 12)
                            ->first();
                        if ($inventoryLine) {
                            $inventoryLine->credit = $invoice->total_cogs;
                            $inventoryLine->save();
                        }

                        // Update Journal Entry Totals
                        $totalDebit = JournalEntryLine::where('journal_entry_id', $entry->id)->sum('debit');
                        $totalCredit = JournalEntryLine::where('journal_entry_id', $entry->id)->sum('credit');
                        $entry->total_debit = $totalDebit;
                        $entry->total_credit = $totalCredit;
                        $entry->save();

                        // Recalculate balances for Account 41 and Account 12
                        foreach ([12, 41] as $accId) {
                            $acc = Account::find($accId);
                            if ($acc) {
                                $totalDebits = JournalEntryLine::where('account_id', $accId)->sum('debit');
                                $totalCredits = JournalEntryLine::where('account_id', $accId)->sum('credit');
                                if ($acc->type === 'debit') {
                                    $acc->current_balance = $totalDebits - $totalCredits;
                                } else {
                                    $acc->current_balance = $totalCredits - $totalDebits;
                                }
                                $acc->save();
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
