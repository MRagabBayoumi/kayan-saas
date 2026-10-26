<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer index has open-add-customer button in control panel and modal container', function () {
    $response = $this->get(route('customers.index'));

    $response->assertOk()
        ->assertSee('data-action="open-add-customer"', false)
        ->assertSee('id="addCustomerModal"', false)
        ->assertSee('data-action="close-add-customer"', false)
        ->assertSee('action="'.route('customers.store').'"', false);
});

test('supplier index has open-add-supplier button in control panel and modal container', function () {
    $response = $this->get(route('suppliers.index'));

    $response->assertOk()
        ->assertSee('data-action="open-add-supplier"', false)
        ->assertSee('id="addSupplierModal"', false)
        ->assertSee('data-action="close-add-supplier"', false)
        ->assertSee('action="'.route('suppliers.store').'"', false);
});

test('shipping offices index has open-add-shipping button in control panel and modal container', function () {
    $response = $this->get(route('shipping-offices.index'));

    $response->assertOk()
        ->assertSee('data-action="open-add-shipping"', false)
        ->assertSee('id="addShippingModal"', false)
        ->assertSee('data-action="close-add-shipping"', false)
        ->assertSee('action="'.route('shipping-offices.store').'"', false);
});

test('products index has top batch print button and counter in control panel', function () {
    $category = Category::create(['name' => 'إكسسوارات شعر', 'code' => 'ACC']);
    Product::create([
        'category_id' => $category->id,
        'name' => 'توكة شعر ستان بريميوم',
        'sku' => 'TK-STN-001',
        'barcode' => '622000001001',
        'retail_price' => 35.00,
        'wholesale_price' => 25.00,
        'distributor_price' => 20.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertOk()
        ->assertSee('id="topBatchPrintBtn"', false)
        ->assertSee('id="topBatchCount"', false)
        ->assertSee('data-action="direct-print-batch"', false)
        ->assertSee('id="batchActionBar"', false)
        ->assertSee('id="batchPrintModal"', false);
});

test('submitting customer form saves record and refreshes list with success flash message', function () {
    $payload = [
        'name' => 'مؤسسة الصفوة للتجارة والتوزيع',
        'company_name' => 'الصفوة جروب',
        'phone' => '01099887766',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'المنصورة',
        'credit_limit' => 25000,
        'current_balance' => 0,
        'is_active' => '1',
    ];

    $response = $this->post(route('customers.store'), $payload);

    $response->assertRedirect(route('customers.index'))
        ->assertSessionHas('success', 'تم إضافة العميل بنجاح.');

    $this->assertDatabaseHas('customers', [
        'name' => 'مؤسسة الصفوة للتجارة والتوزيع',
        'phone' => '01099887766',
    ]);

    $indexResponse = $this->get(route('customers.index'));
    $indexResponse->assertSee('مؤسسة الصفوة للتجارة والتوزيع');
});

test('submitting supplier form saves record and refreshes list with success flash message', function () {
    $payload = [
        'name' => 'مصنع إيوو للتوك والإكسسوارات (Yiwu Factory)',
        'supplier_type' => 'china_factory',
        'contact_person' => 'Mr. Chen Wei',
        'wechat_id' => 'yiwu_chen_88',
        'phone' => '+8613800138000',
        'email' => 'sales@yiwuhair.cn',
        'country' => 'China',
        'city' => 'Yiwu',
        'currency' => 'CNY',
        'current_balance' => 0,
        'is_active' => '1',
    ];

    $response = $this->post(route('suppliers.store'), $payload);

    $response->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'تم تسجيل المورد بنجاح.');

    $this->assertDatabaseHas('suppliers', [
        'name' => 'مصنع إيوو للتوك والإكسسوارات (Yiwu Factory)',
        'wechat_id' => 'yiwu_chen_88',
    ]);

    $indexResponse = $this->get(route('suppliers.index'));
    $indexResponse->assertSee('مصنع إيوو للتوك والإكسسوارات (Yiwu Factory)');
});

test('submitting shipping office form saves record and refreshes list with success flash message', function () {
    $payload = [
        'name' => 'مكتب الصفا للشحن والتخليص الجمركي',
        'shipping_type' => 'international_china',
        'contact_person' => 'أحمد فتحي',
        'phone' => '01200112233',
        'rate_per_cbm_or_kg' => 180.00,
        'address' => 'الإسكندرية، ميناء الدخيلة',
        'is_active' => '1',
    ];

    $response = $this->post(route('shipping-offices.store'), $payload);

    $response->assertRedirect(route('shipping-offices.index'))
        ->assertSessionHas('success', 'تم إضافة مكتب الشحن بنجاح.');

    $this->assertDatabaseHas('shipping_offices', [
        'name' => 'مكتب الصفا للشحن والتخليص الجمركي',
        'shipping_type' => 'international_china',
    ]);

    $indexResponse = $this->get(route('shipping-offices.index'));
    $indexResponse->assertSee('مكتب الصفا للشحن والتخليص الجمركي');
});

test('customer record can be edited and updated via modal form action', function () {
    $customer = Customer::create([
        'name' => 'عميل قديم',
        'phone' => '01011112222',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
        'is_active' => true,
    ]);

    $updateResponse = $this->put(route('customers.update', $customer), [
        'name' => 'عميل تم تحديثه بنجاح',
        'phone' => '01011112222',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'المنصورة',
        'credit_limit' => 30000,
        'current_balance' => 500,
        'is_active' => '1',
    ]);

    $updateResponse->assertRedirect(route('customers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'عميل تم تحديثه بنجاح',
        'customer_type' => 'wholesale',
    ]);

    $indexResponse = $this->get(route('customers.index'));
    $indexResponse->assertSee('عميل تم تحديثه بنجاح');
});

test('supplier record can be edited and updated via modal form action', function () {
    $supplier = Supplier::create([
        'name' => 'مورد قديم',
        'supplier_type' => 'local_supplier',
        'currency' => 'EGP',
        'is_active' => true,
    ]);

    $updateResponse = $this->put(route('suppliers.update', $supplier), [
        'name' => 'مورد محدث - مصنع شنغهاي',
        'supplier_type' => 'china_factory',
        'wechat_id' => 'shanghai_acc_99',
        'country' => 'China',
        'city' => 'Shanghai',
        'currency' => 'CNY',
        'is_active' => '1',
    ]);

    $updateResponse->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'مورد محدث - مصنع شنغهاي',
        'wechat_id' => 'shanghai_acc_99',
        'currency' => 'CNY',
    ]);

    $indexResponse = $this->get(route('suppliers.index'));
    $indexResponse->assertSee('مورد محدث - مصنع شنغهاي');
});

test('customer validation failure retains user in session with errors and does not corrupt data', function () {
    $response = $this->post(route('customers.store'), [
        'name' => '', // missing name
        'phone' => '', // missing phone
    ]);

    $response->assertSessionHasErrors(['name', 'phone']);
    $this->assertDatabaseMissing('customers', [
        'name' => '',
    ]);
});

test('direct batch print endpoint successfully generates multi-item label print view', function () {
    $category = Category::create(['name' => 'إكسسوارات', 'code' => 'ACC']);
    $p1 = Product::create([
        'category_id' => $category->id,
        'name' => 'صنف للطباعة 1',
        'sku' => 'PRNT-001',
        'retail_price' => 10,
        'wholesale_price' => 8,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);
    $p2 = Product::create([
        'category_id' => $category->id,
        'name' => 'صنف للطباعة 2',
        'sku' => 'PRNT-002',
        'retail_price' => 20,
        'wholesale_price' => 15,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.print-batch', [
        'products' => [
            $p1->id => 2,
            $p2->id => 3,
        ],
        'autoprint' => 1,
    ]));

    $response->assertOk()
        ->assertSee('PRNT-001')
        ->assertSee('PRNT-002')
        ->assertSee('صنف للطباعة 1')
        ->assertSee('صنف للطباعة 2')
        ->assertSee('data-auto-print="true"', false);
});

test('purchases index has vertically stacked main cards and enterprise filter form', function () {
    $response = $this->get(route('purchases.index'));

    $response->assertOk()
        ->assertSee('data-page="purchases-index"', false)
        ->assertDontSee('data-page="purchases-index" class="flex flex-col gap-5"', false)
        ->assertSee('class="purchases-kpi-row"', false)
        ->assertSee('class="purchases-kpi-stat"', false)
        ->assertSee('class="erp-filter-form"', false)
        ->assertSee('name="search"', false)
        ->assertSee('name="status"', false)
        ->assertSee('name="warehouse_id"', false)
        ->assertSee('name="supplier_id"', false)
        ->assertSee('class="erp-table"', false);
});
