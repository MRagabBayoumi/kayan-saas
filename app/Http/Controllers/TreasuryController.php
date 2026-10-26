<?php

namespace App\Http\Controllers;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\TreasuryTransfer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TreasuryController extends Controller
{
    public function index(): View
    {
        $treasuries = Treasury::withCount('transactions')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $totalCash = (float) $treasuries->where('type', Treasury::TYPE_CASH)->sum('current_balance');
        $totalBank = (float) $treasuries->where('type', Treasury::TYPE_BANK)->sum('current_balance');
        $totalWallet = (float) $treasuries->where('type', Treasury::TYPE_WALLET)->sum('current_balance');
        $totalLiquidity = $totalCash + $totalBank + $totalWallet;

        $recentTransactions = TreasuryTransaction::with(['treasury', 'creator'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $recentTransfers = TreasuryTransfer::with(['fromTreasury', 'toTreasury', 'creator'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('treasuries.index', [
            'title' => 'الخزائن والحسابات البنكية',
            'treasuries' => $treasuries,
            'totalCash' => $totalCash,
            'totalBank' => $totalBank,
            'totalWallet' => $totalWallet,
            'totalLiquidity' => $totalLiquidity,
            'recentTransactions' => $recentTransactions,
            'recentTransfers' => $recentTransfers,
        ]);
    }

    public function show(Treasury $treasury, Request $request): View
    {
        $query = $treasury->transactions()->with('creator');

        if ($request->filled('type') && in_array($request->input('type'), [TreasuryTransaction::TYPE_DEPOSIT, TreasuryTransaction::TYPE_WITHDRAWAL], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('from_date')) {
            $query->where('transaction_date', '>=', Carbon::parse($request->input('from_date'))->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('transaction_date', '<=', Carbon::parse($request->input('to_date'))->endOfDay());
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('transaction_number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $transactions = $query->paginate(25)->withQueryString();

        $totalDeposits = (float) (clone $query)->where('type', TreasuryTransaction::TYPE_DEPOSIT)->sum('amount');
        $totalWithdrawals = (float) (clone $query)->where('type', TreasuryTransaction::TYPE_WITHDRAWAL)->sum('amount');

        return view('treasuries.show', [
            'title' => 'كشف حركة: '.$treasury->name,
            'treasury' => $treasury,
            'transactions' => $transactions,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'allTreasuries' => Treasury::where('id', '!=', $treasury->id)->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:cash,bank,wallet'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0.00);

        DB::transaction(function () use ($validated, $openingBalance) {
            $isDefault = ! empty($validated['is_default']);

            if ($isDefault) {
                Treasury::where('is_default', true)->update(['is_default' => false]);
            }

            $treasury = Treasury::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'is_default' => $isDefault,
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if ($openingBalance > 0) {
                TreasuryTransaction::create([
                    'transaction_number' => TreasuryTransaction::generateNumber(),
                    'treasury_id' => $treasury->id,
                    'type' => TreasuryTransaction::TYPE_DEPOSIT,
                    'amount' => $openingBalance,
                    'balance_after' => $openingBalance,
                    'transaction_date' => now()->toDateString(),
                    'source_type' => 'opening_balance',
                    'source_id' => $treasury->id,
                    'description' => 'رصيد افتتاحي عند إنشاء الخزينة / الحساب',
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('treasuries.index')->with('success', 'تم إنشاء الخزينة / الحساب البنكي بنجاح');
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_treasury_id' => ['required', 'exists:treasuries,id'],
            'to_treasury_id' => ['required', 'exists:treasuries,id', 'different:from_treasury_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($validated, $amount) {
            $from = Treasury::where('id', $validated['from_treasury_id'])->lockForUpdate()->firstOrFail();
            $to = Treasury::where('id', $validated['to_treasury_id'])->lockForUpdate()->firstOrFail();

            if ((float) $from->current_balance < $amount) {
                throw new \InvalidArgumentException("رصيد الخزينة المصدر ({$from->name}) غير كافٍ لإتمام التحويل. الرصيد الحالي: ".number_format((float) $from->current_balance, 2).' ج.م');
            }

            $transferNumber = TreasuryTransfer::generateNumber();

            $transfer = TreasuryTransfer::create([
                'transfer_number' => $transferNumber,
                'from_treasury_id' => $from->id,
                'to_treasury_id' => $to->id,
                'amount' => $amount,
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Withdrawal from sender
            $from->recordWithdrawal(
                $amount,
                'transfer_out',
                $transfer->id,
                "تحويل صادر إلى [{$to->name}] (إذن تحويل رقم: {$transferNumber})",
                auth()->id(),
                $validated['transfer_date']
            );

            // Deposit to receiver
            $to->recordDeposit(
                $amount,
                'transfer_in',
                $transfer->id,
                "تحويل وارد من [{$from->name}] (إذن تحويل رقم: {$transferNumber})",
                auth()->id(),
                $validated['transfer_date']
            );
        });

        return back()->with('success', 'تم تنفيذ التحويل الداخلي بين الخزائن بنجاح وتحديث الأرصدة');
    }

    public function manualTransaction(Request $request, Treasury $treasury): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:deposit,withdrawal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $amount = (float) $validated['amount'];

        DB::transaction(function () use ($treasury, $validated, $amount) {
            if ($validated['type'] === TreasuryTransaction::TYPE_WITHDRAWAL) {
                if ((float) $treasury->current_balance < $amount) {
                    throw new \InvalidArgumentException("رصيد الخزينة الحالي ({$treasury->current_balance} ج.م) غير كافٍ للصرف.");
                }
                $treasury->recordWithdrawal(
                    $amount,
                    'manual',
                    null,
                    $validated['description'],
                    auth()->id(),
                    $validated['transaction_date']
                );
            } else {
                $treasury->recordDeposit(
                    $amount,
                    'manual',
                    null,
                    $validated['description'],
                    auth()->id(),
                    $validated['transaction_date']
                );
            }
        });

        return back()->with('success', 'تم تسجيل الحركة النقدية وتحديث الرصيد بنجاح');
    }
}
