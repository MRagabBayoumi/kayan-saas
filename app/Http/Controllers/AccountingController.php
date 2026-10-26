<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingController extends Controller
{
    /**
     * Display the Chart of Accounts tree and summary metrics.
     */
    public function chartOfAccounts(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $typeFilter = $request->input('type');

        // Fetch top-level root accounts (level 1) with their nested children
        $query = Account::with([
            'children' => function ($q) {
                $q->with([
                    'children' => function ($q2) {
                        $q2->with('children');
                    },
                ]);
            },
        ])->whereNull('parent_id')->orderBy('code');

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        $rootAccounts = $query->get();

        // Calculate high-level financial health indicators
        $totalAssets = (float) Account::where('code', '1')->value('current_balance') ?? 0.0;
        $totalLiabilities = (float) Account::where('code', '2')->value('current_balance') ?? 0.0;
        $totalEquity = (float) Account::where('code', '3')->value('current_balance') ?? 0.0;
        $totalRevenues = (float) Account::where('code', '4')->value('current_balance') ?? 0.0;
        $totalExpenses = (float) Account::where('code', '5')->value('current_balance') ?? 0.0;
        $netIncome = round($totalRevenues - $totalExpenses, 2);

        // Eligible parents for adding new sub-accounts
        $eligibleParents = Account::where('level', '<', 4)->orderBy('code')->get();

        return view('accounting.chart-of-accounts', [
            'title' => 'دليل الحسابات الشجري (Chart of Accounts)',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'rootAccounts' => $rootAccounts,
            'eligibleParents' => $eligibleParents,
            'metrics' => [
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'total_revenues' => $totalRevenues,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'total_accounts_count' => Account::count(),
            ],
            'currentSearch' => $search,
            'currentType' => $typeFilter,
        ]);
    }

    /**
     * Display paginated General Ledger Journal Entries.
     */
    public function journalEntries(Request $request): View
    {
        $query = JournalEntry::with(['creator', 'lines.account', 'lines.customer', 'lines.supplier']);

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('entry_number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('lines.account', fn ($sq) => $sq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->input('reference_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->input('date_to'));
        }

        $entries = $query->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $metrics = [
            'total_entries' => JournalEntry::count(),
            'total_posted' => JournalEntry::where('status', JournalEntry::STATUS_POSTED)->count(),
            'sum_debit' => (float) JournalEntry::where('status', JournalEntry::STATUS_POSTED)->sum('total_debit'),
            'sum_credit' => (float) JournalEntry::where('status', JournalEntry::STATUS_POSTED)->sum('total_credit'),
        ];

        return view('accounting.journal-entries', [
            'title' => 'دفتر قيود اليومية العامة (General Journal)',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'entries' => $entries,
            'metrics' => $metrics,
            'currentSearch' => $request->input('search'),
            'currentRefType' => $request->input('reference_type'),
            'currentStatus' => $request->input('status'),
            'currentDateFrom' => $request->input('date_from'),
            'currentDateTo' => $request->input('date_to'),
        ]);
    }

    /**
     * Display a dedicated view page for an individual journal entry voucher.
     */
    public function journalEntryShow(Request $request, JournalEntry $entry): View|JsonResponse
    {
        $entry->load(['creator', 'lines.account', 'lines.customer', 'lines.supplier']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'entry' => $entry,
            ]);
        }

        $company = CompanySetting::first();

        return view('accounting.journal-entries.show', [
            'title' => 'سند قيد يومية: '.$entry->entry_number,
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'entry' => $entry,
            'company' => $company,
        ]);
    }

    /**
     * Display an official printable voucher for a journal entry.
     */
    public function journalEntryPrint(Request $request, JournalEntry $entry): View
    {
        $entry->load(['creator', 'lines.account', 'lines.customer', 'lines.supplier']);
        $company = CompanySetting::first();
        $format = $request->input('format', 'a4');

        return view('accounting.journal-entries.print', [
            'title' => 'طباعة سند قيد يومية - '.$entry->entry_number,
            'entry' => $entry,
            'company' => $company,
            'format' => $format,
        ]);
    }

    /**
     * Store a newly created custom sub-account in the chart of accounts.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['required', 'exists:accounts,id'],
            'name' => ['required', 'string', 'max:150'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30', 'unique:accounts,code'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $parent = Account::findOrFail((int) $validated['parent_id']);

        // Determine sub-code if not manually entered
        $code = $validated['code'];
        if (blank($code)) {
            $siblingCount = Account::where('parent_id', $parent->id)->count();
            $nextSeq = str_pad((string) ($siblingCount + 1), 2, '0', STR_PAD_LEFT);
            $code = $parent->code.$nextSeq;

            // In case collision, increment
            $counter = 1;
            while (Account::where('code', $code)->exists()) {
                $code = $parent->code.str_pad((string) ($siblingCount + 1 + $counter), 2, '0', STR_PAD_LEFT);
                $counter++;
            }
        }

        // Parent becomes branch (not leaf)
        if ($parent->is_leaf) {
            $parent->update(['is_leaf' => false]);
        }

        Account::create([
            'code' => $code,
            'name' => $validated['name'],
            'name_en' => $validated['name_en'] ?? null,
            'parent_id' => $parent->id,
            'type' => $parent->type,
            'nature' => $parent->nature,
            'level' => $parent->level + 1,
            'is_leaf' => true,
            'is_system' => false,
            'is_active' => true,
            'description' => $validated['description'] ?? null,
            'current_balance' => 0.0,
        ]);

        return back()->with('status', 'success')->with('message', "تمت إضافة الحساب الفرعي الجديد '{$validated['name']}' بالكود [{$code}] بنجاح.");
    }

    /**
     * Recalculate all balances in the Chart of Accounts tree.
     */
    public function recalculateBalances(AccountingService $accountingService): RedirectResponse
    {
        $accountingService->recalculateAllBalances();

        return back()->with('status', 'success')->with('message', 'تمت إعادة احتساب أرصدة شجرة الحسابات بالكامل وتحديث مجاميع الفروع.');
    }
}
