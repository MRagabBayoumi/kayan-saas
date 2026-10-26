<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\Treasury;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::with(['category', 'treasury', 'shift', 'creator'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->input('category_id'));
        }

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->input('treasury_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('from_date')) {
            $query->where('expense_date', '>=', Carbon::parse($request->input('from_date'))->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('expense_date', '<=', Carbon::parse($request->input('to_date'))->endOfDay());
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('expense_number', 'like', "%{$term}%")
                    ->orWhere('paid_to', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhere('reference_number', 'like', "%{$term}%");
            });
        }

        $expenses = $query->paginate(20)->withQueryString();

        $today = now()->toDateString();
        $thisMonthStart = now()->startOfMonth()->toDateString();

        $metrics = [
            'total_amount' => (float) (clone $query)->sum('amount'),
            'today_amount' => (float) Expense::where('expense_date', $today)->sum('amount'),
            'month_amount' => (float) Expense::where('expense_date', '>=', $thisMonthStart)->sum('amount'),
            'count' => (clone $query)->count(),
        ];

        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();
        $activeShift = CashierShift::getActiveShiftForUser(auth()->id());

        // Category breakdown for chart/summary
        $categoryBreakdown = Expense::join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total_amount'), DB::raw('COUNT(expenses.id) as count'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();

        return view('expenses.index', [
            'title' => 'المصروفات التشغيلية والعمومية',
            'expenses' => $expenses,
            'metrics' => $metrics,
            'categories' => $categories,
            'treasuries' => $treasuries,
            'activeShift' => $activeShift,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,card'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'pay_from_shift' => ['nullable', 'boolean'],
            'cashier_shift_id' => ['nullable', 'exists:cashier_shifts,id'],
            'paid_to' => ['nullable', 'string', 'max:150'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = (float) $validated['amount'];

        if (empty($validated['pay_from_shift']) && ! auth()->user()?->isAdmin() && ! auth()->user()?->can('manage-expenses')) {
            abort(403, 'غير مصرح لك بالصرف من الخزينة الرئيسية مباشرة دون صلاحيات مالية.');
        }

        DB::transaction(function () use ($validated, $amount) {
            $category = ExpenseCategory::findOrFail($validated['expense_category_id']);

            $activeShift = null;
            if (! empty($validated['pay_from_shift'])) {
                if (! empty($validated['cashier_shift_id'])) {
                    $activeShift = CashierShift::open()->find($validated['cashier_shift_id']);
                }
                if (! $activeShift) {
                    $activeShift = CashierShift::getActiveShiftForUser(auth()->id());
                }
                if (! $activeShift) {
                    throw new \InvalidArgumentException('لا توجد وردية كاشير نشطة ومفتوحة لك حالياً للصرف منها.');
                }
            }

            $treasury = null;
            if (empty($activeShift)) {
                if (! empty($validated['treasury_id'])) {
                    $treasury = Treasury::where('id', $validated['treasury_id'])->lockForUpdate()->first();
                } else {
                    $treasury = Treasury::getDefault();
                }

                if ($treasury && (float) $treasury->current_balance < $amount) {
                    throw new \InvalidArgumentException("رصيد الخزينة ({$treasury->name}) لا يكفي للصرف. الرصيد الحالي: ".number_format((float) $treasury->current_balance, 2).' ج.م');
                }
            }

            $expenseNumber = Expense::generateExpenseNumber();

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'expense_category_id' => $category->id,
                'treasury_id' => $treasury?->id,
                'cashier_shift_id' => $activeShift?->id,
                'amount' => $amount,
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'],
                'paid_to' => $validated['paid_to'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Deduct from Treasury
            if ($treasury) {
                $desc = "صرف مصروف [{$category->name}] برقم {$expenseNumber}".($expense->paid_to ? " للمستلم: {$expense->paid_to}" : '');
                $treasury->recordWithdrawal(
                    $amount,
                    'expense',
                    $expense->id,
                    $desc,
                    auth()->id(),
                    $validated['expense_date']
                );
            }

            // Deduct from Cashier Shift
            if ($activeShift) {
                $activeShift->recordExpense($expense);
            }

            // Record general ledger journal entry
            app(AccountingService::class)->recordExpense($expense);
        });

        return back()->with('success', 'تم تسجيل سند صرف المصروف بنجاح وتحديث حركة الخزينة');
    }

    public function voucher(Expense $expense, Request $request): View
    {
        $expense->load(['category', 'treasury', 'shift', 'creator']);
        $company = CompanySetting::first();
        $format = $request->query('format', 'a5');
        $title = 'إذن صرف مصروف - '.$expense->expense_number;

        return view('expenses.voucher', [
            'expense' => $expense,
            'company' => $company,
            'format' => $format,
            'title' => $title,
        ]);
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense) {
            $exp = Expense::where('id', $expense->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $exp->amount;

            // Refund treasury if deducted
            if ($exp->treasury_id && $exp->treasury) {
                $exp->treasury->recordDeposit(
                    $amount,
                    'manual',
                    $exp->id,
                    "إلغاء سند المصروف رقم {$exp->expense_number} واسترداد المبلغ للخزينة",
                    auth()->id(),
                    now()->toDateString()
                );
            }

            // Reverse general ledger journal entry before deletion
            app(AccountingService::class)->voidEntry(
                JournalEntry::REF_EXPENSE,
                $exp->id,
                'إلغاء وحذف سند المصروف',
                auth()->id()
            );

            $exp->delete();
        });

        return back()->with('success', 'تم إلغاء سند المصروف واسترداد المبلغ للخزينة بنجاح');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ExpenseCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'تمت إضافة بند المصروف بنجاح');
    }
}
