<?php

use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\FinancialReportingService;
use App\Services\InventoryService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);

    CompanySetting::create([
        'company_name' => 'Kayan Accessories',
        'currency' => 'EGP',
    ]);

    $this->seed(ChartOfAccountsSeeder::class);
});

test('unauthorized users cannot access financial reports', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    $this->actingAs($cashier);

    $this->get(route('accounting.reports.trial-balance'))->assertForbidden();
    $this->get(route('accounting.reports.income-statement'))->assertForbidden();
    $this->get(route('accounting.reports.balance-sheet'))->assertForbidden();
});

test('trial balance page and print view load correctly and balance', function () {
    $this->actingAs($this->admin);

    $cashAccount = Account::where('system_role', 'cash_main')->firstOrFail();
    $salesAccount = Account::where('system_role', 'sales_retail')->firstOrFail();

    // Create a balanced journal entry
    $entry = JournalEntry::create([
        'entry_number' => 'JV-TEST-001',
        'entry_date' => now()->toDateString(),
        'description' => 'قيد مبيعات نقدي تجريبي',
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->admin->id,
        'posted_by' => $this->admin->id,
        'posted_at' => now(),
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $cashAccount->id,
        'debit' => 1000.00,
        'credit' => 0.00,
        'description' => 'مدين - الصندوق',
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $salesAccount->id,
        'debit' => 0.00,
        'credit' => 1000.00,
        'description' => 'دائن - إيراد المبيعات',
    ]);

    $response = $this->get(route('accounting.reports.trial-balance'));
    $response->assertOk();
    $response->assertSee('ميزان المراجعة');
    $response->assertSee('1,000.00');

    $printResponse = $this->get(route('accounting.reports.trial-balance.print'));
    $printResponse->assertOk();
    $printResponse->assertSee('ميزان المراجعة بالأرصدة والمجاميع');
});

test('income statement displays revenue, expenses, and net profit correctly', function () {
    $this->actingAs($this->admin);

    $salesAccount = Account::where('system_role', 'sales_retail')->firstOrFail();
    $expenseAccount = Account::where('system_role', 'general_expense')->firstOrFail();
    $cashAccount = Account::where('system_role', 'cash_main')->firstOrFail();

    // Entry 1: Revenue 5,000
    $entry1 = JournalEntry::create([
        'entry_number' => 'JV-TEST-REV',
        'entry_date' => now()->toDateString(),
        'description' => 'إيراد مبيعات',
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->admin->id,
        'posted_by' => $this->admin->id,
        'posted_at' => now(),
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry1->id,
        'account_id' => $cashAccount->id,
        'debit' => 5000.00,
        'credit' => 0.00,
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry1->id,
        'account_id' => $salesAccount->id,
        'debit' => 0.00,
        'credit' => 5000.00,
    ]);

    // Entry 2: Expense 1,200
    $entry2 = JournalEntry::create([
        'entry_number' => 'JV-TEST-EXP',
        'entry_date' => now()->toDateString(),
        'description' => 'مصروف إداري',
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->admin->id,
        'posted_by' => $this->admin->id,
        'posted_at' => now(),
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry2->id,
        'account_id' => $expenseAccount->id,
        'debit' => 1200.00,
        'credit' => 0.00,
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry2->id,
        'account_id' => $cashAccount->id,
        'debit' => 0.00,
        'credit' => 1200.00,
    ]);

    $response = $this->get(route('accounting.reports.income-statement'));
    $response->assertOk();
    $response->assertSee('قائمة الدخل والأرباح والخسائر');
    $response->assertSee('5,000.00'); // Revenue
    $response->assertSee('1,200.00'); // Expense
    $response->assertSee('3,800.00'); // Net profit: 5000 - 1200

    $printResponse = $this->get(route('accounting.reports.income-statement.print'));
    $printResponse->assertOk();
    $printResponse->assertSee('قائمة الدخل والأرباح والخسائر');
});

test('balance sheet maintains accounting equation Assets = Liabilities + Equity', function () {
    $this->actingAs($this->admin);

    $cashAccount = Account::where('system_role', 'cash_main')->firstOrFail();
    $salesAccount = Account::where('system_role', 'sales_retail')->firstOrFail();

    // 20,000 cash from sales
    $entry = JournalEntry::create([
        'entry_number' => 'JV-TEST-BS',
        'entry_date' => now()->toDateString(),
        'description' => 'قيد أرباح مبيعات للميزانية',
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->admin->id,
        'posted_by' => $this->admin->id,
        'posted_at' => now(),
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $cashAccount->id,
        'debit' => 20000.00,
        'credit' => 0.00,
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $salesAccount->id,
        'debit' => 0.00,
        'credit' => 20000.00,
    ]);

    $response = $this->get(route('accounting.reports.balance-sheet'));
    $response->assertOk();
    $response->assertSee('الميزانية العمومية والمركز المالي');
    $response->assertSee('20,000.00');
    $response->assertSee('الميزانية متوازنة ومطابقة تماماً');

    $printResponse = $this->get(route('accounting.reports.balance-sheet.print'));
    $printResponse->assertOk();
    $printResponse->assertSee('الميزانية العمومية والمركز المالي');
});

test('account ledger tracks individual movements and running balance', function () {
    $this->actingAs($this->admin);

    $cashAccount = Account::where('system_role', 'cash_main')->firstOrFail();
    $salesAccount = Account::where('system_role', 'sales_retail')->firstOrFail();

    $entry = JournalEntry::create([
        'entry_number' => 'JV-LEDGER-01',
        'entry_date' => now()->toDateString(),
        'description' => 'حركة كشف الحساب التجريبي',
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->admin->id,
        'posted_by' => $this->admin->id,
        'posted_at' => now(),
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $cashAccount->id,
        'debit' => 4500.00,
        'credit' => 0.00,
        'description' => 'إيداع نقدي بالخزينة',
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $salesAccount->id,
        'debit' => 0.00,
        'credit' => 4500.00,
        'description' => 'مبيعات نقدية',
    ]);

    $response = $this->get(route('accounting.accounts.ledger', $cashAccount));
    $response->assertOk();
    $response->assertSee('كشف حساب الأستاذ العام');
    $response->assertSee($cashAccount->name);
    $response->assertSee('4,500.00');
    $response->assertSee('إيداع نقدي بالخزينة');

    $printResponse = $this->get(route('accounting.accounts.ledger.print', $cashAccount));
    $printResponse->assertOk();
    $printResponse->assertSee('كشف حساب الأستاذ العام');
    $printResponse->assertSee($cashAccount->name);
});

test('cash sales invoice records full revenue even if paid amount is omitted and unposting voids entry', function () {
    $this->actingAs($this->admin);

    $customer = Customer::create([
        'name' => 'عميل نقدي تجريبي',
        'phone' => '01011223344',
        'pricing_tier' => 'retail',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH-TEST',
        'name' => 'المخزن التجريبي',
        'is_active' => true,
    ]);

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-CASH-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => SalesInvoice::PAYMENT_CASH,
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 500.00,
        'discount_amount' => 0.00,
        'shipping_cost' => 0.00,
        'total_amount' => 500.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 0.00,
        'total_cogs' => 350.00,
        'total_profit' => 150.00,
        'created_by' => $this->admin->id,
    ]);

    $accounting = app(AccountingService::class);
    $entry = $accounting->recordSalesInvoice($invoice);

    expect($entry)->not->toBeNull();
    expect((float) $entry->total_debit)->toBe(850.00); // 500 cash + 350 cogs
    expect((float) $entry->total_credit)->toBe(850.00); // 500 sales + 350 inventory

    $reporting = app(FinancialReportingService::class);
    $statement = $reporting->getIncomeStatement();

    expect((float) $statement['net_revenue'])->toBe(500.00);
    expect((float) $statement['total_cogs'])->toBe(350.00);
    expect((float) $statement['gross_profit'])->toBe(150.00);

    // Now test that unposting reverses the journal entry
    $inventoryService = app(InventoryService::class);
    $inventoryService->unpostSalesInvoice($invoice, $this->admin->id, 'تعديل الفاتورة');

    $entry->refresh();
    expect($entry->status)->toBe(JournalEntry::STATUS_REVERSED);

    // After unposting, income statement should be empty
    $statementAfter = $reporting->getIncomeStatement();
    expect((float) $statementAfter['gross_profit'])->toBe(0.00);
});
