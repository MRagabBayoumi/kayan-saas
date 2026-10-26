<?php

use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanySetting::create([
        'company_name' => 'Kayan Accessories',
        'currency' => 'EGP',
    ]);

    // Seed standard chart of accounts
    $this->artisan('db:seed', ['--class' => 'ChartOfAccountsSeeder']);

    $this->admin = User::firstOrCreate(
        ['email' => 'admin_test_gl@kayan.com'],
        [
            'name' => 'Admin Tester',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]
    );

    $this->actingAs($this->admin);
});

test('chart of accounts has 5 root heads and standard accounts seeded', function () {
    $roots = Account::whereNull('parent_id')->get();
    expect($roots->count())->toBe(5);

    $codes = $roots->pluck('code')->all();
    expect($codes)->toContain('1', '2', '3', '4', '5');

    // Verify key accounts exist
    expect(Account::where('code', '110101')->exists())->toBeTrue(); // Cash
    expect(Account::where('code', '110201')->exists())->toBeTrue(); // Accounts Receivable
    expect(Account::where('code', '110301')->exists())->toBeTrue(); // Inventory
    expect(Account::where('code', '210101')->exists())->toBeTrue(); // Local Suppliers
    expect(Account::where('code', '410101')->exists())->toBeTrue(); // Retail Sales
    expect(Account::where('code', '510101')->exists())->toBeTrue(); // COGS
});

test('accounting service strictly rejects unbalanced entries', function () {
    $service = new AccountingService;

    $cashAcc = Account::where('code', '110101')->firstOrFail();
    $revAcc = Account::where('code', '410101')->firstOrFail();

    expect(fn () => $service->createEntry([
        'description' => 'Unbalanced test',
        'entry_date' => now()->toDateString(),
    ], [
        ['account_id' => $cashAcc->id, 'debit' => 500, 'credit' => 0],
        ['account_id' => $revAcc->id, 'debit' => 0, 'credit' => 450],
    ]))->toThrow(DomainException::class);
});

test('accounting service creates balanced manual journal entry and updates balances', function () {
    $service = new AccountingService;

    $cashAcc = Account::where('code', '110101')->firstOrFail();
    $revAcc = Account::where('code', '410101')->firstOrFail();

    $initialCash = (float) $cashAcc->current_balance;

    $entry = $service->createEntry([
        'description' => 'قيد يدوي متزن للاختبار',
        'entry_date' => now()->toDateString(),
    ], [
        ['account_id' => $cashAcc->id, 'debit' => 1000.00, 'credit' => 0],
        ['account_id' => $revAcc->id, 'debit' => 0, 'credit' => 1000.00],
    ]);

    expect($entry)->toBeInstanceOf(JournalEntry::class);
    expect($entry->isBalanced())->toBeTrue();
    expect((float) $entry->total_debit)->toBe(1000.00);
    expect((float) $entry->total_credit)->toBe(1000.00);

    // Cash balance should increase by 1000
    expect((float) $cashAcc->fresh()->current_balance)->toBe(round($initialCash + 1000.00, 2));
});

test('sales invoice creates fully balanced double-entry with cogs', function () {
    $service = new AccountingService;

    $customer = Customer::firstOrCreate(
        ['name' => 'عميل تجريبي للقيود'],
        ['phone' => '01099999991', 'is_active' => true]
    );

    $warehouse = Warehouse::firstOrCreate(
        ['name' => 'مخزن المحاسبة'],
        ['code' => 'WH-ACC-01', 'is_active' => true]
    );

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-GL-'.uniqid(),
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'partial',
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 2000.00,
        'discount_amount' => 100.00,
        'shipping_cost' => 0.00,
        'total_amount' => 1900.00,
        'paid_amount' => 900.00,
        'remaining_amount' => 1000.00,
        'total_cogs' => 1200.00,
        'total_profit' => 700.00,
        'created_by' => $this->admin->id,
    ]);

    $entry = $service->recordSalesInvoice($invoice);

    expect($entry)->not->toBeNull();
    expect($entry->isBalanced())->toBeTrue();
    expect($entry->reference_type)->toBe('sales_invoice');
    expect($entry->lines->count())->toBeGreaterThanOrEqual(4);

    // Sum of debits and credits:
    // Debits: 900 cash + 1000 AR + 100 discount + 1200 COGS = 3200
    // Credits: 2000 Sales + 1200 Inventory = 3200
    expect((float) $entry->total_debit)->toBe(3200.00);
    expect((float) $entry->total_credit)->toBe(3200.00);
});

test('chart of accounts and journal entries pages load successfully', function () {
    $resCoa = $this->get(route('accounting.chart-of-accounts'));
    $resCoa->assertStatus(200);
    $resCoa->assertSee('دليل الحسابات الشجري');
    $resCoa->assertSee('الأصول');

    // Create an entry with customer and supplier line relations to ensure eager loading works
    $customer = Customer::create(['name' => 'عميل اختبار', 'phone' => '01011112222', 'is_active' => true]);
    $cashAcc = Account::where('code', '110101')->firstOrFail();
    $arAcc = Account::where('code', '110201')->firstOrFail();

    $entry = (new AccountingService)->createEntry([
        'description' => 'قيد تجريبي للأطراف',
        'entry_date' => now()->toDateString(),
    ], [
        ['account_id' => $cashAcc->id, 'debit' => 250, 'credit' => 0],
        ['account_id' => $arAcc->id, 'debit' => 0, 'credit' => 250, 'party_type' => 'customer', 'party_id' => $customer->id],
    ]);

    $resJe = $this->get(route('accounting.journal-entries'));
    $resJe->assertStatus(200);
    $resJe->assertSee('دفتر قيود اليومية العامة');
    $resJe->assertSee($entry->entry_number);

    // Test HTML show page
    $resShow = $this->get(route('accounting.journal-entries.show', $entry));
    $resShow->assertStatus(200);
    $resShow->assertSee('سند قيد يومية');
    $resShow->assertSee($entry->entry_number);

    // Test Printable voucher page
    $resPrint = $this->get(route('accounting.journal-entries.print', $entry));
    $resPrint->assertStatus(200);
    $resPrint->assertSee('طباعة سند القيد');

    // Test JSON endpoint when requested as JSON
    $resJson = $this->getJson(route('accounting.journal-entries.show', $entry));
    $resJson->assertStatus(200);
    $resJson->assertJsonPath('success', true);
    $resJson->assertJsonPath('entry.id', $entry->id);
});

test('can create a sub-account under an existing parent', function () {
    $parent = Account::where('code', '1101')->firstOrFail(); // Cash & cash equiv

    $res = $this->post(route('accounting.accounts.store'), [
        'parent_id' => $parent->id,
        'name' => 'خزينة فرع الاختبار الذاتي',
        'code' => '110199',
        'description' => 'حساب فرعي تجريبي',
    ]);

    $res->assertRedirect();
    $res->assertSessionHas('status', 'success');

    $created = Account::where('code', '110199')->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('خزينة فرع الاختبار الذاتي');
    expect($created->parent_id)->toBe($parent->id);
    expect($created->level)->toBe($parent->level + 1);
    expect($created->is_leaf)->toBeTrue();
});
