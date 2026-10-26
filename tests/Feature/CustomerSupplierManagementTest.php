<?php

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\ShippingOffice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customers index page renders properly with metrics and table', function () {
    Customer::create([
        'name' => 'تاجر الجملة الحاج سعيد',
        'company_name' => 'مؤسسة النور للأدوات',
        'phone' => '01012345678',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'القاهرة',
        'credit_limit' => 50000,
        'current_balance' => 12500,
        'is_active' => true,
    ]);

    Customer::create([
        'name' => 'أحمد علي قطاعي',
        'phone' => '01123456789',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
        'city' => 'الجيزة',
        'credit_limit' => 0,
        'current_balance' => 0,
        'is_active' => true,
    ]);

    $response = $this->get(route('customers.index'));

    $response->assertOk();
    $response->assertSee('دليل عملاء الجملة والتجزئة');
    $response->assertSee('تاجر الجملة الحاج سعيد');
    $response->assertSee('أحمد علي قطاعي');
    $response->assertSee('12,500.00');
});

test('customer creation validates required fields', function () {
    $response = $this->post(route('customers.store'), []);

    $response->assertSessionHasErrors(['name', 'phone', 'customer_type', 'pricing_tier']);
});

test('customer can be created with full details', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'معرض البركة للتجارة',
        'company_name' => 'شركة البركة العالمية',
        'phone' => '01234567890',
        'phone_alt' => '022345678',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'طنطا',
        'address' => 'شارع البحر، المحطة',
        'credit_limit' => 100000,
        'current_balance' => 0,
        'notes' => 'عميل جملة موثوق',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'name' => 'معرض البركة للتجارة',
        'company_name' => 'شركة البركة العالمية',
        'phone' => '01234567890',
        'customer_type' => 'wholesale',
        'city' => 'طنطا',
    ]);
});

test('customer show page displays profile and ledger placeholder', function () {
    $customer = Customer::create([
        'name' => 'محلات السلام',
        'phone' => '01099887766',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'المنصورة',
        'current_balance' => 4500.50,
    ]);

    $response = $this->get(route('customers.show', $customer));

    $response->assertOk();
    $response->assertSee('محلات السلام');
    $response->assertSee('المنصورة');
    $response->assertSee('4,500.50');
    $response->assertSee('كشف حساب وحركات الفواتير');
});

test('customer can be updated', function () {
    $customer = Customer::create([
        'name' => 'عميل للتعديل',
        'phone' => '01000000000',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
    ]);

    $response = $this->put(route('customers.update', $customer), [
        'name' => 'الاسم بعد التعديل',
        'phone' => '01111111111',
        'customer_type' => 'distributor',
        'pricing_tier' => 'wholesale',
        'city' => 'الإسكندرية',
        'credit_limit' => 20000,
        'current_balance' => 0,
    ]);

    $response->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'الاسم بعد التعديل',
        'phone' => '01111111111',
        'customer_type' => 'distributor',
        'city' => 'الإسكندرية',
    ]);
});

test('customer can be created and updated with distributor pricing tier', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'شركة التوزيع الحديثة',
        'company_name' => 'موزع معتمد دلتا',
        'phone' => '01099887766',
        'customer_type' => 'distributor',
        'pricing_tier' => 'distributor',
        'city' => 'المنصورة',
        'credit_limit' => 150000,
        'current_balance' => 0,
    ]);

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');

    $customer = Customer::where('phone', '01099887766')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->pricing_tier)->toBe('distributor')
        ->and($customer->customer_type)->toBe('distributor')
        ->and($customer->getPricingTierLabel())->toBe('سعر الموزع');

    $updateResponse = $this->put(route('customers.update', $customer), [
        'name' => 'شركة التوزيع الحديثة (معدل)',
        'phone' => '01099887766',
        'customer_type' => 'distributor',
        'pricing_tier' => 'distributor',
        'credit_limit' => 200000,
    ]);

    $updateResponse->assertRedirect(route('customers.index'));
    expect($customer->fresh()->name)->toBe('شركة التوزيع الحديثة (معدل)')
        ->and($customer->fresh()->pricing_tier)->toBe('distributor');
});

test('customer with zero balance can be deleted', function () {
    $customer = Customer::create([
        'name' => 'عميل بدون رصيد',
        'phone' => '01022222222',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
        'current_balance' => 0.00,
    ]);

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('customer with non-zero balance cannot be deleted', function () {
    $customer = Customer::create([
        'name' => 'عميل مدين',
        'phone' => '01033333333',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'current_balance' => 5000.00,
    ]);

    $response = $this->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

test('customer lookup json api returns matching results', function () {
    Customer::create([
        'name' => 'عميل بحث خاص 1',
        'phone' => '01088888888',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'is_active' => true,
    ]);

    Customer::create([
        'name' => 'محل آخر مختلف',
        'phone' => '01099999999',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
        'is_active' => true,
    ]);

    $response = $this->getJson(route('customers.lookup', ['q' => 'بحث خاص']));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonCount(1, 'results');
    $response->assertJsonPath('results.0.name', 'عميل بحث خاص 1');
});

test('suppliers index page renders properly with metrics and table', function () {
    Supplier::create([
        'name' => 'Yiwu Leather Goods Factory',
        'supplier_type' => 'china_factory',
        'contact_person' => 'Mr. Chen',
        'wechat_id' => 'chen_yiwu88',
        'country' => 'China',
        'city' => 'Yiwu',
        'currency' => 'CNY',
        'current_balance' => 35000,
        'is_active' => true,
    ]);

    Supplier::create([
        'name' => 'شركة الأهرام للتوريدات',
        'supplier_type' => 'local_supplier',
        'contact_person' => 'م. طارق',
        'country' => 'Egypt',
        'city' => 'القاهرة',
        'currency' => 'EGP',
        'current_balance' => 0,
        'is_active' => true,
    ]);

    $response = $this->get(route('suppliers.index'));

    $response->assertOk();
    $response->assertSee('دليل الموردين ومصانع الصين');
    $response->assertSee('Yiwu Leather Goods Factory');
    $response->assertSee('chen_yiwu88');
    $response->assertSee('شركة الأهرام للتوريدات');
});

test('supplier creation validates required fields', function () {
    $response = $this->post(route('suppliers.store'), []);

    $response->assertSessionHasErrors(['name', 'supplier_type', 'currency']);
});

test('supplier can be created as local supplier defaulting to EGP and Egypt with null wechat_id', function () {
    $response = $this->post(route('suppliers.store'), [
        'name' => 'شركة النور للمستلزمات المحلية',
        'supplier_type' => 'local_supplier',
        'contact_person' => 'أحمد محمود',
        'phone' => '01012345678',
        'city' => 'القاهرة',
    ]);

    $response->assertRedirect(route('suppliers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'name' => 'شركة النور للمستلزمات المحلية',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'country' => 'مصر',
        'wechat_id' => null,
    ]);
});

test('supplier can be created with China factory info and WeChat ID', function () {
    $response = $this->post(route('suppliers.store'), [
        'name' => 'Guangzhou Handbag Co.',
        'supplier_type' => 'china_factory',
        'contact_person' => 'Linda Wong',
        'wechat_id' => 'linda_gz_bags',
        'phone' => '+8613800138000',
        'email' => 'linda@gzbags.com',
        'country' => 'China',
        'city' => 'Guangzhou',
        'currency' => 'USD',
        'current_balance' => 0,
        'notes' => 'مصنع حقائب يد معتمد',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('suppliers.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Guangzhou Handbag Co.',
        'wechat_id' => 'linda_gz_bags',
        'currency' => 'USD',
        'country' => 'China',
    ]);
});

test('supplier show page displays details and balance', function () {
    $supplier = Supplier::create([
        'name' => 'Zhejiang Accessories Mill',
        'supplier_type' => 'china_factory',
        'contact_person' => 'Master Zhang',
        'wechat_id' => 'zhang_mill',
        'country' => 'China',
        'currency' => 'CNY',
        'current_balance' => 15800,
    ]);

    $response = $this->get(route('suppliers.show', $supplier));

    $response->assertOk();
    $response->assertSee('Zhejiang Accessories Mill');
    $response->assertSee('zhang_mill');
    $response->assertSee('15,800.00 CNY');
    $response->assertSee('سجل أوامر الشراء والاستيراد من الصين');
});

test('supplier can be updated', function () {
    $supplier = Supplier::create([
        'name' => 'مورد قديم',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
    ]);

    $response = $this->put(route('suppliers.update', $supplier), [
        'name' => 'مورد محدث بالكامل',
        'supplier_type' => 'shipping_agent',
        'contact_person' => 'أ. حسام',
        'country' => 'Egypt',
        'currency' => 'EGP',
        'current_balance' => 0,
    ]);

    $response->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'مورد محدث بالكامل',
        'supplier_type' => 'shipping_agent',
    ]);
});

test('supplier with non-zero balance cannot be deleted', function () {
    $supplier = Supplier::create([
        'name' => 'مصنع له مستحقات',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'current_balance' => 8000,
    ]);

    $response = $this->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirect(route('suppliers.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
});

test('supplier lookup json api returns matching results', function () {
    Supplier::create([
        'name' => 'Foshan Hardware Factory',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
        'is_active' => true,
    ]);

    Supplier::create([
        'name' => 'مورد آخر محلي',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'is_active' => true,
    ]);

    $response = $this->getJson(route('suppliers.lookup', ['q' => 'Foshan']));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonCount(1, 'results');
    $response->assertJsonPath('results.0.name', 'Foshan Hardware Factory');
});

test('shipping offices can be managed via index, store, update, and destroy', function () {
    // 1. Create shipping office
    $storeResponse = $this->post(route('shipping-offices.store'), [
        'name' => 'شركة النجم الساطع للشحن الجوي والبحري',
        'shipping_type' => 'international_china',
        'contact_person' => 'كابتن محمود',
        'phone' => '01001234567',
        'phone_alt' => '01101234567',
        'rate_per_cbm_or_kg' => 180.50,
        'address' => 'مكتب كوانزو ومكتب بورسعيد',
        'notes' => 'شحن بحري سريع 25 يوم',
        'is_active' => '1',
    ]);

    $storeResponse->assertRedirect(route('shipping-offices.index'));
    $storeResponse->assertSessionHas('success');

    $office = ShippingOffice::where('name', 'شركة النجم الساطع للشحن الجوي والبحري')->firstOrFail();
    expect($office->rate_per_cbm_or_kg)->toEqual(180.50);

    // 2. View index
    $indexResponse = $this->get(route('shipping-offices.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('شركة النجم الساطع للشحن الجوي والبحري');
    $indexResponse->assertSee('كابتن محمود');

    // 3. Update
    $updateResponse = $this->put(route('shipping-offices.update', $office), [
        'name' => 'شركة النجم للشحن الدولي المحدثة',
        'shipping_type' => 'international_china',
        'phone' => '01099998888',
    ]);
    $updateResponse->assertRedirect(route('shipping-offices.index'));
    $this->assertDatabaseHas('shipping_offices', [
        'id' => $office->id,
        'name' => 'شركة النجم للشحن الدولي المحدثة',
    ]);

    // 4. Destroy
    $deleteResponse = $this->delete(route('shipping-offices.destroy', $office));
    $deleteResponse->assertRedirect(route('shipping-offices.index'));
    $this->assertSoftDeleted('shipping_offices', ['id' => $office->id]);
});

test('supplier with zero balance can be deleted', function () {
    $supplier = Supplier::create([
        'name' => 'مورد للتصفية',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'current_balance' => 0.00,
    ]);

    $response = $this->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirect(route('suppliers.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});

test('dashboard displays real customer, supplier, and product counters', function () {
    Customer::create([
        'name' => 'عميل اختبار',
        'phone' => '01011111111',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
    ]);

    Supplier::create([
        'name' => 'مورد اختبار',
        'supplier_type' => 'china_factory',
        'currency' => 'CNY',
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('دليل العملاء');
    $response->assertSee('دليل الموردين');
});

test('customer can be created with credit opening balance and appears in statement', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'علي حنفي تيست',
        'phone' => '01127556176',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'opening_balance' => -5734.00,
    ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::where('phone', '01127556176')->first();
    expect($customer)->not->toBeNull();
    expect((float) $customer->opening_balance)->toBe(-5734.00);
    expect((float) $customer->current_balance)->toBe(-5734.00);

    // Check Statement page
    $statementResponse = $this->get(route('customers.statement', $customer));
    $statementResponse->assertOk();
    $statementResponse->assertSee('-5,734.00');
    $statementResponse->assertSee('رصيد دائن لصالح العميل');

    // Check Printable Statement
    $printResponse = $this->get(route('customers.statement.print', ['customer' => $customer, 'format' => 'a4']));
    $printResponse->assertOk();
    $printResponse->assertSee('5,734.00');
    $printResponse->assertSee('رصيد دائن لصالح العميل');
});

test('sales invoice print shows paid, invoice remaining, previous balance, and net remaining balance', function () {
    $customer = Customer::create([
        'name' => 'عميل اختبار فواتير',
        'phone' => '01012344321',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'opening_balance' => -5734.00,
        'current_balance' => -334.00,
    ]);

    $warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'المخزن الرئيسي',
        'code' => 'MAIN-WH',
        'is_active' => true,
    ]);

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-TEST-0001',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => 'credit',
        'status' => 'posted',
        'subtotal' => 5400.00,
        'discount_amount' => 0.00,
        'shipping_cost' => 0.00,
        'total_amount' => 5400.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 5400.00,
        'total_cogs' => 0.00,
        'total_profit' => 0.00,
        'created_by' => auth()->id() ?? 1,
        'posted_at' => now(),
    ]);

    $response = $this->get(route('sales.print', ['sale' => $invoice, 'format' => 'a4']));
    $response->assertOk();
    $response->assertSee('المدفوع:');
    $response->assertSee('المتبقي من الفاتورة:');
    $response->assertSee('5,400.00');
    $response->assertSee('الرصيد السابق للعميل:');
    $response->assertSee('5,734.00');
    $response->assertSee('إجمالي الحساب المتبقي:');
    $response->assertSee('334.00');
});

test('general customer payment auto allocates to open invoices and synchronizes current balance', function () {
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::create([
        'name' => 'عميل اختبار تسوية الدفعة',
        'phone' => '01011112233',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'opening_balance' => 0.00,
        'current_balance' => 0.00,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::first() ?? Warehouse::create([
        'name' => 'المخزن الرئيسي',
        'code' => 'MAIN-WH',
        'is_active' => true,
    ]);

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-ALLOC-TEST-1',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'wholesale',
        'payment_type' => 'credit',
        'status' => 'posted',
        'subtotal' => 2000.00,
        'total_amount' => 2000.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 2000.00,
        'created_by' => $user->id,
        'posted_at' => now(),
    ]);

    // Record a general payment of 2500 EGP (exceeding the invoice by 500)
    $response = $this->post(route('customers.payments.store', $customer), [
        'payment_date' => now()->toDateString(),
        'amount' => 2500.00,
        'payment_method' => 'cash',
        'notes' => 'سداد عام للحساب',
    ]);

    $response->assertSessionHas('success');

    $invoice->refresh();
    expect((float) $invoice->paid_amount)->toEqual(2000.00)
        ->and((float) $invoice->remaining_amount)->toEqual(0.00);

    $customer->refresh();
    // 0 + 2000 - 2500 = -500 (credit in favor of customer)
    expect((float) $customer->current_balance)->toEqual(-500.00);

    // Visiting customer show page should render the new payments table and accurate values
    $showResponse = $this->get(route('customers.show', $customer));
    $showResponse->assertOk();
    $showResponse->assertSee('سجل سندات القبض والدفعات المالية للعميل');
    $showResponse->assertSee('2,500.00');
    $showResponse->assertSee('مسددة بالكامل');
    $showResponse->assertSee('500.00');
    $showResponse->assertSee('رصيد دائن لصالح العميل');
});
