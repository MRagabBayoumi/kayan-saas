<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerPaymentController extends Controller
{
    /**
     * Display a listing of all customer payments and receipt vouchers.
     */
    public function index(Request $request): View
    {
        $query = CustomerPayment::with(['customer', 'salesInvoice', 'creator'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
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
            'total_collected' => (float) (clone $query)->sum('amount'),
            'payments_count' => (clone $query)->count(),
            'customers_count' => Customer::count(),
            'total_receivables' => (float) Customer::where('current_balance', '>', 0)->sum('current_balance'),
        ];

        return view('sales.payments.index', [
            'title' => 'سندات قبض ومتحصلات العملاء',
            'payments' => $payments,
            'metrics' => $metrics,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a payment / installment against a specific sales invoice.
     */
    public function storeForInvoice(Request $request, SalesInvoice $sale): RedirectResponse
    {
        if ($sale->status !== SalesInvoice::STATUS_POSTED) {
            return back()->with('error', 'لا يمكن تسجيل دفعات إلا على فواتير المبيعات المرحّلة فقط.');
        }

        $remaining = (float) $sale->remaining_amount;
        if ($remaining <= 0.001) {
            return back()->with('error', 'هذه الفاتورة مسددة بالكامل ولا يوجد مبالغ متبقية عليها.');
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', "max:{$remaining}"],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,cheque,card'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_date.required' => 'تاريخ الدفعة مطلوب.',
            'amount.required' => 'مبلغ الدفعة مطلوب.',
            'amount.min' => 'يجب أن يكون مبلغ الدفعة أكبر من الصفر.',
            'amount.max' => "مبلغ الدفعة لا يمكن أن يتجاوز المتبقي على الفاتورة ({$remaining} ج.م).",
            'payment_method.required' => 'طريقة السداد مطلوبة.',
            'payment_method.in' => 'طريقة السداد المختارة غير صالحة.',
        ]);

        $amount = (float) $validated['amount'];
        $user = auth()->user();

        // Check for active cashier shift if paying in cash
        $activeShift = null;
        if ($user) {
            $activeShift = CashierShift::getActiveShift($user->id, (int) $sale->warehouse_id)
                ?: CashierShift::getActiveShift($user->id)
                ?: CashierShift::open()->where('warehouse_id', (int) $sale->warehouse_id)->latest('id')->first();
        }

        $payment = DB::transaction(function () use ($sale, $validated, $amount, $activeShift, $user) {
            $lockedSale = SalesInvoice::lockForUpdate()->findOrFail($sale->id);
            $customer = Customer::lockForUpdate()->findOrFail($sale->customer_id);

            if ($amount > (float) $lockedSale->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => "المبلغ المدخل ({$amount} ج.م) أكبر من المتبقي الفعلي حالياً ({$lockedSale->remaining_amount} ج.م).",
                ]);
            }

            $paymentNumber = CustomerPayment::generatePaymentNumber();

            $payment = CustomerPayment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'sales_invoice_id' => $lockedSale->id,
                'cashier_shift_id' => $activeShift?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            // Update sales invoice paid and remaining balances
            $newPaid = round((float) $lockedSale->paid_amount + $amount, 2);
            $newRemaining = max(0.00, round((float) $lockedSale->remaining_amount - $amount, 2));

            $lockedSale->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
            ]);

            // Sync customer current balance accurately
            $customer->recalculateCurrentBalance();

            // Record into active cashier shift if cash
            if ($activeShift && $payment->payment_method === CustomerPayment::METHOD_CASH) {
                $activeShift->recordCustomerPayment($payment);
            }

            // Record general ledger journal entry
            app(AccountingService::class)->recordCustomerPayment($payment);

            return $payment;
        });

        return redirect()->route('sales.show', $sale)
            ->with('status', 'success')
            ->with('message', "تم تسجيل الدفعة بنجاح برقم سند: {$payment->payment_number} بمبلغ {$amount} ج.م.");
    }

    /**
     * Store a payment directly on a customer's account (optionally linked to an invoice).
     */
    public function storeForCustomer(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,cheque,card'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_date.required' => 'تاريخ الدفعة مطلوب.',
            'amount.required' => 'مبلغ الدفعة مطلوب.',
            'amount.min' => 'يجب أن يكون مبلغ الدفعة أكبر من الصفر.',
            'payment_method.required' => 'طريقة السداد مطلوبة.',
            'payment_method.in' => 'طريقة السداد المختارة غير صالحة.',
            'sales_invoice_id.exists' => 'الفاتورة المحددة غير موجودة.',
        ]);

        $amount = (float) $validated['amount'];
        $user = auth()->user();
        $invoiceId = ! empty($validated['sales_invoice_id']) ? (int) $validated['sales_invoice_id'] : null;

        $invoice = null;
        if ($invoiceId) {
            $invoice = SalesInvoice::where('customer_id', $customer->id)->findOrFail($invoiceId);
            if ($amount > (float) $invoice->remaining_amount) {
                throw ValidationException::withMessages([
                    'amount' => "المبلغ المدخل ({$amount} ج.م) أكبر من المتبقي على الفاتورة المختارة ({$invoice->remaining_amount} ج.م).",
                ]);
            }
        }

        $activeShift = null;
        if ($user) {
            $activeShift = CashierShift::getActiveShift($user->id)
                ?: CashierShift::open()->latest('id')->first();
        }

        $payment = DB::transaction(function () use ($customer, $invoice, $validated, $amount, $activeShift, $user) {
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($customer->id);
            $paymentNumber = CustomerPayment::generatePaymentNumber();

            $payment = CustomerPayment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $lockedCustomer->id,
                'sales_invoice_id' => $invoice?->id,
                'cashier_shift_id' => $activeShift?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            if ($invoice) {
                $lockedInvoice = SalesInvoice::lockForUpdate()->findOrFail($invoice->id);
                $newPaid = round((float) $lockedInvoice->paid_amount + $amount, 2);
                $newRemaining = max(0.00, round((float) $lockedInvoice->remaining_amount - $amount, 2));

                $lockedInvoice->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                ]);
            } else {
                // Auto-allocate general payment to unpaid posted invoices (FIFO order)
                $unpaidInvoices = SalesInvoice::where('customer_id', $lockedCustomer->id)
                    ->where('status', SalesInvoice::STATUS_POSTED)
                    ->where('remaining_amount', '>', 0.001)
                    ->orderBy('invoice_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $remainingToAllocate = $amount;
                foreach ($unpaidInvoices as $uInv) {
                    if ($remainingToAllocate <= 0.001) {
                        break;
                    }
                    $uRem = (float) $uInv->remaining_amount;
                    $portion = min($remainingToAllocate, $uRem);
                    $uInv->update([
                        'paid_amount' => round((float) $uInv->paid_amount + $portion, 2),
                        'remaining_amount' => max(0.00, round($uRem - $portion, 2)),
                    ]);
                    $remainingToAllocate -= $portion;
                }
            }

            // Synchronize customer current balance accurately with full transaction history
            $lockedCustomer->recalculateCurrentBalance();

            if ($activeShift && $payment->payment_method === CustomerPayment::METHOD_CASH) {
                $activeShift->recordCustomerPayment($payment);
            }

            // Record general ledger journal entry
            app(AccountingService::class)->recordCustomerPayment($payment);

            return $payment;
        });

        return back()->with('success', "تم تسجيل سند القبض بنجاح برقم: {$payment->payment_number} بمبلغ {$amount} ج.م.");
    }

    /**
     * Dedicated printable receipt voucher for customer payment (سند قبض).
     */
    public function receipt(CustomerPayment $payment, Request $request): View
    {
        $payment->loadMissing(['customer', 'salesInvoice.warehouse', 'creator']);
        $company = CompanySetting::getSettings();

        $format = $request->input('format', 'a5');
        if (! in_array($format, ['a4', 'a5', 'thermal_80mm', 'thermal'], true)) {
            $format = 'a5';
        }

        return view('sales.payments.receipt', [
            'title' => 'سند قبض مالي: '.$payment->payment_number,
            'payment' => $payment,
            'company' => $company,
            'format' => $format,
        ]);
    }

    /**
     * Void / Delete a payment receipt with full safety ledger rollback.
     */
    public function destroy(CustomerPayment $payment): RedirectResponse
    {
        $user = auth()->user();
        if (! $user?->isAdmin()) {
            abort(403, 'صلاحية إلغاء أو حذف سندات القبض مخصصة لمدير النظام فقط.');
        }

        $invoice = $payment->salesInvoice;
        $amount = (float) $payment->amount;

        DB::transaction(function () use ($payment, $invoice, $amount) {
            $customer = Customer::lockForUpdate()->findOrFail($payment->customer_id);

            // Rollback invoice remaining & paid
            if ($invoice) {
                $lockedInvoice = SalesInvoice::lockForUpdate()->findOrFail($invoice->id);
                $lockedInvoice->decrement('paid_amount', min((float) $lockedInvoice->paid_amount, $amount));
                $lockedInvoice->increment('remaining_amount', $amount);
            }

            // Sync customer balance accurately
            $customer->recalculateCurrentBalance();

            // If cash and shift still open, reverse shift cash
            if ($payment->cashier_shift_id && $payment->payment_method === CustomerPayment::METHOD_CASH) {
                $shift = CashierShift::find($payment->cashier_shift_id);
                if ($shift && $shift->status === CashierShift::STATUS_OPEN) {
                    $shift->cash_sales_amount = max(0.00, round((float) $shift->cash_sales_amount - $amount, 2));
                    $shift->recalculateAndSave();
                }
            }

            // Reverse general ledger journal entry before deletion
            app(AccountingService::class)->voidEntry(
                JournalEntry::REF_CUSTOMER_PAYMENT,
                $payment->id,
                'إلغاء وحذف سند القبض',
                auth()->id()
            );

            $payment->delete();
        });

        if ($invoice) {
            return redirect()->route('sales.show', $invoice)
                ->with('status', 'success')
                ->with('message', 'تم إلغاء سند القبض وإرجاع رصيد الفاتورة وحساب العميل بنجاح.');
        }

        return back()->with('success', 'تم إلغاء سند القبض وإرجاع حساب العميل بنجاح.');
    }
}
