<?php

use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reconciles legacy sales invoices where cash payments were recorded with zero paid_amount,
     * and re-records incomplete or outdated journal entries so the General Ledger
     * perfectly matches the Sales register and Income Statement.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Correct cash sales invoices where paid_amount was recorded as 0
            $cashInvoices = SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)
                ->where('payment_type', SalesInvoice::PAYMENT_CASH)
                ->where('paid_amount', '<=', 0)
                ->where('remaining_amount', '<=', 0)
                ->where('total_amount', '>', 0)
                ->get();

            foreach ($cashInvoices as $inv) {
                $inv->update([
                    'paid_amount' => $inv->total_amount,
                    'remaining_amount' => 0.00,
                ]);
            }

            // 2. Re-record any posted sales invoices whose journal entries are missing or have mismatched totals
            $accountingService = app(AccountingService::class);
            $postedInvoices = SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)->get();

            foreach ($postedInvoices as $inv) {
                $jes = JournalEntry::where('reference_type', JournalEntry::REF_SALES_INVOICE)
                    ->where('reference_id', $inv->id)
                    ->get();

                $revenueCredits = 0.0;
                $cogsDebits = 0.0;
                foreach ($jes as $je) {
                    if ($je->status === JournalEntry::STATUS_POSTED) {
                        $revenueCredits += (float) $je->lines()
                            ->whereHas('account', fn ($q) => $q->where('type', 'revenue')->where('code', '!=', '4103'))
                            ->sum('credit');
                        $cogsDebits += (float) $je->lines()
                            ->whereHas('account', fn ($q) => $q->where('code', '510101'))
                            ->sum('debit');
                    }
                }

                $expectedRevenue = round((float) $inv->total_amount + (float) $inv->discount_amount, 2);
                $expectedCogs = round((float) $inv->total_cogs, 2);

                if (abs($revenueCredits - $expectedRevenue) > 0.05 || abs($cogsDebits - $expectedCogs) > 0.05) {
                    foreach ($jes as $je) {
                        $je->lines()->delete();
                        $je->delete();
                    }
                    $accountingService->recordSalesInvoice($inv->fresh());
                }
            }

            // 3. Recalculate all account balances in the chart of accounts
            $accountingService->recalculateAllBalances();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for data integrity reconciliation.
    }
};
