<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierInstallment;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierInstallmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupplierInstallment::with(['supplier', 'purchaseInvoice', 'creator', 'payments'])
            ->orderBy('due_date')
            ->orderBy('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
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

        $allPendingQuery = SupplierInstallment::pending();
        $totalDue = (float) (clone $allPendingQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');

        $overdueQuery = SupplierInstallment::overdue();
        $overdueDue = (float) (clone $overdueQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');
        $overdueCount = (clone $overdueQuery)->count();

        $dueSoonQuery = SupplierInstallment::dueSoon(7);
        $dueSoonDue = (float) (clone $dueSoonQuery)->selectRaw('SUM(amount - paid_amount) as remaining')->value('remaining');
        $dueSoonCount = (clone $dueSoonQuery)->count();

        $totalPaid = (float) SupplierInstallment::sum('paid_amount');

        $metrics = [
            'total_due' => $totalDue,
            'overdue_due' => $overdueDue,
            'overdue_count' => $overdueCount,
            'due_soon_due' => $dueSoonDue,
            'due_soon_count' => $dueSoonCount,
            'total_paid' => $totalPaid,
            'pending_count' => SupplierInstallment::pending()->count(),
            'paid_count' => SupplierInstallment::paid()->count(),
        ];

        $suppliers = Supplier::orderBy('name')->get();
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();

        return view('suppliers.installments.index', [
            'title' => 'جدول أقساط ومستحقات الموردين المجدولة',
            'installments' => $installments,
            'metrics' => $metrics,
            'suppliers' => $suppliers,
            'treasuries' => $treasuries,
            'currentFilter' => $filter,
            'selectedSupplierId' => $request->input('supplier_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
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
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        $invoice = null;
        if (! empty($validated['purchase_invoice_id'])) {
            $invoice = PurchaseInvoice::where('id', $validated['purchase_invoice_id'])
                ->where('supplier_id', $supplier->id)
                ->firstOrFail();
        }

        $planType = $validated['plan_type'] ?? 'single';

        if ($planType === 'schedule') {
            $request->validate([
                'total_amount' => ['required', 'numeric', 'min:0.01'],
                'installments_count' => ['required', 'integer', 'min:2', 'max:36'],
                'first_due_date' => ['required', 'date'],
                'interval' => ['required', 'in:weekly,biweekly,monthly'],
            ]);

            $totalAmount = (float) $validated['total_amount'];
            $count = (int) $validated['installments_count'];
            $baseAmount = round($totalAmount / $count, 2);
            $startDate = Carbon::parse($validated['first_due_date']);
            $interval = $validated['interval'];

            DB::transaction(function () use ($supplier, $invoice, $count, $baseAmount, $totalAmount, $startDate, $interval, $validated) {
                $accumulated = 0.00;

                for ($i = 1; $i <= $count; $i++) {
                    $installmentDate = clone $startDate;

                    if ($i > 1) {
                        if ($interval === 'weekly') {
                            $installmentDate->addWeeks($i - 1);
                        } elseif ($interval === 'biweekly') {
                            $installmentDate->addWeeks(($i - 1) * 2);
                        } else {
                            $installmentDate->addMonthsNoOverflow($i - 1);
                        }
                    }

                    // Handle round pennies on the last installment
                    $installmentAmount = ($i === $count)
                        ? round($totalAmount - $accumulated, 2)
                        : $baseAmount;

                    $accumulated += $installmentAmount;

                    $title = filled($validated['installment_title'] ?? null)
                        ? trim($validated['installment_title']).' (قسط '.$i.'/'.$count.')'
                        : ($invoice
                            ? 'قسط '.$i.'/'.$count.' - فاتورة #'.$invoice->invoice_number
                            : 'قسط مجدول '.$i.'/'.$count);

                    SupplierInstallment::create([
                        'supplier_id' => $supplier->id,
                        'purchase_invoice_id' => $invoice?->id,
                        'installment_title' => $title,
                        'due_date' => $installmentDate->toDateString(),
                        'amount' => $installmentAmount,
                        'paid_amount' => 0.00,
                        'status' => SupplierInstallment::STATUS_PENDING,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            });

            return back()->with('success', "تم إنشاء جدول سداد يتضمن {$count} أقساط مجدولة بنجاح.");
        }

        // Single installment
        $request->validate([
            'installment_title' => ['required', 'string', 'max:100'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        SupplierInstallment::create([
            'supplier_id' => $supplier->id,
            'purchase_invoice_id' => $invoice?->id,
            'installment_title' => trim($validated['installment_title']),
            'due_date' => $validated['due_date'],
            'amount' => (float) $validated['amount'],
            'paid_amount' => 0.00,
            'status' => SupplierInstallment::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم تسجيل القسط المستحق في جدول السداد بنجاح.');
    }

    public function destroy(SupplierInstallment $installment): RedirectResponse
    {
        if ((float) $installment->paid_amount > 0) {
            return back()->with('error', 'لا يمكن حذف قسط تم سداد جزء منه أو سداده بالكامل. يمكنك مراجعة سندات الصرف المرتبطة به أولاً.');
        }

        $installment->delete();

        return back()->with('success', 'تم حذف القسط المجدول بنجاح.');
    }
}
