<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatementService
{
    /**
     * Build customer ledger account statement with running balance.
     */
    public function getCustomerStatement(Customer $customer, ?string $fromDate = null, ?string $toDate = null): array
    {
        $openingBalance = (float) ($customer->opening_balance ?? 0.00);

        // Fetch all posted sales invoices
        $invoicesQuery = SalesInvoice::query()
            ->where('customer_id', $customer->id)
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->with(['warehouse', 'payments']);

        // Fetch all posted sales returns
        $returnsQuery = SalesReturn::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'posted')
            ->with(['warehouse']);

        // Fetch all customer payments (receipt vouchers / installments)
        $paymentsQuery = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->with(['salesInvoice.warehouse', 'creator']);

        // Fallback for customers where opening_balance was not stored but current_balance was set initially
        if ($openingBalance == 0.00 && (float) ($customer->current_balance ?? 0.00) != 0.00) {
            $hasInvoices = (clone $invoicesQuery)->exists();
            $hasReturns = (clone $returnsQuery)->exists();
            $hasPayments = (clone $paymentsQuery)->exists();
            if (! $hasInvoices && ! $hasReturns && ! $hasPayments) {
                $openingBalance = (float) $customer->current_balance;
            }
        }

        // Compute pre-period balance if fromDate is provided
        $prePeriodBalance = $openingBalance;
        if ($fromDate) {
            $from = Carbon::parse($fromDate)->startOfDay();

            $priorInvoices = (clone $invoicesQuery)->where('invoice_date', '<', $from)->get();
            foreach ($priorInvoices as $inv) {
                $initialDownpayment = 0.00;
                if ($inv->payment_type === SalesInvoice::PAYMENT_CASH) {
                    $initialDownpayment = (float) $inv->total_amount;
                } elseif ($inv->payment_type === SalesInvoice::PAYMENT_PARTIAL) {
                    $subsequentPaymentsSum = (float) $inv->payments->where('payment_date', '<', $from)->sum('amount');
                    $initialDownpayment = max(0.00, (float) $inv->paid_amount - $subsequentPaymentsSum);
                }
                $prePeriodBalance += ((float) $inv->total_amount - $initialDownpayment);
            }

            $priorReturns = (clone $returnsQuery)->where('return_date', '<', $from)->get();
            foreach ($priorReturns as $ret) {
                $prePeriodBalance -= (float) $ret->total_amount;
            }

            $priorPayments = (clone $paymentsQuery)->where('payment_date', '<', $from)->get();
            foreach ($priorPayments as $pay) {
                $prePeriodBalance -= (float) $pay->amount;
            }
        }

        // Apply date filters to the transactions within the period
        if ($fromDate) {
            $invoicesQuery->where('invoice_date', '>=', Carbon::parse($fromDate)->startOfDay());
            $returnsQuery->where('return_date', '>=', Carbon::parse($fromDate)->startOfDay());
            $paymentsQuery->where('payment_date', '>=', Carbon::parse($fromDate)->startOfDay());
        }
        if ($toDate) {
            $invoicesQuery->where('invoice_date', '<=', Carbon::parse($toDate)->endOfDay());
            $returnsQuery->where('return_date', '<=', Carbon::parse($toDate)->endOfDay());
            $paymentsQuery->where('payment_date', '<=', Carbon::parse($toDate)->endOfDay());
        }

        $invoices = $invoicesQuery->orderBy('invoice_date')->orderBy('id')->get();
        $returns = $returnsQuery->orderBy('return_date')->orderBy('id')->get();
        $payments = $paymentsQuery->orderBy('payment_date')->orderBy('id')->get();

        // Merge and sort transactions chronologically
        $rows = new Collection;

        foreach ($invoices as $inv) {
            $date = $inv->invoice_date ? Carbon::parse($inv->invoice_date) : $inv->created_at;
            $paymentLabel = match ($inv->payment_type) {
                SalesInvoice::PAYMENT_CASH => 'نقدي',
                SalesInvoice::PAYMENT_CREDIT => 'آجل بالكامل',
                SalesInvoice::PAYMENT_PARTIAL => 'دفع جزئي',
                default => 'نقدي',
            };

            // Invoice line credits only the initial downpayment made at creation time.
            // Subsequent installments appear as distinct receipt voucher lines.
            $initialDownpayment = 0.00;
            if ($inv->payment_type === SalesInvoice::PAYMENT_CASH) {
                $initialDownpayment = (float) $inv->total_amount;
            } elseif ($inv->payment_type === SalesInvoice::PAYMENT_PARTIAL) {
                $subsequentPaymentsSum = (float) $inv->payments->sum('amount');
                $initialDownpayment = max(0.00, (float) $inv->paid_amount - $subsequentPaymentsSum);
            }

            $rows->push([
                'id' => 'inv-'.$inv->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i'),
                'type' => 'invoice',
                'type_label' => 'فاتورة مبيعات',
                'doc_number' => $inv->invoice_number,
                'warehouse_name' => $inv->warehouse?->name ?? '—',
                'description' => "فاتورة مبيعات ({$paymentLabel}) رقم {$inv->invoice_number}",
                'debit' => (float) $inv->total_amount,
                'credit' => $initialDownpayment,
                'remaining' => (float) $inv->remaining_amount,
                'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : null,
                'url' => route('sales.show', $inv),
            ]);
        }

        foreach ($returns as $ret) {
            $date = $ret->return_date ? Carbon::parse($ret->return_date) : $ret->created_at;
            $rows->push([
                'id' => 'ret-'.$ret->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i'),
                'type' => 'return',
                'type_label' => 'مرتجع مبيعات',
                'doc_number' => $ret->return_number,
                'warehouse_name' => $ret->warehouse?->name ?? '—',
                'description' => "مرتجع مبيعات رقم {$ret->return_number}",
                'debit' => 0.00,
                'credit' => (float) $ret->total_amount,
                'remaining' => 0.00,
                'due_date' => null,
                'url' => '#',
            ]);
        }

        foreach ($payments as $pay) {
            $date = $pay->payment_date ? Carbon::parse($pay->payment_date) : $pay->created_at;
            $methodLabel = $pay->payment_method_label;
            $desc = $pay->sales_invoice_id && $pay->salesInvoice
                ? "سند قبض / سداد دفعة ({$methodLabel}) على فاتورة رقم {$pay->salesInvoice->invoice_number}"
                : "سند قبض / تحصيل دفعة ({$methodLabel}) لحساب العميل";

            if ($pay->reference_number) {
                $desc .= " — مرجع: {$pay->reference_number}";
            }
            if ($pay->notes) {
                $desc .= " ({$pay->notes})";
            }

            $rows->push([
                'id' => 'pay-'.$pay->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $pay->created_at ? $pay->created_at->format('H:i') : '00:00',
                'type' => 'payment',
                'type_label' => 'سند قبض / دفعة',
                'doc_number' => $pay->payment_number,
                'warehouse_name' => $pay->salesInvoice?->warehouse?->name ?? '—',
                'description' => $desc,
                'debit' => 0.00,
                'credit' => (float) $pay->amount,
                'remaining' => 0.00,
                'due_date' => null,
                'url' => route('sales.payments.receipt', $pay),
            ]);
        }

        $sortedRows = $rows->sortBy('raw_date')->values();

        // Calculate running balance
        $runningBalance = $prePeriodBalance;
        $totalDebits = 0.00;
        $totalCredits = 0.00;

        $statementRows = [];
        foreach ($sortedRows as $row) {
            $debit = (float) $row['debit'];
            $credit = (float) $row['credit'];

            $totalDebits += $debit;
            $totalCredits += $credit;

            $runningBalance += ($debit - $credit);
            $row['running_balance'] = round($runningBalance, 2);
            $statementRows[] = $row;
        }

        $finalBalance = round($runningBalance, 2);

        return [
            'customer' => $customer,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => round($openingBalance, 2),
            'pre_period_balance' => round($prePeriodBalance, 2),
            'rows' => $statementRows,
            'total_debits' => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
            'net_movement' => round($totalDebits - $totalCredits, 2),
            'final_balance' => $finalBalance,
            'is_debt' => $finalBalance > 0,
            'is_credit' => $finalBalance < 0,
            'balance_status_label' => $finalBalance > 0 ? 'مستحق على العميل (مدين)' : ($finalBalance < 0 ? 'رصيد دائن لصالح العميل' : 'الحساب متزن'),
        ];
    }

    /**
     * Get purchased items breakdown for a customer.
     */
    public function getCustomerPurchasedItems(
        Customer $customer,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $search = null,
        ?int $categoryId = null
    ): array {
        $query = SalesInvoiceItem::query()
            ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->join('products', 'sales_invoice_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('units', 'sales_invoice_items.unit_id', '=', 'units.id')
            ->where('sales_invoices.customer_id', $customer->id)
            ->where('sales_invoices.status', SalesInvoice::STATUS_POSTED)
            ->select([
                'sales_invoice_items.*',
                'sales_invoices.invoice_number',
                'sales_invoices.invoice_date',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.barcode as product_barcode',
                'products.retail_price as standard_retail_price',
                'products.wholesale_price as standard_wholesale_price',
                'products.distributor_price as standard_distributor_price',
                'categories.name as category_name',
                'units.name as unit_display_name',
            ]);

        if ($fromDate) {
            $query->where('sales_invoices.invoice_date', '>=', Carbon::parse($fromDate)->startOfDay());
        }
        if ($toDate) {
            $query->where('sales_invoices.invoice_date', '<=', Carbon::parse($toDate)->endOfDay());
        }
        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('sales_invoices.invoice_date')->orderByDesc('sales_invoices.id')->get();

        $totalQuantity = 0.0;
        $totalSpend = 0.0;

        $formattedItems = [];
        foreach ($items as $item) {
            $qty = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;
            $lineDiscount = (float) $item->line_discount;
            $subtotal = (float) $item->subtotal;

            $totalQuantity += $qty;
            $totalSpend += $subtotal;

            // Tier comparison
            $customerTier = $customer->pricing_tier;
            $tierBenchmark = match ($customerTier) {
                'wholesale' => (float) ($item->standard_wholesale_price ?? 0),
                'distributor' => (float) ($item->standard_distributor_price ?? 0),
                default => (float) ($item->standard_retail_price ?? 0),
            };

            $diffVsTier = $tierBenchmark > 0 ? ($unitPrice - $tierBenchmark) : 0;

            $formattedItems[] = [
                'id' => $item->id,
                'invoice_id' => $item->sales_invoice_id,
                'invoice_number' => $item->invoice_number,
                'invoice_date' => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('Y-m-d') : '—',
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'product_barcode' => $item->product_barcode,
                'category_name' => $item->category_name ?: 'عام',
                'unit_name' => $item->unit_display_name ?: $item->unit_name ?: 'قطعة',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_discount' => $lineDiscount,
                'subtotal' => $subtotal,
                'standard_retail_price' => (float) $item->standard_retail_price,
                'standard_wholesale_price' => (float) $item->standard_wholesale_price,
                'standard_distributor_price' => (float) $item->standard_distributor_price,
                'tier_benchmark' => $tierBenchmark,
                'diff_vs_tier' => $diffVsTier,
                'url' => route('sales.show', $item->sales_invoice_id),
            ];
        }

        return [
            'customer' => $customer,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'search' => $search,
            'category_id' => $categoryId,
            'items' => $formattedItems,
            'items_count' => count($formattedItems),
            'total_quantity' => round($totalQuantity, 4),
            'total_spend' => round($totalSpend, 2),
        ];
    }

    /**
     * Generate WhatsApp share URL with encoded Arabic summary.
     */
    public function generateWhatsAppUrl(Customer $customer, ?string $statementUrl = null): string
    {
        $phone = preg_replace('/\D+/', '', $customer->phone ?? '');
        if (str_starts_with($phone, '01')) {
            $phone = '2'.$phone; // Egyptian national format
        }

        $lastInvoice = SalesInvoice::where('customer_id', $customer->id)
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->latest('invoice_date')
            ->first();

        $lastDate = $lastInvoice?->invoice_date ? Carbon::parse($lastInvoice->invoice_date)->format('Y-m-d') : 'لا يوجد';
        $balance = number_format((float) $customer->current_balance, 2);
        $invoicesCount = SalesInvoice::where('customer_id', $customer->id)->where('status', SalesInvoice::STATUS_POSTED)->count();
        $url = $statementUrl ?: route('customers.statement', $customer);

        $statusText = (float) $customer->current_balance > 0
            ? "مستحق عليكم: {$balance} ج.م"
            : ((float) $customer->current_balance < 0 ? "رصيد دائن لصالحكم: {$balance} ج.م" : 'الرصيد: 0.00 ج.م (حساب متزن)');

        $lines = [
            'السلام عليكم ورحمة الله وبركاته 🌹',
            "السيد العميل المحترم: *{$customer->name}*",
            'مرفق ملخص كشف حسابكم التجاري لدى *مؤسسة كيان*:',
            '───────────────────',
            "💰 *{$statusText}*",
            "📋 *عدد الفواتير المسجلة:* {$invoicesCount}",
            "📅 *تاريخ آخر حركة:* {$lastDate}",
            '───────────────────',
            '🔗 *رابط كشف الحساب والطباعة المباشرة:*',
            $url,
            '───────────────────',
            'نشكركم لثقتكم الغالية ونسعد دائماً بخدمتكم.',
        ];

        $message = implode("\n", $lines);

        return 'https://api.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message);
    }

    /**
     * Build supplier account statement with running balance.
     */
    public function getSupplierStatement(Supplier $supplier, ?string $fromDate = null, ?string $toDate = null): array
    {
        $openingBalance = (float) ($supplier->opening_balance ?? 0.00);

        $purchasesQuery = PurchaseInvoice::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', PurchaseInvoice::STATUS_POSTED)
            ->with(['warehouse']);

        // Fallback for suppliers where opening_balance was not stored but current_balance was set initially
        if ($openingBalance == 0.00 && (float) ($supplier->current_balance ?? 0.00) != 0.00) {
            $hasPurchases = (clone $purchasesQuery)->exists();
            if (! $hasPurchases) {
                $openingBalance = (float) $supplier->current_balance;
            }
        }

        $prePeriodBalance = $openingBalance;
        $paymentsQuery = SupplierPayment::query()
            ->where('supplier_id', $supplier->id)
            ->with(['purchaseInvoice']);
        $returnsQuery = PurchaseReturn::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', PurchaseReturn::STATUS_POSTED)
            ->with(['warehouse']);

        if ($fromDate) {
            $from = Carbon::parse($fromDate)->startOfDay();
            $priorPurchases = (clone $purchasesQuery)->where('invoice_date', '<', $from)->get();
            foreach ($priorPurchases as $p) {
                // Supplier normal balance is credit: purchases increase debt to supplier (+)
                // Check if this prior purchase had subsequent payments
                $initialDownpayment = 0.00;
                if ($p->payment_type === PurchaseInvoice::PAYMENT_CASH) {
                    $initialDownpayment = (float) $p->total_amount;
                } elseif ($p->payment_type === PurchaseInvoice::PAYMENT_PARTIAL) {
                    $priorSubsequentPayments = SupplierPayment::where('purchase_invoice_id', $p->id)
                        ->where('payment_date', '<', $from)
                        ->sum('amount');
                    $initialDownpayment = max(0.00, (float) $p->paid_amount - (float) $priorSubsequentPayments);
                }
                $prePeriodBalance += ((float) $p->total_amount - $initialDownpayment);
            }

            $priorDirectPayments = (clone $paymentsQuery)->where('payment_date', '<', $from)->get();
            foreach ($priorDirectPayments as $dp) {
                // Payment reduces supplier balance
                $prePeriodBalance -= (float) $dp->amount;
            }

            $priorReturns = (clone $returnsQuery)->where('return_date', '<', $from)->get();
            foreach ($priorReturns as $pr) {
                if ($pr->settlement_type === PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE) {
                    $prePeriodBalance -= (float) $pr->total_amount;
                }
            }
        }

        if ($fromDate) {
            $purchasesQuery->where('invoice_date', '>=', Carbon::parse($fromDate)->startOfDay());
            $paymentsQuery->where('payment_date', '>=', Carbon::parse($fromDate)->startOfDay());
            $returnsQuery->where('return_date', '>=', Carbon::parse($fromDate)->startOfDay());
        }
        if ($toDate) {
            $purchasesQuery->where('invoice_date', '<=', Carbon::parse($toDate)->endOfDay());
            $paymentsQuery->where('payment_date', '<=', Carbon::parse($toDate)->endOfDay());
            $returnsQuery->where('return_date', '<=', Carbon::parse($toDate)->endOfDay());
        }

        $purchases = $purchasesQuery->orderBy('invoice_date')->orderBy('id')->get();
        $payments = $paymentsQuery->orderBy('payment_date')->orderBy('id')->get();
        $returns = $returnsQuery->orderBy('return_date')->orderBy('id')->get();

        $rows = collect();

        foreach ($purchases as $p) {
            $date = $p->invoice_date ? Carbon::parse($p->invoice_date) : $p->created_at;
            $paymentLabel = match ($p->payment_type) {
                PurchaseInvoice::PAYMENT_CASH => 'نقدي',
                PurchaseInvoice::PAYMENT_CREDIT => 'آجل بالكامل',
                PurchaseInvoice::PAYMENT_PARTIAL => 'دفع جزئي',
                default => 'نقدي',
            };

            $initialDownpayment = 0.00;
            if ($p->payment_type === PurchaseInvoice::PAYMENT_CASH) {
                $initialDownpayment = (float) $p->total_amount;
            } elseif ($p->payment_type === PurchaseInvoice::PAYMENT_PARTIAL) {
                $subsequentPaymentsForInvoice = (float) SupplierPayment::where('purchase_invoice_id', $p->id)->sum('amount');
                $initialDownpayment = max(0.00, (float) $p->paid_amount - $subsequentPaymentsForInvoice);
            }

            $rows->push([
                'id' => 'pur-'.$p->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i'),
                'type' => 'purchase',
                'type_label' => 'فاتورة توريد/شراء',
                'doc_number' => $p->invoice_number,
                'warehouse_name' => $p->warehouse?->name ?? '—',
                'description' => "فاتورة توريد مشتريات ({$paymentLabel}) رقم {$p->invoice_number}",
                'credit' => (float) $p->total_amount,
                'debit' => $initialDownpayment,
                'remaining' => (float) $p->remaining_amount,
                'due_date' => $p->due_date ? Carbon::parse($p->due_date)->format('Y-m-d') : null,
                'url' => route('purchases.show', $p),
            ]);
        }

        foreach ($payments as $pay) {
            $date = $pay->payment_date ? Carbon::parse($pay->payment_date) : $pay->created_at;
            $methodLabel = $pay->payment_method_label;
            $desc = $pay->purchase_invoice_id && $pay->purchaseInvoice
                ? "سند صرف دفعة ({$methodLabel}) على فاتورة رقم {$pay->purchaseInvoice->invoice_number}"
                : "سند صرف دفعة ({$methodLabel}) لحساب المورد";

            if ($pay->reference_number) {
                $desc .= " — مرجع: {$pay->reference_number}";
            }
            if ($pay->notes) {
                $desc .= " ({$pay->notes})";
            }

            $rows->push([
                'id' => 'spay-'.$pay->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $pay->created_at ? $pay->created_at->format('H:i') : '00:00',
                'type' => 'payment',
                'type_label' => 'سند صرف / دفعة مورد',
                'doc_number' => $pay->payment_number,
                'warehouse_name' => '—',
                'description' => $desc,
                'credit' => 0.00,
                'debit' => (float) $pay->amount,
                'remaining' => 0.00,
                'due_date' => null,
                'url' => route('supplier-payments.receipt', $pay),
            ]);
        }

        foreach ($returns as $ret) {
            $date = $ret->return_date ? Carbon::parse($ret->return_date) : $ret->created_at;
            $settleLabel = $ret->settlement_type_label;
            $desc = "مرتجع مشتريات ({$settleLabel}) رقم {$ret->return_number}";
            if ($ret->notes) {
                $desc .= " — {$ret->notes}";
            }

            // Only deduct_balance affects running balance of supplier account.
            $debit = $ret->settlement_type === PurchaseReturn::SETTLEMENT_DEDUCT_BALANCE ? (float) $ret->total_amount : 0.00;

            $rows->push([
                'id' => 'pret-'.$ret->id,
                'raw_date' => $date->toIso8601String(),
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i'),
                'type' => 'return',
                'type_label' => 'مرتجع مشتريات',
                'doc_number' => $ret->return_number,
                'warehouse_name' => $ret->warehouse?->name ?? '—',
                'description' => $desc,
                'credit' => 0.00,
                'debit' => $debit,
                'remaining' => 0.00,
                'due_date' => null,
                'url' => route('purchase-returns.show', $ret),
            ]);
        }

        $sortedRows = $rows->sortBy([
            ['raw_date', 'asc'],
            ['id', 'asc'],
        ])->values();

        $statementRows = [];
        $runningBalance = $prePeriodBalance;
        $totalCredits = 0.00;
        $totalDebits = 0.00;

        foreach ($sortedRows as $row) {
            $credit = (float) $row['credit'];
            $debit = (float) $row['debit'];

            $totalCredits += $credit;
            $totalDebits += $debit;

            $runningBalance += ($credit - $debit);

            $row['running_balance'] = round($runningBalance, 2);
            $statementRows[] = $row;
        }

        $finalBalance = round($runningBalance, 2);

        return [
            'supplier' => $supplier,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => round($openingBalance, 2),
            'pre_period_balance' => round($prePeriodBalance, 2),
            'rows' => $statementRows,
            'total_credits' => round($totalCredits, 2),
            'total_debits' => round($totalDebits, 2),
            'net_movement' => round($totalCredits - $totalDebits, 2),
            'final_balance' => $finalBalance,
            'currency' => $supplier->currency ?? 'CNY',
        ];
    }

    /**
     * Build Supplier Balance Due report across all suppliers.
     */
    public function getSupplierBalanceDueReport(?int $supplierId = null, ?string $currency = null): array
    {
        $query = Supplier::query()->active();

        if ($supplierId) {
            $query->where('id', $supplierId);
        }
        if ($currency) {
            $query->where('currency', $currency);
        }

        $suppliers = $query->orderBy('name')->get();

        $rows = [];
        $totalsByCurrency = [];

        foreach ($suppliers as $sup) {
            $balance = (float) $sup->current_balance;
            $curr = $sup->currency ?: 'CNY';

            if (! isset($totalsByCurrency[$curr])) {
                $totalsByCurrency[$curr] = 0.00;
            }
            $totalsByCurrency[$curr] += $balance;

            $lastPurchase = PurchaseInvoice::where('supplier_id', $sup->id)
                ->where('status', PurchaseInvoice::STATUS_POSTED)
                ->latest('invoice_date')
                ->first();

            $rows[] = [
                'supplier' => $sup,
                'id' => $sup->id,
                'name' => $sup->name,
                'type_label' => $sup->getTypeLabel(),
                'contact_person' => $sup->contact_person ?: '—',
                'phone' => $sup->phone ?: '—',
                'currency' => $curr,
                'current_balance' => $balance,
                'last_purchase_date' => $lastPurchase?->invoice_date ? Carbon::parse($lastPurchase->invoice_date)->format('Y-m-d') : null,
                'last_purchase_number' => $lastPurchase?->invoice_number,
                'last_purchase_amount' => $lastPurchase ? (float) $lastPurchase->total_amount : 0.00,
            ];
        }

        return [
            'rows' => $rows,
            'totals_by_currency' => $totalsByCurrency,
            'total_suppliers_count' => count($rows),
            'filtered_currency' => $currency,
        ];
    }
}
