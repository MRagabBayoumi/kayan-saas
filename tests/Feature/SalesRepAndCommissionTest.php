<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesCommission;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'مدير النظام',
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $this->salesRepRole = Role::firstOrCreate(
        ['slug' => 'sales_rep'],
        [
            'name' => 'مندوب مبيعات خارجي / بياع',
            'description' => 'الوصول لبوابة المندوب من الموبايل',
            'is_system' => true,
        ]
    );

    $this->salesRep = User::factory()->create([
        'name' => 'أحمد المندوب',
        'email' => 'ahmed.rep@kayan.com',
        'role' => User::ROLE_SALES_REP,
        'role_id' => $this->salesRepRole->id,
        'commission_rate' => 5.00, // 5%
        'commission_type' => 'percentage',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH-MAIN',
        'name' => 'المستودع الرئيسي',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'ACC',
        'is_active' => true,
    ]);

    $this->unitPiece = Unit::create([
        'name' => 'قطعة',
        'code' => 'PCS',
        'operator' => '*',
        'conversion_factor' => 1.0000,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'sku' => 'PROD-REP-01',
        'barcode' => '6221199887766',
        'name' => 'منتج مبيعات المندوب',
        'cost_price' => 50.00,
        'retail_price' => 100.00,
        'wholesale_price' => 80.00,
        'distributor_price' => 70.00,
        'unit' => 'قطعة',
        'unit_factor' => 1.0,
        'pieces_per_package' => 1,
        'is_active' => true,
    ]);

    // Initial stock: 100 pieces
    app(InventoryService::class)->addStock(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        quantity: 100.0,
        costPrice: 50.00,
        referenceType: 'opening_balance'
    );
});

test('sales rep helper methods and relationships operate correctly', function () {
    expect($this->salesRep->isSalesRep())->toBeTrue();
    expect($this->salesRep->roleLabel)->toBe('مندوب مبيعات خارجي / بياع');

    $customer = Customer::create([
        'name' => 'محل الأناقة الحديثة',
        'phone' => '01012345678',
        'pricing_tier' => 'wholesale',
        'sales_rep_id' => $this->salesRep->id,
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'is_active' => true,
    ]);

    expect($this->salesRep->assignedCustomers)->toHaveCount(1);
    expect($customer->salesRep->id)->toBe($this->salesRep->id);
    expect((float) $customer->latitude)->toBe(30.0444);
});

test('posting an invoice for customer linked to sales rep automatically calculates percentage commission', function () {
    $customer = Customer::create([
        'name' => 'سوبر ماركت النور',
        'phone' => '01055554444',
        'pricing_tier' => 'retail',
        'sales_rep_id' => $this->salesRep->id,
        'is_active' => true,
    ]);

    // 10 pieces at 100 LE = 1000 LE total
    $response = $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 10,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();
    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_POSTED);

    // Verify invoice attributes
    expect($invoice->sales_rep_id)->toBe($this->salesRep->id);
    expect((float) $invoice->commission_rate)->toBe(5.00);
    // 5% of 1000 LE = 50.00 LE
    expect((float) $invoice->commission_amount)->toBe(50.00);

    // Verify SalesCommission entry
    $commission = SalesCommission::where('sales_invoice_id', $invoice->id)->first();
    expect($commission)->not->toBeNull();
    expect($commission->sales_rep_id)->toBe($this->salesRep->id);
    expect((float) $commission->invoice_total)->toBe(1000.00);
    expect((float) $commission->commission_amount)->toBe(50.00);
    expect($commission->status)->toBe(SalesCommission::STATUS_PENDING);
    expect($commission->status_label)->toBe('قيد المراجعة');
});

test('unposting a sales invoice cancels the associated commission', function () {
    $customer = Customer::create([
        'name' => 'محل السلام',
        'phone' => '01077778888',
        'pricing_tier' => 'retail',
        'sales_rep_id' => $this->salesRep->id,
        'is_active' => true,
    ]);

    // Create and post invoice (Total = 500 LE, Commission = 25 LE)
    $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 5,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    $commission = SalesCommission::where('sales_invoice_id', $invoice->id)->first();
    expect($commission->status)->toBe(SalesCommission::STATUS_PENDING);

    // Unpost invoice
    $this->post(route('sales.unpost-and-edit', $invoice));

    $commission->refresh();
    expect($commission->status)->toBe(SalesCommission::STATUS_CANCELED);
    expect($commission->status_label)->toBe('ملغاة');

    $invoice->refresh();
    expect((float) $invoice->commission_amount)->toBe(0.00);
});

test('fixed commission type assigns exact fixed amount per invoice', function () {
    $fixedRep = User::factory()->create([
        'name' => 'محمود المندوب (عمولة ثابتة)',
        'role' => User::ROLE_SALES_REP,
        'commission_rate' => 30.00, // 30 LE fixed per invoice
        'commission_type' => 'fixed',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'معرض السعادة',
        'phone' => '01033332222',
        'pricing_tier' => 'retail',
        'sales_rep_id' => $fixedRep->id,
        'is_active' => true,
    ]);

    // Invoice of 800 LE
    $this->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $this->warehouse->id,
        'invoice_date' => now()->format('Y-m-d'),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'action' => 'post',
        'items' => [
            [
                'product_id' => $this->product->id,
                'unit_id' => $this->unitPiece->id,
                'conversion_factor' => 1.0,
                'quantity' => 8,
                'unit_price' => 100.00,
                'line_discount' => 0,
            ],
        ],
    ]);

    $invoice = SalesInvoice::latest('id')->first();
    expect($invoice->sales_rep_id)->toBe($fixedRep->id);
    expect((float) $invoice->commission_amount)->toBe(30.00);

    $commission = SalesCommission::where('sales_invoice_id', $invoice->id)->first();
    expect((float) $commission->commission_amount)->toBe(30.00);
});
