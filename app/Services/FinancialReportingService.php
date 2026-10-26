<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class FinancialReportingService
{
    /**
     * Generate a comprehensive Trial Balance (ميزان المراجعة بالأرصدة والمجاميع).
     *
     * @return array{
     *     accounts: array<int, array<string, mixed>>,
     *     totals: array{
     *         opening_debit: float,
     *         opening_credit: float,
     *         period_debit: float,
     *         period_credit: float,
     *         closing_debit: float,
     *         closing_credit: float,
     *         diff: float,
     *         is_balanced: bool
     *     },
     *     filters: array{
     *         date_from: ?string,
     *         date_to: ?string,
     *         level: ?int,
     *         show_zero: bool
     *     }
     * }
     */
    public function getTrialBalance(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $level = null,
        bool $showZero = false
    ): array {
        $allAccounts = Account::orderBy('code')->get();

        // 1. Preload debit and credit aggregates per leaf account for performance
        $openingQuery = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED);

        if ($dateFrom) {
            $openingQuery->whereDate('journal_entries.entry_date', '<', $dateFrom);
        } else {
            // No dateFrom means opening is 0
            $openingQuery->whereRaw('1 = 0');
        }

        $openingBalances = $openingQuery
            ->select('journal_entry_lines.account_id')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $periodQuery = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED);

        if ($dateFrom) {
            $periodQuery->whereDate('journal_entries.entry_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $periodQuery->whereDate('journal_entries.entry_date', '<=', $dateTo);
        }

        $periodBalances = $periodQuery
            ->select('journal_entry_lines.account_id')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        // 2. Map and roll up balances for each account (both leaves and parent groups)
        $mapped = [];
        $totalOpeningDebit = 0.0;
        $totalOpeningCredit = 0.0;
        $totalPeriodDebit = 0.0;
        $totalPeriodCredit = 0.0;
        $totalClosingDebit = 0.0;
        $totalClosingCredit = 0.0;

        foreach ($allAccounts as $account) {
            if ($account->is_leaf) {
                $opDeb = (float) ($openingBalances->get($account->id)?->total_debit ?? 0.0);
                $opCred = (float) ($openingBalances->get($account->id)?->total_credit ?? 0.0);

                $perDeb = (float) ($periodBalances->get($account->id)?->total_debit ?? 0.0);
                $perCred = (float) ($periodBalances->get($account->id)?->total_credit ?? 0.0);
            } else {
                // Sum descendants for parent accounts
                $descendantIds = $account->getAllDescendantIds();
                $opDeb = 0.0;
                $opCred = 0.0;
                $perDeb = 0.0;
                $perCred = 0.0;

                foreach ($descendantIds as $dId) {
                    $opDeb += (float) ($openingBalances->get($dId)?->total_debit ?? 0.0);
                    $opCred += (float) ($openingBalances->get($dId)?->total_credit ?? 0.0);
                    $perDeb += (float) ($periodBalances->get($dId)?->total_debit ?? 0.0);
                    $perCred += (float) ($periodBalances->get($dId)?->total_credit ?? 0.0);
                }
            }

            // Calculate opening net
            $opNet = $opDeb - $opCred;
            $openingDebit = $opNet > 0 ? $opNet : 0.0;
            $openingCredit = $opNet < 0 ? abs($opNet) : 0.0;

            // Cumulative closing net
            $totDeb = $opDeb + $perDeb;
            $totCred = $opCred + $perCred;
            $closeNet = $totDeb - $totCred;

            $closingDebit = $closeNet > 0 ? round($closeNet, 2) : 0.0;
            $closingCredit = $closeNet < 0 ? round(abs($closeNet), 2) : 0.0;

            $hasActivity = ($openingDebit > 0 || $openingCredit > 0 || $perDeb > 0 || $perCred > 0 || $closingDebit > 0 || $closingCredit > 0);

            if (! $showZero && ! $hasActivity) {
                continue;
            }

            if ($level && $account->level > $level) {
                continue;
            }

            $entry = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'type_label' => $account->type_label,
                'nature' => $account->nature,
                'level' => $account->level,
                'is_leaf' => $account->is_leaf,
                'opening_debit' => round($openingDebit, 2),
                'opening_credit' => round($openingCredit, 2),
                'period_debit' => round($perDeb, 2),
                'period_credit' => round($perCred, 2),
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
            ];

            $mapped[] = $entry;

            // Only sum root level 1 accounts (or leaves) to avoid double counting in global totals
            if ($account->level === 1) {
                $totalOpeningDebit += $openingDebit;
                $totalOpeningCredit += $openingCredit;
                $totalPeriodDebit += $perDeb;
                $totalPeriodCredit += $perCred;
                $totalClosingDebit += $closingDebit;
                $totalClosingCredit += $closingCredit;
            }
        }

        $diff = abs(round($totalClosingDebit - $totalClosingCredit, 2));

        return [
            'accounts' => $mapped,
            'totals' => [
                'opening_debit' => round($totalOpeningDebit, 2),
                'opening_credit' => round($totalOpeningCredit, 2),
                'period_debit' => round($totalPeriodDebit, 2),
                'period_credit' => round($totalPeriodCredit, 2),
                'closing_debit' => round($totalClosingDebit, 2),
                'closing_credit' => round($totalClosingCredit, 2),
                'diff' => $diff,
                'is_balanced' => $diff < 0.01,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'level' => $level,
                'show_zero' => $showZero,
            ],
        ];
    }

    /**
     * Generate Income Statement (قائمة الدخل / تقرير الأرباح والخسائر).
     *
     * @return array{
     *     operating_revenues: array<int, array<string, mixed>>,
     *     total_operating_revenues: float,
     *     sales_deductions: array<int, array<string, mixed>>,
     *     total_sales_deductions: float,
     *     net_revenue: float,
     *     cogs: array<int, array<string, mixed>>,
     *     total_cogs: float,
     *     gross_profit: float,
     *     gross_margin_pct: float,
     *     operating_expenses: array<int, array<string, mixed>>,
     *     total_operating_expenses: float,
     *     operating_profit: float,
     *     other_revenues_expenses: array<int, array<string, mixed>>,
     *     total_other: float,
     *     net_profit: float,
     *     filters: array{date_from: ?string, date_to: ?string}
     * }
     */
    public function getIncomeStatement(?string $dateFrom = null, ?string $dateTo = null): array
    {
        // 1. Fetch all Revenue (type=revenue) and Expense (type=expense) leaf accounts
        $accounts = Account::whereIn('type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
            ->where('is_leaf', true)
            ->orderBy('code')
            ->get();

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereIn('journal_entry_lines.account_id', $accounts->pluck('id'));

        if ($dateFrom) {
            $query->whereDate('journal_entries.entry_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('journal_entries.entry_date', '<=', $dateTo);
        }

        $balances = $query
            ->select('journal_entry_lines.account_id')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $grossSales = [];
        $salesDeductions = [];
        $cogs = [];
        $operatingExpenses = [];
        $otherItems = [];

        $totalGrossSales = 0.0;
        $totalSalesDeductions = 0.0;
        $totalCogs = 0.0;
        $totalOperatingExpenses = 0.0;
        $totalOther = 0.0;

        foreach ($accounts as $acc) {
            $deb = (float) ($balances->get($acc->id)?->total_debit ?? 0.0);
            $cred = (float) ($balances->get($acc->id)?->total_credit ?? 0.0);

            if ($deb == 0 && $cred == 0) {
                continue;
            }

            // Group according to system roles and code prefixes
            if ($acc->code === '4101' || $acc->system_role === 'sales_revenue') {
                $amount = round($cred - $deb, 2);
                $grossSales[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount];
                $totalGrossSales += $amount;
            } elseif (in_array($acc->code, ['4102', '4103']) || in_array($acc->system_role, ['sales_returns', 'sales_discounts'])) {
                // Contra-revenue accounts: debit nature increases deduction
                $amount = round($deb - $cred, 2);
                $salesDeductions[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount];
                $totalSalesDeductions += $amount;
            } elseif ($acc->code === '5101' || $acc->system_role === 'cost_of_goods_sold' || str_starts_with($acc->code, '51')) {
                $amount = round($deb - $cred, 2);
                $cogs[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount];
                $totalCogs += $amount;
            } elseif ($acc->code === '5401' || $acc->system_role === 'purchase_discounts') {
                // Purchase discount received (credit nature income)
                $amount = round($cred - $deb, 2);
                $otherItems[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount, 'is_income' => true];
                $totalOther += $amount;
            } elseif ($acc->type === Account::TYPE_REVENUE) {
                // Other revenues (e.g. 4201 shipping services)
                $amount = round($cred - $deb, 2);
                $grossSales[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount];
                $totalGrossSales += $amount;
            } elseif ($acc->type === Account::TYPE_EXPENSE) {
                // General & operating expenses (52, 53)
                $amount = round($deb - $cred, 2);
                $operatingExpenses[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'amount' => $amount];
                $totalOperatingExpenses += $amount;
            }
        }

        $netRevenue = round($totalGrossSales - $totalSalesDeductions, 2);
        $grossProfit = round($netRevenue - $totalCogs, 2);
        $grossMarginPct = $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 2) : 0.0;
        $operatingProfit = round($grossProfit - $totalOperatingExpenses, 2);
        $netProfit = round($operatingProfit + $totalOther, 2);

        return [
            'gross_sales' => $grossSales,
            'total_gross_sales' => round($totalGrossSales, 2),
            'sales_deductions' => $salesDeductions,
            'total_sales_deductions' => round($totalSalesDeductions, 2),
            'net_revenue' => $netRevenue,
            'cogs' => $cogs,
            'total_cogs' => round($totalCogs, 2),
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            'operating_expenses' => $operatingExpenses,
            'total_operating_expenses' => round($totalOperatingExpenses, 2),
            'operating_profit' => $operatingProfit,
            'other_items' => $otherItems,
            'total_other' => round($totalOther, 2),
            'net_profit' => $netProfit,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ];
    }

    /**
     * Generate Balance Sheet (الميزانية العمومية / قائمة المركز المالي).
     *
     * @return array{
     *     current_assets: array<int, array<string, mixed>>,
     *     total_current_assets: float,
     *     non_current_assets: array<int, array<string, mixed>>,
     *     total_non_current_assets: float,
     *     total_assets: float,
     *     current_liabilities: array<int, array<string, mixed>>,
     *     total_current_liabilities: float,
     *     non_current_liabilities: array<int, array<string, mixed>>,
     *     total_non_current_liabilities: float,
     *     total_liabilities: float,
     *     equity_items: array<int, array<string, mixed>>,
     *     retained_earnings: float,
     *     current_period_net_profit: float,
     *     total_equity: float,
     *     total_liabilities_and_equity: float,
     *     diff: float,
     *     is_balanced: bool,
     *     as_of_date: string
     * }
     */
    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();

        // 1. Fetch balance sheet accounts (Assets, Liabilities, Equity)
        $accounts = Account::whereIn('type', [
            Account::TYPE_ASSET,
            Account::TYPE_LIABILITY,
            Account::TYPE_EQUITY,
        ])
            ->where('is_leaf', true)
            ->orderBy('code')
            ->get();

        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereDate('journal_entries.entry_date', '<=', $asOfDate)
            ->whereIn('journal_entry_lines.account_id', $accounts->pluck('id'));

        $balances = $query
            ->select('journal_entry_lines.account_id')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $currentAssets = [];
        $nonCurrentAssets = [];
        $totalCurrentAssets = 0.0;
        $totalNonCurrentAssets = 0.0;

        $currentLiabilities = [];
        $nonCurrentLiabilities = [];
        $totalCurrentLiabilities = 0.0;
        $totalNonCurrentLiabilities = 0.0;

        $equityItems = [];
        $totalEquityWithoutIncome = 0.0;
        $retainedEarnings = 0.0;

        foreach ($accounts as $acc) {
            $deb = (float) ($balances->get($acc->id)?->total_debit ?? 0.0);
            $cred = (float) ($balances->get($acc->id)?->total_credit ?? 0.0);

            if ($acc->type === Account::TYPE_ASSET) {
                // Debit nature: balance = debit - credit
                $balance = round($deb - $cred, 2);
                if ($balance == 0) {
                    continue;
                }

                $item = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                if (str_starts_with($acc->code, '11')) {
                    $currentAssets[] = $item;
                    $totalCurrentAssets += $balance;
                } else {
                    $nonCurrentAssets[] = $item;
                    $totalNonCurrentAssets += $balance;
                }
            } elseif ($acc->type === Account::TYPE_LIABILITY) {
                // Credit nature: balance = credit - debit
                $balance = round($cred - $deb, 2);
                if ($balance == 0) {
                    continue;
                }

                $item = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                if (str_starts_with($acc->code, '21')) {
                    $currentLiabilities[] = $item;
                    $totalCurrentLiabilities += $balance;
                } else {
                    $nonCurrentLiabilities[] = $item;
                    $totalNonCurrentLiabilities += $balance;
                }
            } elseif ($acc->type === Account::TYPE_EQUITY) {
                // Credit nature: balance = credit - debit
                $balance = round($cred - $deb, 2);

                if ($acc->code === '3102' || $acc->system_role === 'retained_earnings') {
                    $retainedEarnings = $balance;
                }

                if ($balance != 0) {
                    $equityItems[] = ['id' => $acc->id, 'code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalEquityWithoutIncome += $balance;
                }
            }
        }

        // 2. Calculate Current Period Net Income up to the as_of_date
        $incomeStatement = $this->getIncomeStatement(null, $asOfDate);
        $currentPeriodNetProfit = (float) ($incomeStatement['net_profit'] ?? 0.0);

        $totalAssets = round($totalCurrentAssets + $totalNonCurrentAssets, 2);
        $totalLiabilities = round($totalCurrentLiabilities + $totalNonCurrentLiabilities, 2);
        $totalEquity = round($totalEquityWithoutIncome + $currentPeriodNetProfit, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);

        $diff = abs(round($totalAssets - $totalLiabilitiesAndEquity, 2));

        return [
            'current_assets' => $currentAssets,
            'total_current_assets' => round($totalCurrentAssets, 2),
            'non_current_assets' => $nonCurrentAssets,
            'total_non_current_assets' => round($totalNonCurrentAssets, 2),
            'total_assets' => $totalAssets,
            'current_liabilities' => $currentLiabilities,
            'total_current_liabilities' => round($totalCurrentLiabilities, 2),
            'non_current_liabilities' => $nonCurrentLiabilities,
            'total_non_current_liabilities' => round($totalNonCurrentLiabilities, 2),
            'total_liabilities' => $totalLiabilities,
            'equity_items' => $equityItems,
            'retained_earnings' => $retainedEarnings,
            'current_period_net_profit' => $currentPeriodNetProfit,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'diff' => $diff,
            'is_balanced' => $diff < 0.01,
            'as_of_date' => $asOfDate,
        ];
    }

    /**
     * Generate an Account General Ledger Statement (كشف حساب الأستاذ العام).
     *
     * @return array{
     *     account: Account,
     *     opening_balance: float,
     *     opening_nature: string,
     *     lines: array<int, array<string, mixed>>,
     *     total_debit: float,
     *     total_credit: float,
     *     closing_balance: float,
     *     closing_nature: string,
     *     filters: array{date_from: ?string, date_to: ?string}
     * }
     */
    public function getAccountLedger(Account $account, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $targetAccountIds = $account->is_leaf ? [$account->id] : array_merge([$account->id], $account->getAllDescendantIds());

        // 1. Calculate opening balance before $dateFrom
        $openingBalance = 0.0;
        if ($dateFrom) {
            $opTotals = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
                ->whereDate('journal_entries.entry_date', '<', $dateFrom)
                ->whereIn('journal_entry_lines.account_id', $targetAccountIds)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit, COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
                ->first();

            $opDeb = (float) ($opTotals->total_debit ?? 0);
            $opCred = (float) ($opTotals->total_credit ?? 0);

            $openingBalance = $account->nature === Account::NATURE_CREDIT
                ? round($opCred - $opDeb, 2)
                : round($opDeb - $opCred, 2);
        }

        // 2. Fetch lines within period
        $query = JournalEntryLine::with(['journalEntry', 'account', 'customer', 'supplier'])
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', JournalEntry::STATUS_POSTED);
                if ($dateFrom) {
                    $q->whereDate('entry_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('entry_date', '<=', $dateTo);
                }
            })
            ->whereIn('account_id', $targetAccountIds)
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->orderBy('journal_entry_lines.id', 'asc')
            ->select('journal_entry_lines.*');

        $lines = $query->get();

        $runningBalance = $openingBalance;
        $mappedLines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $deb = (float) $line->debit;
            $cred = (float) $line->credit;
            $totalDebit += $deb;
            $totalCredit += $cred;

            if ($account->nature === Account::NATURE_CREDIT) {
                $runningBalance += ($cred - $deb);
            } else {
                $runningBalance += ($deb - $cred);
            }

            $mappedLines[] = [
                'id' => $line->id,
                'entry_id' => $line->journalEntry->id,
                'entry_number' => $line->journalEntry->entry_number,
                'entry_date' => $line->journalEntry->entry_date->format('Y-m-d'),
                'description' => $line->description ?: $line->journalEntry->description,
                'reference_type' => $line->journalEntry->reference_type,
                'reference_number' => $line->journalEntry->reference_number,
                'party_name' => $line->customer?->name ?: ($line->supplier?->name ?: null),
                'debit' => $deb,
                'credit' => $cred,
                'running_balance' => round($runningBalance, 2),
            ];
        }

        $closingBalance = round($runningBalance, 2);

        return [
            'account' => $account,
            'opening_balance' => $openingBalance,
            'opening_nature' => $openingBalance >= 0 ? $account->nature : ($account->nature === Account::NATURE_DEBIT ? Account::NATURE_CREDIT : Account::NATURE_DEBIT),
            'lines' => $mappedLines,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'closing_balance' => $closingBalance,
            'closing_nature' => $closingBalance >= 0 ? $account->nature : ($account->nature === Account::NATURE_DEBIT ? Account::NATURE_CREDIT : Account::NATURE_DEBIT),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ];
    }
}
