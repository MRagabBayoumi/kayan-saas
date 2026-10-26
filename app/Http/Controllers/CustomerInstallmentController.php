<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerInstallment;
use App\Models\CustomerPayment;
use App\Models\SalesInvoice;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerInstallmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerInstallment::with(['customer', 'salesInvoice', 'creator', 'payments'])
            ->orderBy('due_date')
            ->orderBy('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        $filter = $request->input('status', 'pending');

        if ($filter === 'overdue') {
            $query->overdue();
        } elseif ($filter === 'due_soon') {
            $query->dueSoon(7);
        } elseif ($filter === 'pending') {
            $query->pending();
        } elseif ($filter === 'paid') {
            $query->paid();
        }

        $installments = $query->paginate(20)->withQueryString();

        $allPendingQuery = CustomerInstallment::pending();
        $totalDue = (float) (clone $allPendingQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');

        $overdueQuery = CustomerInstallment::overdue();
        $overdueDue = (float) (clone $overdueQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');
        $overdueCount = (clone $overdueQuery)->count();

        $dueSoonQuery = CustomerInstallment::dueSoon(7);
        $dueSoonDue = (float) (clone $dueSoonQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');
        $dueSoonCount = (clone $dueSoonQuery)->count();

        $totalCollected = (float) CustomerInstallment::sum('paid_amount');

        $metrics = [
            'total_due' => $totalDue,
            'overdue_due' => $overdueDue,
            'overdue_count' => $overdueCount,
            'due_soon_due' => $dueSoonDue,
            'due_soon_count' => $dueSoonCount,
            'total_collected' => $totalCollected,
            'pending_count' => CustomerInstallment::pending()->count(),
            'paid_count' => CustomerInstallment::paid()->count(),
        ];

        $customers = Customer::approved()->active()->orderBy('name')->get();
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();

        return view('customers.installments.index', [
            'title' => 'جدول أقساط ومواعيد تحصيل العملاء',
            'installments' => $installments,
            'metrics' => $metrics,
            'customers' => $customers,
            'treasuries' => $treasuries,
            'currentFilter' => $filter,
            'selectedCustomerId' => $request->input('customer_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'plan_type' => ['nullable', 'in:single,schedule'],
            'installment_title' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Schedule generation fields
            'total_amount' => ['nullable', 'numeric', 'min:0.01'],
            'installments_count' => ['nullable', 'integer', 'min:2', 'max:36'],
            'first_due_date' => ['nullable', 'date'],
            'interval' => ['nullable', 'in:weekly,biweekly,monthly'],
        ], [
            'customer_id.required' => 'يرجى اختيار العميل.',
            'customer_id.exists' => 'العميل المحدد غير موجود.',
            'sales_invoice_id.exists' => 'فاتورة المبيعات المحددة غير موجودة.',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $invoice = null;
        if (! empty($validated['sales_invoice_id'])) {
            $invoice = SalesInvoice::where('id', $validated['sales_invoice_id'])
                ->where('customer_id', $customer->id)
                ->firstOrFail();
        }

        $planType = $validated['plan_type'] ?? 'single';

        if ($planType === 'schedule') {
            $request->validate([
                'total_amount' => ['required', 'numeric', 'min:0.01'],
                'installments_count' => ['required', 'integer', 'min:2', 'max:36'],
                'first_due_date' => ['required', 'date'],
                'interval' => ['required', 'in:weekly,biweekly,monthly'],
            ], [
                'total_amount.required' => 'إجمالي المبلغ المطلوب جدولته إلزامي.',
                'installments_count.required' => 'عدد الأقساط إلزامي (من 2 إلى 36 قسط).',
                'first_due_date.required' => 'تاريخ استحقاق أول قسط إلزامي.',
            ]);

            $totalAmount = (float) $validated['total_amount'];
            $count = (int) $validated['installments_count'];
            $startDate = Carbon::parse($validated['first_due_date']);
            $interval = $validated['interval'];

            $baseAmount = round($totalAmount / $count, 2);
            $accumulated = 0.00;

            DB::transaction(function () use ($customer, $invoice, $validated, $count, $totalAmount, $baseAmount, &$accumulated, $startDate, $interval) {
                for ($i = 1; $i <= $count; $i++) {
                    $installmentDate = match ($interval) {
                        'weekly' => $startDate->copy()->addWeeks($i - 1),
                        'biweekly' => $startDate->copy()->addWeeks(($i - 1) * 2),
                        'monthly' => $startDate->copy()->addMonths($i - 1),
                        default => $startDate->copy()->addMonths($i - 1),
                    };

                    $installmentAmount = ($i === $count)
                        ? round($totalAmount - $accumulated, 2)
                        : $baseAmount;

                    $accumulated += $installmentAmount;

                    $title = filled($validated['installment_title'] ?? null)
                        ? trim($validated['installment_title']).' (قسط '.$i.'/'.$count.')'
                        : ($invoice
                            ? 'قسط '.$i.'/'.$count.' - فاتورة #'.$invoice->invoice_number
                            : 'قسط مجدول '.$i.'/'.$count);

                    CustomerInstallment::create([
                        'customer_id' => $customer->id,
                        'sales_invoice_id' => $invoice?->id,
                        'installment_title' => $title,
                        'due_date' => $installmentDate->toDateString(),
                        'amount' => $installmentAmount,
                        'paid_amount' => 0.00,
                        'status' => CustomerInstallment::STATUS_PENDING,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            });

            return back()->with('success', "تم إنشاء جدول تحصيل يتضمن {$count} أقساط مجدولة للعميل بنجاح.");
        }

        // Single installment
        $request->validate([
            'installment_title' => ['required', 'string', 'max:100'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'installment_title.required' => 'عنوان القسط أو بيانه مطلوب.',
            'due_date.required' => 'تاريخ استحقاق التحصيل مطلوب.',
            'amount.required' => 'مبلغ القسط مطلوب.',
            'amount.min' => 'يجب أن يكون مبلغ القسط أكبر من الصفر.',
        ]);

        CustomerInstallment::create([
            'customer_id' => $customer->id,
            'sales_invoice_id' => $invoice?->id,
            'installment_title' => trim($validated['installment_title']),
            'due_date' => $validated['due_date'],
            'amount' => (float) $validated['amount'],
            'paid_amount' => 0.00,
            'status' => CustomerInstallment::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم تسجيل القسط المستحق في جدول تحصيل العميل بنجاح.');
    }

    public function collect(Request $request, CustomerInstallment $installment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,card'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'مبلغ التحصيل مطلوب.',
            'amount.min' => 'يجب أن يكون المبلغ المحصل أكبر من الصفر.',
            'payment_date.required' => 'تاريخ التحصيل مطلوب.',
            'payment_method.required' => 'طريقة التحصيل مطلوبة.',
        ]);

        $amount = (float) $validated['amount'];

        if ($amount > (float) $installment->remaining_amount) {
            throw ValidationException::withMessages([
                'amount' => "المبلغ المدخل ({$amount} ج.م) أكبر من المتبقي على هذا القسط ({$installment->remaining_amount} ج.م).",
            ]);
        }

        $user = auth()->user();
        $activeShift = null;
        if ($user) {
            $activeShift = CashierShift::getActiveShift($user->id)
                ?: CashierShift::open()->latest('id')->first();
        }

        $payment = DB::transaction(function () use ($installment, $validated, $amount, $activeShift, $user) {
            $lockedInstallment = CustomerInstallment::lockForUpdate()->findOrFail($installment->id);
            $lockedCustomer = Customer::lockForUpdate()->findOrFail($lockedInstallment->customer_id);

            $invoice = null;
            if ($lockedInstallment->sales_invoice_id) {
                $invoice = SalesInvoice::lockForUpdate()->find($lockedInstallment->sales_invoice_id);
            }

            $paymentNumber = CustomerPayment::generatePaymentNumber();

            $payment = CustomerPayment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $lockedCustomer->id,
                'sales_invoice_id' => $invoice?->id,
                'customer_installment_id' => $lockedInstallment->id,
                'cashier_shift_id' => $activeShift?->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            // Update Installment status & paid amount
            $lockedInstallment->recordPayment($amount);

            // Update linked invoice if exists
            if ($invoice) {
                $newPaid = round((float) $invoice->paid_amount + $amount, 2);
                $newRemaining = max(0.00, round((float) $invoice->remaining_amount - $amount, 2));

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'payment_status' => $newRemaining <= 0.0001
                        ? 'paid'
                        : 'partial',
                ]);
            }

            // Decrement customer total debt
            $lockedCustomer->decrement('current_balance', $amount);

            // Record in active shift if paid cash
            if ($activeShift && $payment->payment_method === CustomerPayment::METHOD_CASH) {
                $activeShift->recordCustomerPayment($payment);
            }

            return $payment;
        });

        return back()->with('success', "تم تحصيل الدفعة بنجاح وتسجيل سند قبض برقم: {$payment->payment_number} بمبلغ {$amount} ج.م.");
    }

    public function destroy(CustomerInstallment $installment): RedirectResponse
    {
        if ((float) $installment->paid_amount > 0) {
            return back()->with('error', 'لا يمكن حذف قسط تم تحصيل جزء منه أو تحصيله بالكامل. يمكنك مراجعة سندات القبض المرتبطة به أولاً.');
        }

        $installment->delete();

        return back()->with('success', 'تم حذف القسط المجدول بنجاح.');
    }

    public function invoicesForCustomer(Customer $customer): JsonResponse
    {
        $invoices = SalesInvoice::where('customer_id', $customer->id)
            ->where('remaining_amount', '>', 0)
            ->where('status', SalesInvoice::STATUS_POSTED)
            ->orderByDesc('id')
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'paid_amount', 'remaining_amount']);

        return response()->json([
            'success' => true,
            'invoices' => $invoices,
        ]);
    }
}
