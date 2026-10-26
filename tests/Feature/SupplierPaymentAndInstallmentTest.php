<?php

use App\Models\Supplier;
use App\Models\SupplierInstallment;
use App\Models\SupplierPayment;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->actingAs($this->user);

    $this->treasury = Treasury::create([
        'name' => 'الخزينة الرئيسية للاختبار',
        'type' => Treasury::TYPE_CASH,
        'current_balance' => 50000.00,
        'is_active' => true,
    ]);
});

test('supplier installments index can be accessed and shows metrics', function () {
    $supplier = Supplier::create([
        'name' => 'مصنع شينزن للإلكترونيات',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'current_balance' => 12000.00,
    ]);

    SupplierInstallment::create([
        'supplier_id' => $supplier->id,
        'installment_title' => 'دفعة استلام الشحنة',
        'due_date' => now()->addDays(5)->toDateString(),
        'amount' => 4000.00,
        'paid_amount' => 0.00,
        'status' => SupplierInstallment::STATUS_PENDING,
    ]);

    $response = $this->get(route('supplier-installments.index'));

    $response->assertOk();
    $response->assertSee('جدول أقساط ومستحقات الموردين المجدولة');
    $response->assertSee('مصنع شينزن للإلكترونيات');
    $response->assertSee('دفعة استلام الشحنة');
});

test('single supplier installment can be created', function () {
    $supplier = Supplier::create([
        'name' => 'شركة النور للتوريدات المحلية',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 15000.00,
    ]);

    $response = $this->post(route('supplier-installments.store'), [
        'supplier_id' => $supplier->id,
        'plan_type' => 'single',
        'installment_title' => 'قسط أول بعد الفحص',
        'due_date' => now()->addDays(10)->toDateString(),
        'amount' => 5000.00,
        'notes' => 'مشروط بفحص الجودة',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('supplier_installments', [
        'supplier_id' => $supplier->id,
        'installment_title' => 'قسط أول بعد الفحص',
        'amount' => 5000.00,
        'status' => SupplierInstallment::STATUS_PENDING,
    ]);
});

test('recurring schedule of installments can be generated automatically', function () {
    $supplier = Supplier::create([
        'name' => 'مصنع إيوو للمصنوعات الجلدية',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'current_balance' => 30000.00,
    ]);

    $response = $this->post(route('supplier-installments.store'), [
        'supplier_id' => $supplier->id,
        'plan_type' => 'schedule',
        'installment_title' => 'أقساط فاتورة الاستيراد',
        'total_amount' => 30000.00,
        'installments_count' => 3,
        'first_due_date' => now()->addMonth()->toDateString(),
        'interval' => 'monthly',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(SupplierInstallment::where('supplier_id', $supplier->id)->count())->toBe(3);

    $installments = SupplierInstallment::where('supplier_id', $supplier->id)->orderBy('due_date')->get();
    expect((float) $installments[0]->amount)->toBe(10000.00);
    expect((float) $installments[1]->amount)->toBe(10000.00);
    expect((float) $installments[2]->amount)->toBe(10000.00);
});

test('supplier payment can settle specific installment and update balances', function () {
    $supplier = Supplier::create([
        'name' => 'مورد مستلزمات الإنتاج',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 10000.00,
    ]);

    $installment = SupplierInstallment::create([
        'supplier_id' => $supplier->id,
        'installment_title' => 'قسط شهر نوفمبر',
        'due_date' => now()->addDays(7)->toDateString(),
        'amount' => 4000.00,
        'paid_amount' => 0.00,
        'status' => SupplierInstallment::STATUS_PENDING,
    ]);

    $response = $this->post(route('suppliers.payments.store', $supplier), [
        'amount' => 4000.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'treasury_id' => $this->treasury->id,
        'supplier_installment_id' => $installment->id,
        'notes' => 'سداد القسط كاملاً',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $installment->refresh();
    expect((float) $installment->paid_amount)->toBe(4000.00);
    expect($installment->status)->toBe(SupplierInstallment::STATUS_PAID);

    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(6000.00);

    $this->treasury->refresh();
    expect((float) $this->treasury->current_balance)->toBe(46000.00);
});

test('deleting a supplier payment linked to installment restores the installment', function () {
    $supplier = Supplier::create([
        'name' => 'مورد للاختبار مع الاسترجاع',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 10000.00,
    ]);

    $installment = SupplierInstallment::create([
        'supplier_id' => $supplier->id,
        'installment_title' => 'قسط قابل للاسترجاع',
        'due_date' => now()->addDays(3)->toDateString(),
        'amount' => 3000.00,
        'paid_amount' => 0.00,
        'status' => SupplierInstallment::STATUS_PENDING,
    ]);

    $this->post(route('suppliers.payments.store', $supplier), [
        'amount' => 3000.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'bank_transfer',
        'treasury_id' => $this->treasury->id,
        'supplier_installment_id' => $installment->id,
    ]);

    $payment = SupplierPayment::where('supplier_installment_id', $installment->id)->firstOrFail();

    $deleteResponse = $this->delete(route('supplier-payments.destroy', $payment));
    $deleteResponse->assertRedirect();

    $installment->refresh();
    expect((float) $installment->paid_amount)->toBe(0.00);
    expect($installment->status)->toBe(SupplierInstallment::STATUS_PENDING);

    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(10000.00);
});

test('payablesData endpoint returns supplier invoices and installments as json', function () {
    $supplier = Supplier::create([
        'name' => 'مورد فحص الـ API',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 8500.00,
    ]);

    SupplierInstallment::create([
        'supplier_id' => $supplier->id,
        'installment_title' => 'قسط تجريبي',
        'due_date' => now()->addDays(4)->toDateString(),
        'amount' => 2500.00,
        'paid_amount' => 0.00,
        'status' => SupplierInstallment::STATUS_PENDING,
    ]);

    $response = $this->getJson(route('suppliers.payables-data', $supplier));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('supplier.id', $supplier->id);
    $response->assertJsonCount(1, 'installments');
    $response->assertJsonPath('installments.0.installment_title', 'قسط تجريبي');
});
