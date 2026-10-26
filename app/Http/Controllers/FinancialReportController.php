<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CompanySetting;
use App\Services\FinancialReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    /**
     * Display the Trial Balance report (ميزان المراجعة بالأرصدة والمجاميع).
     */
    public function trialBalance(Request $request, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to') ?: now()->toDateString();
        $level = $request->filled('level') ? (int) $request->input('level') : null;
        $showZero = $request->boolean('show_zero', false);

        $reportData = $reportingService->getTrialBalance($dateFrom, $dateTo, $level, $showZero);

        return view('accounting.reports.trial-balance', [
            'title' => 'ميزان المراجعة (Trial Balance)',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'report' => $reportData,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
            'currentLevel' => $level,
            'currentShowZero' => $showZero,
        ]);
    }

    /**
     * Dedicated printable view of the Trial Balance report.
     */
    public function trialBalancePrint(Request $request, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to') ?: now()->toDateString();
        $level = $request->filled('level') ? (int) $request->input('level') : null;
        $showZero = $request->boolean('show_zero', false);

        $reportData = $reportingService->getTrialBalance($dateFrom, $dateTo, $level, $showZero);
        $company = CompanySetting::first();

        return view('accounting.reports.trial-balance-print', [
            'title' => 'طباعة ميزان المراجعة',
            'report' => $reportData,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }

    /**
     * Display the Income Statement report (قائمة الدخل / الأرباح والخسائر).
     */
    public function incomeStatement(Request $request, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from') ?: now()->startOfYear()->toDateString();
        $dateTo = $request->input('date_to') ?: now()->toDateString();

        $reportData = $reportingService->getIncomeStatement($dateFrom, $dateTo);

        return view('accounting.reports.income-statement', [
            'title' => 'قائمة الدخل والأرباح والخسائر (Income Statement)',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'report' => $reportData,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ]);
    }

    /**
     * Dedicated printable view of the Income Statement report.
     */
    public function incomeStatementPrint(Request $request, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from') ?: now()->startOfYear()->toDateString();
        $dateTo = $request->input('date_to') ?: now()->toDateString();

        $reportData = $reportingService->getIncomeStatement($dateFrom, $dateTo);
        $company = CompanySetting::first();

        return view('accounting.reports.income-statement-print', [
            'title' => 'طباعة قائمة الدخل والأرباح والخسائر',
            'report' => $reportData,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }

    /**
     * Display the Balance Sheet report (الميزانية العمومية / قائمة المركز المالي).
     */
    public function balanceSheet(Request $request, FinancialReportingService $reportingService): View
    {
        $asOfDate = $request->input('as_of_date') ?: now()->toDateString();

        $reportData = $reportingService->getBalanceSheet($asOfDate);

        return view('accounting.reports.balance-sheet', [
            'title' => 'الميزانية العمومية والمركز المالي (Balance Sheet)',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'report' => $reportData,
            'currentAsOfDate' => $asOfDate,
        ]);
    }

    /**
     * Dedicated printable view of the Balance Sheet report.
     */
    public function balanceSheetPrint(Request $request, FinancialReportingService $reportingService): View
    {
        $asOfDate = $request->input('as_of_date') ?: now()->toDateString();

        $reportData = $reportingService->getBalanceSheet($asOfDate);
        $company = CompanySetting::first();

        return view('accounting.reports.balance-sheet-print', [
            'title' => 'طباعة الميزانية العمومية',
            'report' => $reportData,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }

    /**
     * Display an individual Account General Ledger statement (كشف حساب الأستاذ العام).
     */
    public function accountLedger(Request $request, Account $account, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to') ?: now()->toDateString();

        $ledgerData = $reportingService->getAccountLedger($account, $dateFrom, $dateTo);

        return view('accounting.reports.account-ledger', [
            'title' => 'كشف حساب الأستاذ: '.$account->name.' ['.$account->code.']',
            'moduleName' => 'الإدارة المالية والحسابات العامة',
            'ledger' => $ledgerData,
            'account' => $account,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ]);
    }

    /**
     * Dedicated printable view of an individual Account General Ledger statement.
     */
    public function accountLedgerPrint(Request $request, Account $account, FinancialReportingService $reportingService): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to') ?: now()->toDateString();

        $ledgerData = $reportingService->getAccountLedger($account, $dateFrom, $dateTo);
        $company = CompanySetting::first();

        return view('accounting.reports.account-ledger-print', [
            'title' => 'طباعة كشف حساب: '.$account->name,
            'ledger' => $ledgerData,
            'account' => $account,
            'company' => $company,
            'autoPrint' => $request->boolean('auto_print', false),
        ]);
    }
}
