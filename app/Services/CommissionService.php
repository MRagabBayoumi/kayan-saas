<?php

namespace App\Services;

use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Calculate and record commission for a sales invoice upon posting.
     */
    public function recordInvoiceCommission(SalesInvoice $invoice): ?SalesCommission
    {
        return DB::transaction(function () use ($invoice) {
            // Determine sales representative: from invoice or from assigned customer
            $salesRepId = $invoice->sales_rep_id;
            if (! $salesRepId && $invoice->customer_id) {
                $salesRepId = $invoice->customer?->sales_rep_id;
            }

            if (! $salesRepId) {
                return null;
            }

            $salesRep = User::find($salesRepId);
            if (! $salesRep) {
                return null;
            }

            $rate = (float) $salesRep->commission_rate;
            $totalAmount = (float) $invoice->total_amount;
            $amount = 0.00;

            if ($rate > 0) {
                if ($salesRep->commission_type === 'fixed') {
                    $amount = round($rate, 2);
                } else {
                    $amount = round(($totalAmount * $rate) / 100, 2);
                }
            }

            // Sync invoice columns
            $invoice->updateQuietly([
                'sales_rep_id' => $salesRep->id,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
            ]);

            // Create or update commission record
            return SalesCommission::updateOrCreate(
                ['sales_invoice_id' => $invoice->id],
                [
                    'sales_rep_id' => $salesRep->id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_total' => $totalAmount,
                    'commission_rate' => $rate,
                    'commission_amount' => $amount,
                    'status' => SalesCommission::STATUS_PENDING,
                    'notes' => 'عمولة مبيعات تلقائية للفاتورة: '.$invoice->invoice_number,
                ]
            );
        });
    }

    /**
     * Cancel commission when an invoice is unposted or voided.
     */
    public function cancelInvoiceCommission(SalesInvoice $invoice): void
    {
        $commission = SalesCommission::where('sales_invoice_id', $invoice->id)->first();
        if ($commission) {
            $commission->update([
                'status' => SalesCommission::STATUS_CANCELED,
                'notes' => $commission->notes.' | تم الإلغاء لإلغاء أو فك ترحيل الفاتورة',
            ]);
        }

        $invoice->updateQuietly([
            'commission_amount' => 0.00,
        ]);
    }
}
