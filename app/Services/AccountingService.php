<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SupplierPayment;
use App\Models\Treasury;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Create and post a balanced double-entry journal entry atomically.
     *
     * @param  array  $headerData  [
     *                             'entry_date' => string|Carbon,
     *                             'reference_type' => string,
     *                             'reference_id' => int,
     *                             'description' => string,
     *                             'created_by' => ?int,
     *                             ]
     * @param  array  $linesData  Array of line items:
     *                            [
     *                            'account_id' => int,
     *                            'debit' => float,
     *                            'credit' => float,
     *                            'description' => ?string,
     *                            'party_type' => ?string,
     *                            'party_id' => ?int,
     *                            ]
     *
     * @throws \DomainException
     */
    public function createEntry(array $headerData, array $linesData): JournalEntry
    {
        $this->ensureStandardAccountsExist();

        // Filter out zero-amount lines
        $validLines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($linesData as $line) {
            $debit = round((float) ($line['debit'] ?? 0.0), 2);
            $credit = round((float) ($line['credit'] ?? 0.0), 2);

            if ($debit <= 0.0 && $credit <= 0.0) {
                continue;
            }

            if ($debit > 0.0 && $credit > 0.0) {
                // If both are present, net them out
                if ($debit >= $credit) {
                    $debit = round($debit - $credit, 2);
                    $credit = 0.0;
                } else {
                    $credit = round($credit - $debit, 2);
                    $debit = 0.0;
                }
            }

            $line['debit'] = $debit;
            $line['credit'] = $credit;
            $validLines[] = $line;

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if (empty($validLines) || ($totalDebit <= 0.0 && $totalCredit <= 0.0)) {
            throw new \DomainException('لا يمكن إنشاء قيد يومية بدون مبالغ أو سطور صالحة.');
        }

        // Strict balance check (difference < 0.01)
        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw new \DomainException("قيد اليومية غير متزن محاسبياً: إجمالي المدين ({$totalDebit}) لا يساوي إجمالي الدائن ({$totalCredit}). الفارق: ".round($totalDebit - $totalCredit, 2));
        }

        return DB::transaction(function () use ($headerData, $validLines, $totalDebit, $totalCredit) {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => $headerData['entry_date'] ?? now()->toDateString(),
                'reference_type' => $headerData['reference_type'] ?? JournalEntry::REF_MANUAL,
                'reference_id' => $headerData['reference_id'] ?? null,
                'description' => $headerData['description'] ?? 'قيد محاسبي آلي',
                'status' => JournalEntry::STATUS_POSTED,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => $headerData['created_by'] ?? auth()->id(),
                'posted_at' => now(),
            ]);

            $order = 1;
            $touchedAccountIds = [];

            foreach ($validLines as $line) {
                $accountId = (int) $line['account_id'];
                $touchedAccountIds[] = $accountId;

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accountId,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'] ?? $entry->description,
                    'party_type' => $line['party_type'] ?? null,
                    'party_id' => $line['party_id'] ?? null,
                    'line_order' => $order++,
                ]);
            }

            // Recalculate balances for all affected accounts and propagate up their hierarchy
            $this->recalculateAccountBalances(array_unique($touchedAccountIds));

            return $entry;
        });
    }

    /**
     * Record sales invoice journal entry.
     */
    public function recordSalesInvoice(SalesInvoice $invoice): ?JournalEntry
    {
        // Prevent duplicate entries
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_SALES_INVOICE)
            ->where('reference_id', $invoice->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $paidAmount = round((float) $invoice->paid_amount, 2);
        $remainingAmount = round((float) $invoice->remaining_amount, 2);
        $discountAmount = round((float) $invoice->discount_amount, 2);
        $totalCogs = round((float) $invoice->total_cogs, 2);

        // Safeguard: Fallback if amounts are zero on a non-zero invoice
        if ($paidAmount <= 0.0 && $remainingAmount <= 0.0 && (float) $invoice->total_amount > 0.0) {
            if ($invoice->payment_type === SalesInvoice::PAYMENT_CASH) {
                $paidAmount = round((float) $invoice->total_amount, 2);
            } else {
                $remainingAmount = round((float) $invoice->total_amount, 2);
            }
        }

        // Credit to Sales Revenue = (paid + remaining) + discount = total_amount + discount_amount
        $grossSalesAmount = round($paidAmount + $remainingAmount + $discountAmount, 2);

        if ($grossSalesAmount <= 0.0 && $totalCogs <= 0.0) {
            return null;
        }

        // Resolve accounts
        $cashAccountId = $this->resolveTreasuryAccount();
        $arAccountId = $this->resolveCustomerAccount($invoice->customer_id);
        $discountAccountId = $this->resolveAccountByRole('sales_discount', '410301');
        $revenueAccountId = match ($invoice->price_tier) {
            'wholesale' => $this->resolveAccountByRole('sales_wholesale', '410102'),
            'distributor' => $this->resolveAccountByRole('sales_distributor', '410103'),
            default => $this->resolveAccountByRole('sales_retail', '410101'),
        };
        $cogsAccountId = $this->resolveAccountByRole('cogs', '510101');
        $inventoryAccountId = $this->resolveAccountByRole('inventory_merchandise', '110301');

        $lines = [];

        // 1. Dr. Cash Safe (if partially or fully paid)
        if ($paidAmount > 0.0) {
            $lines[] = [
                'account_id' => $cashAccountId,
                'debit' => $paidAmount,
                'credit' => 0.0,
                'description' => 'سداد نقدي مع فاتورة مبيعات رقم '.$invoice->invoice_number,
                'party_type' => 'customer',
                'party_id' => $invoice->customer_id,
            ];
        }

        // 2. Dr. Accounts Receivable (if debt / remaining exists)
        if ($remainingAmount > 0.0) {
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $remainingAmount,
                'credit' => 0.0,
                'description' => 'متبقي آجل على العميل لفاتورة مبيعات '.$invoice->invoice_number,
                'party_type' => 'customer',
                'party_id' => $invoice->customer_id,
            ];
        }

        // 3. Dr. Sales Discount (if discount applied)
        if ($discountAmount > 0.0) {
            $lines[] = [
                'account_id' => $discountAccountId,
                'debit' => $discountAmount,
                'credit' => 0.0,
                'description' => 'خصم مسموح به على فاتورة مبيعات '.$invoice->invoice_number,
            ];
        }

        // 4. Cr. Sales Revenue (Gross Sales)
        if ($grossSalesAmount > 0.0) {
            $lines[] = [
                'account_id' => $revenueAccountId,
                'debit' => 0.0,
                'credit' => $grossSalesAmount,
                'description' => 'إيراد مبيعات فاتورة '.$invoice->invoice_number.' ('.($invoice->customer?->name ?? 'عميل').')',
            ];
        }

        // 5. Perpetual Inventory & FIFO COGS entries
        if ($totalCogs > 0.0) {
            // Dr. Cost of Goods Sold
            $lines[] = [
                'account_id' => $cogsAccountId,
                'debit' => $totalCogs,
                'credit' => 0.0,
                'description' => 'تكلفة البضاعة المباعة (FIFO) لفاتورة '.$invoice->invoice_number,
            ];

            // Cr. Inventory (Warehoused Merchandise)
            $lines[] = [
                'account_id' => $inventoryAccountId,
                'debit' => 0.0,
                'credit' => $totalCogs,
                'description' => 'صرف بضاعة من المخزن لفاتورة '.$invoice->invoice_number,
            ];
        }

        return $this->createEntry([
            'entry_date' => $invoice->invoice_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_SALES_INVOICE,
            'reference_id' => $invoice->id,
            'description' => 'قيد فاتورة مبيعات رقم '.$invoice->invoice_number.' - العميل: '.($invoice->customer?->name ?? 'نقدي'),
            'created_by' => $invoice->posted_by ?? $invoice->created_by,
        ], $lines);
    }

    /**
     * Record sales return journal entry.
     */
    public function recordSalesReturn(SalesReturn $return): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_SALES_RETURN)
            ->where('reference_id', $return->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalAmount = round((float) $return->total_amount, 2);
        $totalCogsReturned = round((float) $return->total_cogs_returned, 2);

        if ($totalAmount <= 0.0 && $totalCogsReturned <= 0.0) {
            return null;
        }

        $salesReturnAccountId = $this->resolveAccountByRole('sales_returns', '410201');
        $arAccountId = $this->resolveCustomerAccount($return->customer_id);
        $inventoryAccountId = $this->resolveAccountByRole('inventory_merchandise', '110301');
        $cogsAccountId = $this->resolveAccountByRole('cogs', '510101');

        $lines = [];

        // 1. Dr. Sales Returns & Allowances
        if ($totalAmount > 0.0) {
            $lines[] = [
                'account_id' => $salesReturnAccountId,
                'debit' => $totalAmount,
                'credit' => 0.0,
                'description' => 'مردودات مبيعات إشعار دائن رقم '.$return->return_number,
            ];

            // 2. Cr. Customer Receivables
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => 0.0,
                'credit' => $totalAmount,
                'description' => 'تخفيض حساب العميل بمردود مبيعات '.$return->return_number,
                'party_type' => 'customer',
                'party_id' => $return->customer_id,
            ];
        }

        // 3. Inventory & COGS restoration
        if ($totalCogsReturned > 0.0) {
            // Dr. Inventory
            $lines[] = [
                'account_id' => $inventoryAccountId,
                'debit' => $totalCogsReturned,
                'credit' => 0.0,
                'description' => 'إعادة تكلفة البضاعة للمخزن لمردود '.$return->return_number,
            ];

            // Cr. Cost of Goods Sold
            $lines[] = [
                'account_id' => $cogsAccountId,
                'debit' => 0.0,
                'credit' => $totalCogsReturned,
                'description' => 'تخفيض تكلفة البضاعة المباعة بمردود '.$return->return_number,
            ];
        }

        return $this->createEntry([
            'entry_date' => $return->return_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_SALES_RETURN,
            'reference_id' => $return->id,
            'description' => 'قيد مردودات مبيعات رقم '.$return->return_number.' - العميل: '.($return->customer?->name ?? 'عميل'),
            'created_by' => $return->created_by,
        ], $lines);
    }

    /**
     * Record purchase invoice journal entry.
     */
    public function recordPurchaseInvoice(PurchaseInvoice $invoice): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_PURCHASE_INVOICE)
            ->where('reference_id', $invoice->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalAmount = round((float) $invoice->total_amount, 2);
        $paidAmount = round((float) $invoice->paid_amount, 2);
        $remainingAmount = round((float) $invoice->remaining_amount, 2);

        if ($totalAmount <= 0.0) {
            return null;
        }

        $inventoryAccountId = $this->resolveAccountByRole('inventory_merchandise', '110301');
        $apAccountId = $this->resolveSupplierAccount($invoice->supplier_id);
        $cashAccountId = $this->resolveTreasuryAccount();

        $lines = [];

        // 1. Dr. Inventory for Total Invoice Amount
        $lines[] = [
            'account_id' => $inventoryAccountId,
            'debit' => $totalAmount,
            'credit' => 0.0,
            'description' => 'إضافة بضاعة مشتريات للمخزن بفاتورة '.$invoice->invoice_number,
        ];

        // 2. Cr. Accounts Payable (remaining balance)
        if ($remainingAmount > 0.0) {
            $lines[] = [
                'account_id' => $apAccountId,
                'debit' => 0.0,
                'credit' => $remainingAmount,
                'description' => 'مستحقات للمورد عن فاتورة مشتريات '.$invoice->invoice_number,
                'party_type' => 'supplier',
                'party_id' => $invoice->supplier_id,
            ];
        }

        // 3. Cr. Cash Safe (paid amount)
        if ($paidAmount > 0.0) {
            $lines[] = [
                'account_id' => $cashAccountId,
                'debit' => 0.0,
                'credit' => $paidAmount,
                'description' => 'سداد نقدي من الخزينة لفاتورة مشتريات '.$invoice->invoice_number,
                'party_type' => 'supplier',
                'party_id' => $invoice->supplier_id,
            ];
        }

        return $this->createEntry([
            'entry_date' => $invoice->invoice_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_PURCHASE_INVOICE,
            'reference_id' => $invoice->id,
            'description' => 'قيد فاتورة مشتريات واردة رقم '.$invoice->invoice_number.' - المورد: '.($invoice->supplier?->name ?? 'مورد'),
            'created_by' => $invoice->created_by,
        ], $lines);
    }

    /**
     * Record purchase return journal entry.
     */
    public function recordPurchaseReturn(PurchaseReturn $return): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_PURCHASE_RETURN)
            ->where('reference_id', $return->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $totalAmount = round((float) $return->total_amount, 2);
        if ($totalAmount <= 0.0) {
            return null;
        }

        $inventoryAccountId = $this->resolveAccountByRole('inventory_merchandise', '110301');
        $apAccountId = $this->resolveSupplierAccount($return->supplier_id);
        $cashAccountId = $this->resolveTreasuryAccount($return->treasury_id);

        $lines = [];

        // 1. Dr. Accounts Payable or Cash
        if ($return->settlement_type === 'cash') {
            $lines[] = [
                'account_id' => $cashAccountId,
                'debit' => $totalAmount,
                'credit' => 0.0,
                'description' => 'استرداد نقدي بالخزينة عن مردود مشتريات '.$return->return_number,
            ];
        } else {
            $lines[] = [
                'account_id' => $apAccountId,
                'debit' => $totalAmount,
                'credit' => 0.0,
                'description' => 'تخفيض مستحقات المورد بمردود مشتريات '.$return->return_number,
                'party_type' => 'supplier',
                'party_id' => $return->supplier_id,
            ];
        }

        // 2. Cr. Inventory
        $lines[] = [
            'account_id' => $inventoryAccountId,
            'debit' => 0.0,
            'credit' => $totalAmount,
            'description' => 'صرف بضاعة مرتجعة للمورد بإشعار مدين '.$return->return_number,
        ];

        return $this->createEntry([
            'entry_date' => $return->return_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_PURCHASE_RETURN,
            'reference_id' => $return->id,
            'description' => 'قيد مردودات مشتريات رقم '.$return->return_number.' - المورد: '.($return->supplier?->name ?? 'مورد'),
            'created_by' => $return->created_by,
        ], $lines);
    }

    /**
     * Record customer payment / receipt voucher journal entry.
     */
    public function recordCustomerPayment(CustomerPayment $payment): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_CUSTOMER_PAYMENT)
            ->where('reference_id', $payment->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0.0) {
            return null;
        }

        $cashAccountId = $this->resolveTreasuryAccount();
        $arAccountId = $this->resolveCustomerAccount($payment->customer_id);

        $lines = [
            [
                'account_id' => $cashAccountId,
                'debit' => $amount,
                'credit' => 0.0,
                'description' => 'سند قبض نقدية من العميل '.($payment->customer?->name ?? '').' رقم '.$payment->payment_number,
                'party_type' => 'customer',
                'party_id' => $payment->customer_id,
            ],
            [
                'account_id' => $arAccountId,
                'debit' => 0.0,
                'credit' => $amount,
                'description' => 'تحصيل وتخفيض مديونية العميل بسند قبض '.$payment->payment_number,
                'party_type' => 'customer',
                'party_id' => $payment->customer_id,
            ],
        ];

        return $this->createEntry([
            'entry_date' => $payment->payment_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_CUSTOMER_PAYMENT,
            'reference_id' => $payment->id,
            'description' => 'قيد سند قبض نقدية رقم '.$payment->payment_number.' - العميل: '.($payment->customer?->name ?? 'عميل'),
            'created_by' => $payment->created_by,
        ], $lines);
    }

    /**
     * Record supplier payment / disbursement voucher journal entry.
     */
    public function recordSupplierPayment(SupplierPayment $payment): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_SUPPLIER_PAYMENT)
            ->where('reference_id', $payment->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0.0) {
            return null;
        }

        $apAccountId = $this->resolveSupplierAccount($payment->supplier_id);
        $cashAccountId = $this->resolveTreasuryAccount($payment->treasury_id);

        $lines = [
            [
                'account_id' => $apAccountId,
                'debit' => $amount,
                'credit' => 0.0,
                'description' => 'سداد دفعة للمورد '.($payment->supplier?->name ?? '').' بسند صرف '.$payment->payment_number,
                'party_type' => 'supplier',
                'party_id' => $payment->supplier_id,
            ],
            [
                'account_id' => $cashAccountId,
                'debit' => 0.0,
                'credit' => $amount,
                'description' => 'صرف نقدي من الخزينة بسند صرف مورد '.$payment->payment_number,
                'party_type' => 'supplier',
                'party_id' => $payment->supplier_id,
            ],
        ];

        return $this->createEntry([
            'entry_date' => $payment->payment_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_SUPPLIER_PAYMENT,
            'reference_id' => $payment->id,
            'description' => 'قيد سند صرف مورد رقم '.$payment->payment_number.' - المورد: '.($payment->supplier?->name ?? 'مورد'),
            'created_by' => $payment->created_by,
        ], $lines);
    }

    /**
     * Record operating expense voucher journal entry.
     */
    public function recordExpense(Expense $expense): ?JournalEntry
    {
        $existing = JournalEntry::where('reference_type', JournalEntry::REF_EXPENSE)
            ->where('reference_id', $expense->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = round((float) $expense->amount, 2);
        if ($amount <= 0.0) {
            return null;
        }

        $expenseAccountId = $this->resolveExpenseAccount($expense->expense_category_id);
        $cashAccountId = $this->resolveTreasuryAccount($expense->treasury_id);

        $lines = [
            [
                'account_id' => $expenseAccountId,
                'debit' => $amount,
                'credit' => 0.0,
                'description' => 'مصروف: '.($expense->category?->name ?? 'مصروفات').($expense->paid_to ? ' - صرف لـ: '.$expense->paid_to : ''),
            ],
            [
                'account_id' => $cashAccountId,
                'debit' => 0.0,
                'credit' => $amount,
                'description' => 'صرف من الخزينة بسند مصروف رقم '.$expense->expense_number,
            ],
        ];

        return $this->createEntry([
            'entry_date' => $expense->expense_date ?? now()->toDateString(),
            'reference_type' => JournalEntry::REF_EXPENSE,
            'reference_id' => $expense->id,
            'description' => 'قيد سند صرف مصروفات رقم '.$expense->expense_number.' - '.($expense->category?->name ?? 'مصروف'),
            'created_by' => $expense->created_by,
        ], $lines);
    }

    /**
     * Void or reverse a journal entry linked to a business document.
     */
    public function voidEntry(string $referenceType, int $referenceId, ?string $reason = null, ?int $userId = null): bool
    {
        $entries = JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->get();

        if ($entries->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($entries, $reason) {
            $touchedAccountIds = [];

            foreach ($entries as $entry) {
                $accountIds = $entry->lines()->pluck('account_id')->all();
                $touchedAccountIds = array_merge($touchedAccountIds, $accountIds);

                $entry->update([
                    'status' => JournalEntry::STATUS_REVERSED,
                    'description' => $entry->description.($reason ? ' [تم الإلغاء: '.$reason.']' : ' [ملغى]'),
                ]);
            }

            $this->recalculateAccountBalances(array_unique($touchedAccountIds));
        });

        return true;
    }

    /**
     * Backfill historical transactions that haven't generated journal entries yet.
     */
    public function backfillHistoricalEntries(): array
    {
        $stats = [
            'sales' => 0,
            'sales_returns' => 0,
            'purchases' => 0,
            'purchase_returns' => 0,
            'customer_payments' => 0,
            'supplier_payments' => 0,
            'expenses' => 0,
        ];

        // 1. Posted Sales Invoices
        $invoices = SalesInvoice::where('status', SalesInvoice::STATUS_POSTED)->orderBy('id')->get();
        foreach ($invoices as $inv) {
            try {
                if ($this->recordSalesInvoice($inv)) {
                    $stats['sales']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill sales invoice #{$inv->id} error: ".$e->getMessage());
            }
        }

        // 2. Posted Sales Returns
        $salesReturns = SalesReturn::where('status', 'posted')->orderBy('id')->get();
        foreach ($salesReturns as $ret) {
            try {
                if ($this->recordSalesReturn($ret)) {
                    $stats['sales_returns']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill sales return #{$ret->id} error: ".$e->getMessage());
            }
        }

        // 3. Posted Purchase Invoices
        $purchases = PurchaseInvoice::where('status', PurchaseInvoice::STATUS_POSTED)->orderBy('id')->get();
        foreach ($purchases as $pInv) {
            try {
                if ($this->recordPurchaseInvoice($pInv)) {
                    $stats['purchases']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill purchase invoice #{$pInv->id} error: ".$e->getMessage());
            }
        }

        // 4. Posted Purchase Returns
        $purchaseReturns = PurchaseReturn::where('status', 'posted')->orderBy('id')->get();
        foreach ($purchaseReturns as $pRet) {
            try {
                if ($this->recordPurchaseReturn($pRet)) {
                    $stats['purchase_returns']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill purchase return #{$pRet->id} error: ".$e->getMessage());
            }
        }

        // 5. Customer Payments
        $customerPayments = CustomerPayment::orderBy('id')->get();
        foreach ($customerPayments as $cPay) {
            try {
                if ($this->recordCustomerPayment($cPay)) {
                    $stats['customer_payments']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill customer payment #{$cPay->id} error: ".$e->getMessage());
            }
        }

        // 6. Supplier Payments
        $supplierPayments = SupplierPayment::orderBy('id')->get();
        foreach ($supplierPayments as $sPay) {
            try {
                if ($this->recordSupplierPayment($sPay)) {
                    $stats['supplier_payments']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill supplier payment #{$sPay->id} error: ".$e->getMessage());
            }
        }

        // 7. Expenses
        $expenses = Expense::orderBy('id')->get();
        foreach ($expenses as $exp) {
            try {
                if ($this->recordExpense($exp)) {
                    $stats['expenses']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Backfill expense #{$exp->id} error: ".$e->getMessage());
            }
        }

        // Recalculate all balances in tree
        $this->recalculateAllBalances();

        return $stats;
    }

    /**
     * Recalculate current balances for given accounts and all ancestor branches.
     */
    public function recalculateAccountBalances(array $accountIds): void
    {
        if (empty($accountIds)) {
            return;
        }

        $accounts = Account::whereIn('id', $accountIds)->get();
        $parentIds = [];

        foreach ($accounts as $account) {
            $account->recalculateBalance();
            if ($account->parent_id) {
                $parentIds[] = $account->parent_id;
            }
        }

        // Recalculate parents up the hierarchy
        while (! empty($parentIds)) {
            $parents = Account::whereIn('id', array_unique($parentIds))->get();
            $parentIds = [];

            foreach ($parents as $parent) {
                $parent->recalculateBalance();
                if ($parent->parent_id) {
                    $parentIds[] = $parent->parent_id;
                }
            }
        }
    }

    /**
     * Full recalculation of all account balances in the chart of accounts.
     */
    public function recalculateAllBalances(): void
    {
        // 1. Recalculate all leaf accounts first
        $leaves = Account::leaves()->get();
        foreach ($leaves as $leaf) {
            $leaf->recalculateBalance();
        }

        // 2. Recalculate branches from deepest level (level 3, 2, 1)
        for ($level = 3; $level >= 1; $level--) {
            $branches = Account::where('is_leaf', false)->where('level', $level)->get();
            foreach ($branches as $branch) {
                $branch->recalculateBalance();
            }
        }
    }

    // =========================================================================
    // Account Resolution Helpers
    // =========================================================================

    protected function ensureStandardAccountsExist(): void
    {
        if (Account::count() === 0) {
            (new ChartOfAccountsSeeder)->run();
        }
    }

    protected function resolveTreasuryAccount(?int $treasuryId = null): int
    {
        $this->ensureStandardAccountsExist();

        if ($treasuryId) {
            $treasury = Treasury::find($treasuryId);
            if ($treasury?->account_id) {
                return (int) $treasury->account_id;
            }
        }

        $defaultTreasury = Treasury::first();
        if ($defaultTreasury?->account_id) {
            return (int) $defaultTreasury->account_id;
        }

        return (int) (Account::byRole('cash_main')?->id ?? Account::where('code', '110101')->value('id') ?? Account::value('id') ?? 1);
    }

    protected function resolveCustomerAccount(?int $customerId = null): int
    {
        $this->ensureStandardAccountsExist();

        return (int) (Account::byRole('accounts_receivable')?->id ?? Account::where('code', '110201')->value('id') ?? Account::value('id') ?? 1);
    }

    protected function resolveSupplierAccount(?int $supplierId = null): int
    {
        $this->ensureStandardAccountsExist();

        return (int) (Account::byRole('accounts_payable_local')?->id ?? Account::where('code', '210101')->value('id') ?? Account::value('id') ?? 1);
    }

    protected function resolveExpenseAccount(?int $categoryId = null): int
    {
        $this->ensureStandardAccountsExist();

        if ($categoryId) {
            $category = ExpenseCategory::find($categoryId);
            if ($category?->account_id) {
                return (int) $category->account_id;
            }
        }

        return (int) (Account::byRole('general_expense')?->id ?? Account::where('code', '530106')->value('id') ?? Account::value('id') ?? 1);
    }

    protected function resolveAccountByRole(string $role, string $fallbackCode): int
    {
        $this->ensureStandardAccountsExist();

        $acc = Account::byRole($role);
        if ($acc) {
            return $acc->id;
        }

        $byCode = Account::where('code', $fallbackCode)->first();
        if ($byCode) {
            return $byCode->id;
        }

        return (int) (Account::value('id') ?? 1);
    }
}
