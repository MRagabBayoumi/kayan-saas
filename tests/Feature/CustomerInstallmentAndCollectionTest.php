<?php

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerInstallment;
use App\Models\CustomerPayment;
use App\Models\SalesInvoice;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
    ]);
    $this->actingAs($this->user);

    $this->treasury = Treasury::create([
        'name' => 'الخزينة الرئيسية',
        'type' => 'cash',
        'current_balance' => 10000.00,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST',
        'name' => 'مستودع المعادي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'شركة النور للمقاولات',
        'phone' => '01099887766',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'approval_status' => 'approved',
        'current_balance' => 5000.00,
        'is_active' => true,
    ]);

    $this->salesInvoice = SalesInvoice::create([
        'invoice_number' => 'INV-2026-0001',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 5000.00,
        'total_amount' => 5000.00,
        'paid_amount' => 1000.00,
        'remaining_amount' => 4000.00,
        'payment_status' => 'partial',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);
});

test('customer installments index page renders successfully with kpi cards and tabs', function () {
    CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط أول مجدول',
        'due_date' => now()->addDays(5)->toDateString(),
        'amount' => 2000.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
        'created_by' => $this->user->id,
    ]);

    CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط متأخر',
        'due_date' => now()->subDays(3)->toDateString(),
        'amount' => 1500.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
        'created_by' => $this->user->id,
    ]);

    $response = $this->get(route('customer-installments.index'));

    $response->assertOk();
    $response->assertSee('جدول مواعيد وأقساط تحصيل العملاء');
    $response->assertSee('أقساط متأخرة واجبة التحصيل فوراً');
    $response->assertSee('قسط أول مجدول');
    $response->assertSee('قسط متأخر');
    $response->assertSee('شركة النور للمقاولات');
    $response->assertSee('INV-2026-0001');
});

test('single customer installment can be scheduled successfully', function () {
    $response = $this->post(route('customer-installments.store'), [
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'plan_type' => 'single',
        'installment_title' => 'دفعة نقدية بعد 10 أيام',
        'due_date' => now()->addDays(10)->toDateString(),
        'amount' => 1200.00,
        'notes' => 'تحصيل عبر شيك مصرفي',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customer_installments', [
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'دفعة نقدية بعد 10 أيام',
        'amount' => 1200.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
    ]);
});

test('multiple customer installments can be generated automatically across intervals', function () {
    $response = $this->post(route('customer-installments.store'), [
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'plan_type' => 'schedule',
        'installment_title' => 'خطة تحصيل ربع سنوية',
        'total_amount' => 3000.00,
        'installments_count' => 3,
        'first_due_date' => now()->addDays(7)->toDateString(),
        'interval' => 'monthly',
        'notes' => 'أقساط شهرية منتظمة',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $installments = CustomerInstallment::where('customer_id', $this->customer->id)->orderBy('due_date')->get();
    expect($installments)->toHaveCount(3);

    expect((float) $installments[0]->amount)->toEqual(1000.00);
    expect((float) $installments[1]->amount)->toEqual(1000.00);
    expect((float) $installments[2]->amount)->toEqual(1000.00);
    expect($installments[0]->installment_title)->toContain('(قسط 1/3)');
    expect($installments[1]->installment_title)->toContain('(قسط 2/3)');
    expect($installments[2]->installment_title)->toContain('(قسط 3/3)');
});

test('customer installment can be collected recording a customer payment, updating shift and customer balance', function () {
    $shift = CashierShift::create([
        'shift_number' => 'SHF-TEST-001',
        'warehouse_id' => $this->warehouse->id,
        'user_id' => $this->user->id,
        'opened_at' => now(),
        'opening_balance' => 500.00,
        'cash_sales' => 0.00,
        'cash_collected' => 0.00,
        'status' => CashierShift::STATUS_OPEN,
    ]);

    $installment = CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط يستحق اليوم',
        'due_date' => now()->toDateString(),
        'amount' => 1000.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
        'created_by' => $this->user->id,
    ]);

    $response = $this->post(route('customer-installments.collect', $installment), [
        'amount' => 1000.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => 'REC-9988',
        'notes' => 'تحصيل كامل القسط نقداً بالدرج',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $installment->refresh();
    expect((float) $installment->paid_amount)->toEqual(1000.00);
    expect((float) $installment->remaining_amount)->toEqual(0.00);
    expect($installment->status)->toEqual(CustomerInstallment::STATUS_PAID);

    // Assert CustomerPayment created with customer_installment_id
    $this->assertDatabaseHas('customer_payments', [
        'customer_id' => $this->customer->id,
        'customer_installment_id' => $installment->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'amount' => 1000.00,
        'payment_method' => 'cash',
    ]);

    // Customer balance decremented from 5000 to 4000
    $this->customer->refresh();
    expect((float) $this->customer->current_balance)->toEqual(4000.00);

    // SalesInvoice remaining decremented from 4000 to 3000
    $this->salesInvoice->refresh();
    expect((float) $this->salesInvoice->remaining_amount)->toEqual(3000.00);
    expect((float) $this->salesInvoice->paid_amount)->toEqual(2000.00);

    // Shift cash_sales_amount incremented
    $shift->refresh();
    expect((float) $shift->cash_sales_amount)->toEqual(1000.00);
});

test('customer installment cannot be collected with an amount greater than remaining', function () {
    $installment = CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط اختبار',
        'due_date' => now()->toDateString(),
        'amount' => 500.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
        'created_by' => $this->user->id,
    ]);

    $response = $this->post(route('customer-installments.collect', $installment), [
        'amount' => 600.00, // Greater than 500
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ]);

    $response->assertSessionHasErrors(['amount']);
});

test('customer installment can be deleted if unpaid but cannot be deleted if already partially collected', function () {
    $installment = CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط للحذف',
        'due_date' => now()->toDateString(),
        'amount' => 500.00,
        'paid_amount' => 0.00,
        'status' => CustomerInstallment::STATUS_PENDING,
        'created_by' => $this->user->id,
    ]);

    $delResponse = $this->delete(route('customer-installments.destroy', $installment));
    $delResponse->assertRedirect();
    $delResponse->assertSessionHas('success');

    $this->assertDatabaseMissing('customer_installments', [
        'id' => $installment->id,
    ]);

    $paidInstallment = CustomerInstallment::create([
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $this->salesInvoice->id,
        'installment_title' => 'قسط مسدد جزئياً',
        'due_date' => now()->toDateString(),
        'amount' => 500.00,
        'paid_amount' => 200.00,
        'status' => CustomerInstallment::STATUS_PARTIAL,
        'created_by' => $this->user->id,
    ]);

    $failResponse = $this->delete(route('customer-installments.destroy', $paidInstallment));
    $failResponse->assertRedirect();
    $failResponse->assertSessionHas('error');

    $this->assertDatabaseHas('customer_installments', [
        'id' => $paidInstallment->id,
    ]);
});

test('invoices for customer endpoint returns unpaid invoices for dropdown', function () {
    $response = $this->getJson(route('customers.unpaid-invoices', $this->customer));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    $invoices = $response->json('invoices');
    expect($invoices)->toHaveCount(1);
    expect($invoices[0]['invoice_number'])->toEqual('INV-2026-0001');
    expect((float) $invoices[0]['remaining_amount'])->toEqual(4000.00);
});
