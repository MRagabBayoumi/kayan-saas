<?php

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::firstOrCreate(
        ['email' => 'admin@kayan-erp.com'],
        [
            'name' => 'مدير النظام',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]
    );

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'location' => 'القاهرة',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'عميل تجريبي للدفعات',
        'company_name' => 'مؤسسة الاختبار',
        'phone' => '01011122233',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'opening_balance' => 0.00,
        'current_balance' => 0.00,
        'credit_limit' => 50000.00,
        'is_active' => true,
    ]);
});

test('sales invoice can receive an installment payment and updates remaining and customer balance', function () {
    // 1. Create a posted sales invoice with partial credit
    $invoice = SalesInvoice::create([
        'invoice_number' => SalesInvoice::generateInvoiceNumber(),
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => SalesInvoice::PAYMENT_PARTIAL,
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 1000.00,
        'total_amount' => 1000.00,
        'paid_amount' => 300.00,
        'remaining_amount' => 700.00,
        'created_by' => $this->admin->id,
    ]);

    // Update customer debt for remaining amount
    $this->customer->update(['current_balance' => 700.00]);

    // 2. Open an active cashier shift
    $shift = CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => CashierShift::STATUS_OPEN,
        'opened_at' => now(),
        'opening_balance' => 500.00,
        'cash_sales_amount' => 0.00,
        'opened_by' => $this->admin->id,
    ]);

    // 3. Post an installment payment of 400 EGP
    $response = $this->actingAs($this->admin)->post(route('sales.payments.store', $invoice), [
        'amount' => 400.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => CustomerPayment::METHOD_CASH,
        'reference_number' => 'REF-001',
        'notes' => 'دفعة سداد أولى نقداً',
    ]);

    $response->assertRedirect(route('sales.show', $invoice));
    $response->assertSessionHas('status', 'success');

    // 4. Assert invoice updated
    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toEqual(700.00)
        ->and((float) $invoice->remaining_amount)->toEqual(300.00)
        ->and($invoice->is_fully_paid)->toBeFalse()
        ->and($invoice->payment_status_label)->toBe('مدفوعة جزئياً');

    // 5. Assert customer balance reduced by 400 EGP
    expect((float) $this->customer->fresh()->current_balance)->toEqual(300.00);

    // 6. Assert payment record created
    $payment = CustomerPayment::where('sales_invoice_id', $invoice->id)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toEqual(400.00)
        ->and($payment->payment_method)->toBe(CustomerPayment::METHOD_CASH)
        ->and($payment->reference_number)->toBe('REF-001');

    // 7. Assert cashier shift received cash
    $shift->refresh();
    expect((float) $shift->cash_sales_amount)->toEqual(400.00);

    // 8. Assert statement service ledger contains payment and correct running balance
    $statementService = app(StatementService::class);
    $statement = $statementService->getCustomerStatement($this->customer);

    expect($statement['final_balance'])->toEqual(300.00);
    $paymentRow = collect($statement['rows'])->firstWhere('type', 'payment');
    expect($paymentRow)->not->toBeNull()
        ->and((float) $paymentRow['credit'])->toEqual(400.00)
        ->and($paymentRow['doc_number'])->toBe($payment->payment_number);
});

test('payment cannot exceed invoice remaining amount', function () {
    $invoice = SalesInvoice::create([
        'invoice_number' => SalesInvoice::generateInvoiceNumber(),
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => SalesInvoice::PAYMENT_PARTIAL,
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 500.00,
        'total_amount' => 500.00,
        'paid_amount' => 300.00,
        'remaining_amount' => 200.00,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->post(route('sales.payments.store', $invoice), [
        'amount' => 250.00, // exceeds 200.00
        'payment_date' => now()->toDateString(),
        'payment_method' => CustomerPayment::METHOD_CASH,
    ]);

    $response->assertSessionHasErrors(['amount']);
});

test('payment receipt voucher page renders successfully in different formats', function () {
    $invoice = SalesInvoice::create([
        'invoice_number' => SalesInvoice::generateInvoiceNumber(),
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => SalesInvoice::PAYMENT_CREDIT,
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 800.00,
        'total_amount' => 800.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 800.00,
        'created_by' => $this->admin->id,
    ]);

    $payment = CustomerPayment::create([
        'payment_number' => CustomerPayment::generatePaymentNumber(),
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 500.00,
        'payment_method' => CustomerPayment::METHOD_CASH,
        'created_by' => $this->admin->id,
    ]);

    // A5 format
    $responseA5 = $this->actingAs($this->admin)->get(route('sales.payments.receipt', [$payment, 'format' => 'a5']));
    $responseA5->assertOk();
    $responseA5->assertSee('سند قبض مالي');
    $responseA5->assertSee('500.00');
    $responseA5->assertSee($this->customer->name);

    // Thermal format
    $responseThermal = $this->actingAs($this->admin)->get(route('sales.payments.receipt', [$payment, 'format' => 'thermal_80mm']));
    $responseThermal->assertOk();
    $responseThermal->assertSee('سند قبض نقدية');
    $responseThermal->assertSee('500.00');
});

test('admin can void a customer payment and reverses balances safely', function () {
    $invoice = SalesInvoice::create([
        'invoice_number' => SalesInvoice::generateInvoiceNumber(),
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => SalesInvoice::PAYMENT_PARTIAL,
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 1000.00,
        'total_amount' => 1000.00,
        'paid_amount' => 500.00, // 200 initial + 300 from payment
        'remaining_amount' => 500.00,
        'created_by' => $this->admin->id,
    ]);

    $this->customer->update(['current_balance' => 500.00]);

    $payment = CustomerPayment::create([
        'payment_number' => CustomerPayment::generatePaymentNumber(),
        'customer_id' => $this->customer->id,
        'sales_invoice_id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'amount' => 300.00,
        'payment_method' => CustomerPayment::METHOD_BANK_TRANSFER,
        'created_by' => $this->admin->id,
    ]);

    // Admin voids payment
    $response = $this->actingAs($this->admin)->delete(route('sales.payments.destroy', $payment));
    $response->assertRedirect(route('sales.show', $invoice));

    // Balances reversed
    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toEqual(200.00)
        ->and((float) $invoice->remaining_amount)->toEqual(800.00);

    expect((float) $this->customer->fresh()->current_balance)->toEqual(800.00);
    expect(CustomerPayment::find($payment->id))->toBeNull();
});
