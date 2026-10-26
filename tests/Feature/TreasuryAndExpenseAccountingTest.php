<?php

use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\TreasuryTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
    ]);
    $this->actingAs($this->user);

    CompanySetting::create([
        'company_name' => 'Kayan Accessories',
        'currency' => 'EGP',
    ]);
});

test('treasury can be created, deposited, withdrawn, and transferred atomically', function () {
    $treasuryA = Treasury::create([
        'name' => 'الخزينة الرئيسية',
        'type' => Treasury::TYPE_CASH,
        'opening_balance' => 1000.00,
        'current_balance' => 1000.00,
        'is_default' => true,
        'is_active' => true,
    ]);

    $treasuryB = Treasury::create([
        'name' => 'حساب بنك مصر',
        'type' => Treasury::TYPE_BANK,
        'opening_balance' => 500.00,
        'current_balance' => 500.00,
        'is_default' => false,
        'is_active' => true,
    ]);

    // Test deposit
    $depTrx = $treasuryA->recordDeposit(200.00, 'manual', null, 'إيداع نقدي تجريبي', $this->user->id);
    expect((float) $treasuryA->fresh()->current_balance)->toBe(1200.00)
        ->and($depTrx->type)->toBe(TreasuryTransaction::TYPE_DEPOSIT)
        ->and((float) $depTrx->balance_after)->toBe(1200.00);

    // Test withdrawal
    $wthTrx = $treasuryA->recordWithdrawal(100.00, 'manual', null, 'سحب نقدي تجريبي', $this->user->id);
    expect((float) $treasuryA->fresh()->current_balance)->toBe(1100.00)
        ->and($wthTrx->type)->toBe(TreasuryTransaction::TYPE_WITHDRAWAL)
        ->and((float) $wthTrx->balance_after)->toBe(1100.00);

    // Test transfer from Treasury A to Treasury B via HTTP route
    $response = $this->post(route('treasuries.transfer'), [
        'from_treasury_id' => $treasuryA->id,
        'to_treasury_id' => $treasuryB->id,
        'amount' => 300.00,
        'transfer_date' => now()->toDateString(),
        'notes' => 'تحويل إيداع بنكي',
    ]);

    $response->assertSessionHas('success');
    expect((float) $treasuryA->fresh()->current_balance)->toBe(800.00)
        ->and((float) $treasuryB->fresh()->current_balance)->toBe(800.00);

    expect(TreasuryTransfer::count())->toBe(1);
});

test('supplier payment against purchase invoice updates invoice, supplier balance, and treasury', function () {
    $treasury = Treasury::create([
        'name' => 'الخزينة الرئيسية',
        'type' => Treasury::TYPE_CASH,
        'opening_balance' => 5000.00,
        'current_balance' => 5000.00,
        'is_default' => true,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'المخزن الرئيسي',
        'code' => 'WH-01',
        'is_active' => true,
    ]);

    $supplier = Supplier::create([
        'name' => 'مصنع قوانغتشو للإكسسوارات',
        'supplier_type' => 'china_factory',
        'currency' => 'EGP',
        'opening_balance' => 0.00,
        'current_balance' => 3000.00, // owes supplier 3000
    ]);

    $invoice = PurchaseInvoice::create([
        'invoice_number' => 'PUR-20261017-0001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_type' => PurchaseInvoice::PAYMENT_CREDIT,
        'subtotal' => 3000.00,
        'total_amount' => 3000.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 3000.00,
    ]);

    // Pay 1000 installment
    $response = $this->post(route('purchases.payments.store', $invoice), [
        'amount' => 1000.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'treasury_id' => $treasury->id,
        'notes' => 'دفعة أولى من الحساب',
    ]);

    $response->assertSessionHas('success');

    $invoice->refresh();
    $supplier->refresh();
    $treasury->refresh();

    expect((float) $invoice->paid_amount)->toBe(1000.00)
        ->and((float) $invoice->remaining_amount)->toBe(2000.00)
        ->and($invoice->is_fully_paid)->toBeFalse()
        ->and((float) $supplier->current_balance)->toBe(2000.00)
        ->and((float) $treasury->current_balance)->toBe(4000.00);

    // Check payment record
    $payment = SupplierPayment::first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(1000.00)
        ->and($payment->payment_number)->toStartWith('SPAY-');

    // Check StatementService reflects this payment
    $statementService = app(StatementService::class);
    $statement = $statementService->getSupplierStatement($supplier);

    $paymentRows = collect($statement['rows'])->where('type', 'payment');
    expect($paymentRows)->toHaveCount(1)
        ->and((float) $paymentRows->first()['debit'])->toBe(1000.00)
        ->and($statement['final_balance'])->toBe(2000.00);
});

test('expense creation deducts from treasury and can be reversed on cancel', function () {
    $treasury = Treasury::create([
        'name' => 'الخزينة الرئيسية',
        'type' => Treasury::TYPE_CASH,
        'opening_balance' => 2000.00,
        'current_balance' => 2000.00,
        'is_default' => true,
        'is_active' => true,
    ]);

    $category = ExpenseCategory::firstOrCreate(
        ['code' => 'EXP-RENT'],
        ['name' => 'إيجارات ومقرات', 'is_active' => true]
    );

    // Record expense of 450
    $response = $this->post(route('expenses.store'), [
        'expense_category_id' => $category->id,
        'amount' => 450.00,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'treasury_id' => $treasury->id,
        'paid_to' => 'مالك العقار',
        'notes' => 'إيجار شهر أكتوبر',
    ]);

    $response->assertSessionHas('success');

    $treasury->refresh();
    expect((float) $treasury->current_balance)->toBe(1550.00);

    $expense = Expense::first();
    expect($expense)->not->toBeNull()
        ->and((float) $expense->amount)->toBe(450.00)
        ->and($expense->expense_number)->toStartWith('EXP-');

    // Delete / Void expense
    $deleteResp = $this->delete(route('expenses.destroy', $expense));
    $deleteResp->assertSessionHas('success');

    $treasury->refresh();
    expect((float) $treasury->current_balance)->toBe(2000.00)
        ->and(Expense::count())->toBe(0);
});

test('accounting screens load correctly', function () {
    Treasury::create([
        'name' => 'الخزينة الرئيسية',
        'type' => Treasury::TYPE_CASH,
        'opening_balance' => 1000.00,
        'current_balance' => 1000.00,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->get(route('treasuries.index'))->assertOk();
    $this->get(route('supplier-payments.index'))->assertOk();
    $this->get(route('expenses.index'))->assertOk();
    $this->get(route('sales.payments.index'))->assertOk();
});
