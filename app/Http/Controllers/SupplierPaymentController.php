<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierInstallment;
use App\Models\SupplierPayment;
use App\Models\Treasury;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupplierPayment::with(['supplier', 'purchaseInvoice', 'treasury', 'creator'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', Carbon::parse($request->input('from_date'))->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', Carbon::parse($request->input('to_date'))->endOfDay());
        }

        $payments = $query->paginate(20)->withQueryString();

        $metrics = [
            'total_payments' => (float) (clone $query)->sum('amount'),
            'payments_count' => (clone $query)->count(),
            'suppliers_count' => Supplier::count(),
            'total_payables' => (float) Supplier::where('current_balance', '>', 0)->sum('current_balance'),
            'overdue_installments_count' => SupplierInstallment::overdue()->count(),
        ];

        return view('suppliers.payments.index', [
            'title' => 'مدفوعات وسندات صرف الموردين',
            'payments' => $payments,
            'metrics' => $metrics,
            'suppliers' => Supplier::orderBy('name')->get(),
            'treasuries' => Treasury::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeForInvoice(Request $request, PurchaseInvoice $purchase): RedirectResponse
    {
        $remaining = (float) $purchase->remaining_amount;

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', "max:{$remaining}"],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,card'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.max' => 'المبلغ المدفوع لا يمكن أن يتجاوز متبقي الفاتورة الحالي ('.number_format($remaining, 2).' ج.م)',
        ]);

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($purchase, $validated, $amount) {
            $invoice = PurchaseInvoice::where('id', $purchase->id)->lockForUpdate()->firstOrFail();
            $supplier = Supplier::where('id', $invoice->supplier_id)->lockForUpdate()->firstOrFail();

            // Resolve Treasury
            $treasury = null;
            if (! empty($validated['treasury_id'])) {
                $treasury = Treasury::where('id', $validated['treasury_id'])->lockForUpdate()->first();
            } else {
                $treasury = Treasury::getDefault();
            }

            // Check if cashier shift is open for current user and method is cash
            $activeShift = null;
            if ($validated['payment_method'] === SupplierPayment::METHOD_CASH) {
                $activeShift = CashierShift::getActiveShiftForUser(auth()->id());
            }

            $paymentNumber = SupplierPayment::generatePaymentNumber();

            $payment = SupplierPayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $supplier->id,
                'purchase_invoice_id' => $invoice->id,
                'treasury_id' => $treasury?->id,
                'cashier_shift_id' => $activeShift?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Deduct from Treasury if selected
            if ($treasury) {
                $treasury->recordWithdrawal(
                    $amount,
                    'supplier_payment',
                    $payment->id,
                    "سند صرف مورد ({$supplier->name}) على فاتورة توريد رقم {$invoice->invoice_number}",
                    auth()->id(),
                    $validated['payment_date']
                );
            }

            // Record in Cashier Shift if disbursed from shift drawer
            if ($activeShift) {
                $activeShift->recordSupplierPayment($payment);
            }

            // Update invoice amounts
            $invoice->paid_amount = round((float) $invoice->paid_amount + $amount, 2);
            $invoice->remaining_amount = max(0.00, round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2));
            if ($invoice->remaining_amount <= 0.0001) {
                $invoice->payment_type = PurchaseInvoice::PAYMENT_CASH;
            } else {
                $invoice->payment_type = PurchaseInvoice::PAYMENT_PARTIAL;
            }
            $invoice->save();

            // Update supplier balance (reduces payable)
            $supplier->current_balance = round((float) $supplier->current_balance - $amount, 2);
            $supplier->save();

            // Record general ledger journal entry
            app(AccountingService::class)->recordSupplierPayment($payment);
        });

        return back()->with('success', 'تم تسجيل سند صرف المورد وتحديث الحساب بنجاح');
    }

    public function storeForSupplier(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,card'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'purchase_invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
            'supplier_installment_id' => ['nullable', 'exists:supplier_installments,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (float) $validated['amount'];

        $createdPayment = DB::transaction(function () use ($supplier, $validated, $amount) {
            $sup = Supplier::where('id', $supplier->id)->lockForUpdate()->firstOrFail();

            $installment = null;
            if (! empty($validated['supplier_installment_id'])) {
                $installment = SupplierInstallment::where('id', $validated['supplier_installment_id'])
                    ->where('supplier_id', $sup->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($amount > (float) $installment->remaining_amount) {
                    throw new \InvalidArgumentException('المبلغ المدفوع يتجاوز متبقي القسط المحدد ('.number_format((float) $installment->remaining_amount, 2).' ج.م)');
                }
            }

            $invoice = null;
            $invoiceId = $validated['purchase_invoice_id'] ?? $installment?->purchase_invoice_id;
            if (! empty($invoiceId)) {
                $invoice = PurchaseInvoice::where('id', $invoiceId)
                    ->where('supplier_id', $sup->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($amount > (float) $invoice->remaining_amount) {
                    throw new \InvalidArgumentException('المبلغ المدفوع يتجاوز متبقي الفاتورة المحددة ('.number_format((float) $invoice->remaining_amount, 2).' ج.م)');
                }
            }

            $treasury = null;
            if (! empty($validated['treasury_id'])) {
                $treasury = Treasury::where('id', $validated['treasury_id'])->lockForUpdate()->first();
            } else {
                $treasury = Treasury::getDefault();
            }

            $activeShift = null;
            if ($validated['payment_method'] === SupplierPayment::METHOD_CASH) {
                $activeShift = CashierShift::getActiveShiftForUser(auth()->id());
            }

            $paymentNumber = SupplierPayment::generatePaymentNumber();

            $payment = SupplierPayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $sup->id,
                'purchase_invoice_id' => $invoice?->id,
                'supplier_installment_id' => $installment?->id,
                'treasury_id' => $treasury?->id,
                'cashier_shift_id' => $activeShift?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Deduct from Treasury
            if ($treasury) {
                $desc = $installment
                    ? "سند صرف قسط ({$installment->installment_title}) للمورد ({$sup->name})"
                    : ($invoice
                        ? "سند صرف دفعة للمورد ({$sup->name}) على فاتورة رقم {$invoice->invoice_number}"
                        : "سند صرف دفعة عامة لحساب المورد ({$sup->name})");
                $treasury->recordWithdrawal(
                    $amount,
                    'supplier_payment',
                    $payment->id,
                    $desc,
                    auth()->id(),
                    $validated['payment_date']
                );
            }

            if ($activeShift) {
                $activeShift->recordSupplierPayment($payment);
            }

            if ($installment) {
                $installment->recordPayment($amount);
            }

            if ($invoice) {
                $invoice->paid_amount = round((float) $invoice->paid_amount + $amount, 2);
                $invoice->remaining_amount = max(0.00, round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2));
                if ($invoice->remaining_amount <= 0.0001) {
                    $invoice->payment_type = PurchaseInvoice::PAYMENT_CASH;
                } else {
                    $invoice->payment_type = PurchaseInvoice::PAYMENT_PARTIAL;
                }
                $invoice->save();
            }

            // Update supplier balance
            $sup->current_balance = round((float) $sup->current_balance - $amount, 2);
            $sup->save();

            // Record general ledger journal entry
            app(AccountingService::class)->recordSupplierPayment($payment);

            return $payment;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل سند صرف المورد بنجاح',
                'payment_id' => $createdPayment->id,
                'receipt_url' => route('supplier-payments.receipt', $createdPayment),
            ]);
        }

        return back()->with('success', 'تم تسجيل سند صرف المورد بنجاح');
    }

    public function payablesData(Supplier $supplier): JsonResponse
    {
        $unpaidInvoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->where('status', PurchaseInvoice::STATUS_POSTED)
            ->where('remaining_amount', '>', 0)
            ->orderBy('invoice_date')
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'paid_amount', 'remaining_amount', 'currency']);

        $pendingInstallments = SupplierInstallment::where('supplier_id', $supplier->id)
            ->pending()
            ->orderBy('due_date')
            ->get()
            ->map(function ($inst) {
                return [
                    'id' => $inst->id,
                    'installment_title' => $inst->installment_title,
                    'due_date' => $inst->due_date->format('Y-m-d'),
                    'amount' => (float) $inst->amount,
                    'paid_amount' => (float) $inst->paid_amount,
                    'remaining_amount' => (float) $inst->remaining_amount,
                    'status_label' => $inst->status_label,
                    'status_badge_class' => $inst->status_badge_class,
                    'is_overdue' => $inst->is_overdue,
                    'is_due_today' => $inst->is_due_today,
                    'purchase_invoice_id' => $inst->purchase_invoice_id,
                ];
            });

        $treasuries = Treasury::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return response()->json([
            'success' => true,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'current_balance' => (float) $supplier->current_balance,
                'currency' => $supplier->currency,
                'supplier_type' => $supplier->supplier_type,
            ],
            'invoices' => $unpaidInvoices,
            'installments' => $pendingInstallments,
            'treasuries' => $treasuries,
        ]);
    }

    public function receipt(SupplierPayment $payment, Request $request): View
    {
        $payment->load(['supplier', 'purchaseInvoice', 'installment', 'treasury', 'creator']);
        $company = CompanySetting::first();
        $format = $request->query('format', 'a5');
        $title = 'سند صرف نقدية / بنكي - '.$payment->payment_number;

        return view('suppliers.payments.receipt', [
            'payment' => $payment,
            'company' => $company,
            'format' => $format,
            'title' => $title,
        ]);
    }

    public function destroy(SupplierPayment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment) {
            $pay = SupplierPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $pay->amount;

            // Rollback installment if linked
            if ($pay->supplier_installment_id && $pay->installment) {
                $inst = $pay->installment;
                $inst->paid_amount = max(0.00, round((float) $inst->paid_amount - $amount, 2));
                $inst->status = ($inst->paid_amount <= 0.0001 ? SupplierInstallment::STATUS_PENDING : SupplierInstallment::STATUS_PARTIAL);
                $inst->save();
            }

            // Rollback invoice if linked
            if ($pay->purchase_invoice_id && $pay->purchaseInvoice) {
                $inv = $pay->purchaseInvoice;
                $inv->paid_amount = max(0.00, round((float) $inv->paid_amount - $amount, 2));
                $inv->remaining_amount = round((float) $inv->total_amount - (float) $inv->paid_amount, 2);
                $inv->payment_type = $inv->remaining_amount > 0 ? PurchaseInvoice::PAYMENT_PARTIAL : PurchaseInvoice::PAYMENT_CASH;
                $inv->save();
            }

            // Restore supplier balance (increase debt back)
            if ($pay->supplier) {
                $pay->supplier->current_balance = round((float) $pay->supplier->current_balance + $amount, 2);
                $pay->supplier->save();
            }

            // Refund treasury if deducted
            if ($pay->treasury_id && $pay->treasury) {
                $pay->treasury->recordDeposit(
                    $amount,
                    'manual',
                    $pay->id,
                    "إلغاء سند صرف مورد رقم {$pay->payment_number} واسترداد المبلغ للخزينة",
                    auth()->id(),
                    now()->toDateString()
                );
            }

            // Reverse general ledger journal entry before deletion
            app(AccountingService::class)->voidEntry(
                JournalEntry::REF_SUPPLIER_PAYMENT,
                $pay->id,
                'إلغاء وحذف سند صرف المورد',
                auth()->id()
            );

            $pay->delete();
        });

        return back()->with('success', 'تم إلغاء سند الصرف واسترجاع المديونية والأرصدة بنجاح');
    }
}
