<?php

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProductImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guest is redirected to login page when attempting to access protected routes', function (string $url) {
    Auth::logout();

    $response = $this->get($url);

    $response->assertRedirect(route('login'));
})->with([
    '/',
    '/products',
    '/settings',
    '/purchases',
    '/inventory',
    '/warehouses',
    '/customers',
    '/suppliers',
]);

test('login page is accessible to guests and renders Arabic RTL form', function () {
    Auth::logout();

    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('تسجيل الدخول')
        ->assertSee('البريد الإلكتروني')
        ->assertSee('كلمة المرور');
});

test('user can authenticate with valid credentials', function () {
    Auth::logout();

    $user = User::create([
        'name' => 'مسؤول تجريبي',
        'email' => 'test-auth@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->post(route('login.post'), [
        'email' => 'test-auth@kayan.test',
        'password' => 'Secret123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('user can authenticate using username instead of email', function () {
    Auth::logout();

    $user = User::create([
        'name' => 'أحمد_المحاسب',
        'email' => 'ahmed.acc@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);

    // Submit using 'login' field with username
    $response = $this->post(route('login.post'), [
        'login' => 'أحمد_المحاسب',
        'password' => 'Secret123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('user can authenticate using login field with email', function () {
    Auth::logout();

    $user = User::create([
        'name' => 'سارة_الكاشير',
        'email' => 'sara.cashier@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_CASHIER,
    ]);

    $response = $this->post(route('login.post'), [
        'login' => 'sara.cashier@kayan.test',
        'password' => 'Secret123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('user cannot authenticate with invalid password', function () {
    Auth::logout();

    User::create([
        'name' => 'مسؤول تجريبي',
        'email' => 'test-auth@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->from(route('login'))->post(route('login.post'), [
        'email' => 'test-auth@kayan.test',
        'password' => 'WrongPassword',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('authenticated user can logout and session is cleared', function () {
    $user = User::create([
        'name' => 'مسؤول تجريبي',
        'email' => 'test-auth@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $this->actingAs($user);

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('admin has full access to settings, print templates, and all modules', function () {
    $admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-check@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $this->actingAs($admin);

    CompanySetting::getSettings();

    $this->get(route('settings.index'))->assertOk();
    $this->get(route('settings.print-templates.data'))->assertOk();
    $this->get(route('products.index'))->assertOk();
    $this->get(route('purchases.index'))->assertOk();
    $this->get(route('inventory.index'))->assertOk();
});

test('warehouse user can access products, inventory, and purchases but cannot access company settings or print templates', function () {
    $warehouseUser = User::create([
        'name' => 'أمين مخزن',
        'email' => 'wh-user@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_WAREHOUSE,
    ]);
    $this->actingAs($warehouseUser);

    CompanySetting::getSettings();

    // Allowed areas
    $this->get(route('products.index'))->assertOk();
    $this->get(route('purchases.index'))->assertOk();
    $this->get(route('inventory.index'))->assertOk();

    // Forbidden areas
    $this->get(route('settings.index'))->assertForbidden();
    $this->get(route('settings.print-templates.data'))->assertForbidden();
});

test('warehouse user cannot post or cancel purchase invoices', function () {
    $warehouseUser = User::create([
        'name' => 'أمين مخزن',
        'email' => 'wh-user@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_WAREHOUSE,
    ]);
    $this->actingAs($warehouseUser);

    $supplier = Supplier::create([
        'name' => 'شركة توريد اختبار',
        'phone' => '01012345678',
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create([
        'code' => 'WH-TEST-01',
        'name' => 'مستودع الاختبار',
        'is_active' => true,
    ]);

    $invoice = PurchaseInvoice::create([
        'invoice_number' => 'PUR-TEST-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'status' => PurchaseInvoice::STATUS_DRAFT,
        'subtotal' => 100.00,
        'total_amount' => 100.00,
    ]);

    $this->post(route('purchases.post', $invoice))->assertForbidden();
    $this->post(route('purchases.cancel', $invoice))->assertForbidden();
});

test('warehouse user cannot edit product prices but can update other product details', function () {
    $warehouseUser = User::create([
        'name' => 'أمين مخزن',
        'email' => 'wh-user@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_WAREHOUSE,
    ]);
    $this->actingAs($warehouseUser);

    $category = Category::create(['name' => 'تصنيف اختبار', 'code' => 'CAT-TEST']);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'SKU-PRICE-01',
        'name' => 'اسم قديم',
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 45.00,
        'unit' => 'قطعة',
        'is_active' => true,
    ]);

    // Updating non-price fields is allowed
    $response = $this->put(route('products.update', $product), [
        'sku' => $product->sku,
        'name' => 'اسم جديد ومحدث',
        'category_id' => $category->id,
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 45.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'اسم جديد ومحدث']);

    // Attempting to modify price is forbidden with 403
    $this->put(route('products.update', $product), [
        'sku' => $product->sku,
        'name' => 'اسم جديد ومحدث',
        'category_id' => $category->id,
        'cost_price' => 25.00, // Changed!
        'wholesale_price' => 30.00,
        'retail_price' => 45.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ])->assertForbidden();
});

test('cashier has read-only access to products and cannot create, edit, or access inventory/purchases/settings', function () {
    $cashier = User::create([
        'name' => 'كاشير المحل',
        'email' => 'cashier@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_CASHIER,
    ]);
    $this->actingAs($cashier);

    $category = Category::create(['name' => 'تصنيف كاشير', 'code' => 'CAT-CASH']);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'SKU-CASH-01',
        'name' => 'صنف كاشير',
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 20.00,
        'unit' => 'قطعة',
        'barcode' => '6221234567890',
        'is_active' => true,
    ]);

    // Allowed read-only product views
    $this->get(route('products.index'))->assertOk();
    $this->get(route('products.show', $product))->assertOk();
    $this->get(route('products.lookup-barcode', ['barcode' => $product->barcode]))->assertOk();

    // Forbidden modifications
    $this->get(route('products.create'))->assertForbidden();
    $this->post(route('products.store'), [])->assertForbidden();
    $this->get(route('products.edit', $product))->assertForbidden();
    $this->put(route('products.update', $product), [])->assertForbidden();
    $this->delete(route('products.destroy', $product))->assertForbidden();

    // Forbidden other modules
    $this->get(route('purchases.index'))->assertForbidden();
    $this->get(route('inventory.index'))->assertForbidden();
    $this->get(route('settings.index'))->assertForbidden();
});

test('created_by and user_id are populated from the currently authenticated user', function () {
    $admin = User::create([
        'name' => 'مهندس التدقيق',
        'email' => 'auditor@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $this->actingAs($admin);

    $supplier = Supplier::create([
        'name' => 'مورد فواتير',
        'phone' => '01055554444',
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create([
        'code' => 'WH-AUDIT',
        'name' => 'مستودع تدقيق',
        'is_active' => true,
    ]);
    $category = Category::create(['name' => 'تصنيف تدقيق', 'code' => 'CAT-AUDIT']);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'SKU-AUDIT-01',
        'name' => 'صنف تدقيق',
        'cost_price' => 15.00,
        'wholesale_price' => 20.00,
        'retail_price' => 25.00,
        'unit' => 'دستة',
        'is_active' => true,
    ]);

    // 1. Purchase invoice store creates record with created_by = auth()->id()
    $response = $this->post(route('purchases.store'), [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_cost' => 15.00,
            ],
        ],
    ]);
    $response->assertRedirect();

    $invoice = PurchaseInvoice::latest('id')->first();
    expect($invoice->created_by)->toBe($admin->id);

    // 2. Inventory movement records user_id = auth()->id()
    $inventoryService = app(InventoryService::class);
    $result = $inventoryService->addStock(
        productId: $product->id,
        warehouseId: $warehouse->id,
        quantity: 5,
        costPrice: 20.00,
        notes: 'حركة تدقيق المسؤول'
    );

    expect($result['movement']->user_id)->toBe($admin->id);
});

test('administrator can create an item with initial prices and subsequently modify those prices', function () {
    $admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-pricing@kayan.test',
        'password' => Hash::make('AdminSecret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $this->actingAs($admin);

    $category = Category::create(['name' => 'إكسسوارات شعر', 'code' => 'HAIR-01']);

    // Admin creates product with initial prices
    $response = $this->post(route('products.store'), [
        'sku' => 'PRD-ADM-001',
        'name' => 'طوق لؤلؤ فاخر',
        'category_id' => $category->id,
        'cost_price' => 50.00,
        'wholesale_price' => 75.00,
        'retail_price' => 100.00,
        'distributor_price' => 65.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $response->assertRedirect(route('products.index'));

    $product = Product::where('sku', 'PRD-ADM-001')->firstOrFail();
    expect((float) $product->cost_price)->toEqual(50.00);
    expect((float) $product->wholesale_price)->toEqual(75.00);
    expect((float) $product->retail_price)->toEqual(100.00);
    expect((float) $product->distributor_price)->toEqual(65.00);

    // Admin updates prices
    $updateResponse = $this->put(route('products.update', $product), [
        'sku' => 'PRD-ADM-001',
        'name' => 'طوق لؤلؤ فاخر معدل',
        'category_id' => $category->id,
        'cost_price' => 60.00,
        'wholesale_price' => 85.00,
        'retail_price' => 120.00,
        'distributor_price' => 75.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 10,
    ]);
    $updateResponse->assertRedirect(route('products.index'));

    $product->refresh();
    expect((float) $product->cost_price)->toEqual(60.00);
    expect((float) $product->wholesale_price)->toEqual(85.00);
    expect((float) $product->retail_price)->toEqual(120.00);
    expect((float) $product->distributor_price)->toEqual(75.00);
});

test('warehouse manager cannot set prices during creation or modify prices during update and DB is unchanged', function () {
    $warehouseUser = User::create([
        'name' => 'أمين المستودع',
        'email' => 'warehouse-pricing@kayan.test',
        'password' => Hash::make('WarehouseSecret123!'),
        'role' => User::ROLE_WAREHOUSE,
    ]);
    $this->actingAs($warehouseUser);

    $category = Category::create(['name' => 'توك واستيك', 'code' => 'ELASTIC-01']);

    // 1. Warehouse user attempts to create product with prices > 0
    $createResponse = $this->post(route('products.store'), [
        'sku' => 'PRD-WH-TAMPER',
        'name' => 'صنف تلاعب بالمستودع',
        'category_id' => $category->id,
        'cost_price' => 30.00,
        'wholesale_price' => 45.00,
        'retail_price' => 60.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $createResponse->assertForbidden();

    // Verify product was NOT created in DB (no partial changes)
    expect(Product::where('sku', 'PRD-WH-TAMPER')->exists())->toBeFalse();

    // 2. Existing product created by admin
    $admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-creator@kayan.test',
        'password' => Hash::make('AdminSecret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'PRD-EXISTING-01',
        'name' => 'صنف ثابت',
        'cost_price' => 20.00,
        'wholesale_price' => 30.00,
        'retail_price' => 40.00,
        'distributor_price' => 25.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    // Warehouse user attempts to modify price via PUT
    $updateResponse = $this->put(route('products.update', $product), [
        'sku' => 'PRD-EXISTING-01',
        'name' => 'صنف ثابت بعد المحاولة',
        'category_id' => $category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 20.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
    ]);
    $updateResponse->assertForbidden();

    // Verify DB record is completely unchanged
    $product->refresh();
    expect((float) $product->cost_price)->toEqual(20.00);
    expect((float) $product->wholesale_price)->toEqual(30.00);
    expect((float) $product->retail_price)->toEqual(40.00);
    expect($product->name)->toEqual('صنف ثابت');
});

test('warehouse manager cannot update existing item prices via Excel import even with update_existing=true', function () {
    $warehouseUser = User::create([
        'name' => 'أمين المستودع',
        'email' => 'wh-import@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_WAREHOUSE,
    ]);
    $this->actingAs($warehouseUser);

    $category = Category::create(['name' => 'إكسسوارات', 'code' => 'ACC-01']);
    $existingProduct = Product::create([
        'sku' => 'KY-IMP-100',
        'name' => 'صنف سابق',
        'category_id' => $category->id,
        'cost_price' => 100.00,
        'wholesale_price' => 150.00,
        'retail_price' => 200.00,
        'distributor_price' => 130.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    // Simulate reviewed rows trying to update prices
    $reviewedRows = [
        [
            'line' => 2,
            'action' => 'update',
            'sku' => 'KY-IMP-100',
            'name' => 'صنف سابق باسم محدث',
            'category_name' => 'إكسسوارات',
            'barcode' => null,
            'cost_price' => 10.00, // Tampered low price
            'wholesale_price' => 15.00,
            'retail_price' => 20.00,
            'distributor_price' => 12.00,
            'unit' => 'دستة',
            'pieces_per_package' => 12,
            'min_stock_alert' => 10,
            'opening_quantity' => 0.0,
            'opening_cost' => null,
            'opening_warehouse_id' => null,
            'opening_balance_date' => null,
            'errors' => [],
        ],
    ];

    $importService = app(ProductImportService::class);
    $result = $importService->executeImport($reviewedRows, updateExisting: true, createCategories: false);

    expect($result['updated'])->toBe(1);

    // Existing prices must be protected and preserved
    $existingProduct->refresh();
    expect((float) $existingProduct->cost_price)->toEqual(100.00);
    expect((float) $existingProduct->wholesale_price)->toEqual(150.00);
    expect((float) $existingProduct->retail_price)->toEqual(200.00);
    expect((float) $existingProduct->distributor_price)->toEqual(130.00);

    // Non-price fields were updated
    expect($existingProduct->name)->toBe('صنف سابق باسم محدث');
    expect($existingProduct->unit)->toBe('دستة');
});

test('authorized administrator can import and update prices with update_existing=true', function () {
    $admin = User::create([
        'name' => 'مدير النظام',
        'email' => 'admin-import@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role' => User::ROLE_ADMIN,
    ]);
    $this->actingAs($admin);

    $category = Category::create(['name' => 'إكسسوارات', 'code' => 'ACC-02']);
    $existingProduct = Product::create([
        'sku' => 'KY-ADM-IMP-1',
        'name' => 'صنف سابق أصلي',
        'category_id' => $category->id,
        'cost_price' => 50.00,
        'wholesale_price' => 70.00,
        'retail_price' => 90.00,
        'distributor_price' => 60.00,
        'unit' => 'قطعة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $reviewedRows = [
        [
            'line' => 2,
            'action' => 'update',
            'sku' => 'KY-ADM-IMP-1',
            'name' => 'صنف سابق بسعر محدث رسمياً',
            'category_name' => 'إكسسوارات',
            'barcode' => null,
            'cost_price' => 55.00,
            'wholesale_price' => 78.00,
            'retail_price' => 110.00,
            'distributor_price' => 70.00,
            'unit' => 'قطعة',
            'pieces_per_package' => null,
            'min_stock_alert' => 8,
            'opening_quantity' => 0.0,
            'opening_cost' => null,
            'opening_warehouse_id' => null,
            'opening_balance_date' => null,
            'errors' => [],
        ],
    ];

    $importService = app(ProductImportService::class);
    $result = $importService->executeImport($reviewedRows, updateExisting: true, createCategories: false);

    expect($result['updated'])->toBe(1);

    $existingProduct->refresh();
    expect((float) $existingProduct->cost_price)->toEqual(55.00);
    expect((float) $existingProduct->wholesale_price)->toEqual(78.00);
    expect((float) $existingProduct->retail_price)->toEqual(110.00);
    expect((float) $existingProduct->distributor_price)->toEqual(70.00);
});

test('database seeder throws exception if ADMIN_DEFAULT_PASSWORD is missing and preserves existing user passwords', function () {
    $seeder = new DatabaseSeeder;

    // 1. When user does not exist and ADMIN_DEFAULT_PASSWORD is empty/null, it must throw RuntimeException
    User::where('email', env('ADMIN_DEFAULT_EMAIL', 'admin@kayan-erp.com'))->delete();
    $oldPass = env('ADMIN_DEFAULT_PASSWORD');
    unset($_SERVER['ADMIN_DEFAULT_PASSWORD'], $_ENV['ADMIN_DEFAULT_PASSWORD']);
    putenv('ADMIN_DEFAULT_PASSWORD');

    expect(fn () => $seeder->run())
        ->toThrow(RuntimeException::class, 'ADMIN_DEFAULT_PASSWORD must be explicitly configured');

    // 2. When admin already exists, seeder does NOT alter its password
    $originalHash = Hash::make('CustomOriginalPassword123!');
    $adminEmail = env('ADMIN_DEFAULT_EMAIL', 'admin@kayan-erp.com');
    User::create([
        'name' => 'مدير قائم',
        'email' => $adminEmail,
        'password' => $originalHash,
        'role' => User::ROLE_ADMIN,
    ]);

    // Run seeder with dummy env password
    putenv('ADMIN_DEFAULT_PASSWORD=SomeOtherPassword!2026');
    $_ENV['ADMIN_DEFAULT_PASSWORD'] = 'SomeOtherPassword!2026';

    $seeder->run();

    $existingUser = User::where('email', $adminEmail)->first();
    expect(Hash::check('CustomOriginalPassword123!', $existingUser->password))->toBeTrue();
    expect(Hash::check('SomeOtherPassword!2026', $existingUser->password))->toBeFalse();

    // Restore env
    if ($oldPass !== false && $oldPass !== null) {
        putenv("ADMIN_DEFAULT_PASSWORD={$oldPass}");
        $_ENV['ADMIN_DEFAULT_PASSWORD'] = $oldPass;
    } else {
        putenv('ADMIN_DEFAULT_PASSWORD');
        unset($_ENV['ADMIN_DEFAULT_PASSWORD']);
    }
});

test('administrator can assign employee workplace and toggle multi-warehouse access', function () {
    $admin = User::create([
        'name' => 'مدير النظام للتجربة',
        'email' => 'admin-wh@kayan.test',
        'password' => Hash::make('Admin123!'),
        'role' => User::ROLE_ADMIN,
    ]);

    $roleCashier = Role::firstOrCreate(['slug' => 'cashier'], ['name' => 'كاشير']);

    $whBranch1 = Warehouse::create([
        'code' => 'WH-ATABA',
        'name' => 'فرع العتبة',
        'location' => 'القاهرة',
        'is_active' => true,
    ]);

    $whBranch2 = Warehouse::create([
        'code' => 'WH-MAADI',
        'name' => 'فرع المعادي',
        'location' => 'المعادي',
        'is_active' => true,
    ]);

    // 1. Create cashier restricted to Branch 1
    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'كاشير فرع العتبة',
        'email' => 'cashier-ataba@kayan.test',
        'password' => 'Pass1234!',
        'role_id' => $roleCashier->id,
        'warehouse_id' => $whBranch1->id,
        'can_access_all_warehouses' => 0,
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('users.index'));

    $cashier = User::where('email', 'cashier-ataba@kayan.test')->first();
    expect($cashier)->not->toBeNull()
        ->and($cashier->warehouse_id)->toBe($whBranch1->id)
        ->and($cashier->can_access_all_warehouses)->toBeFalse();

    expect($cashier->canAccessWarehouse($whBranch1->id))->toBeTrue();
    expect($cashier->canAccessWarehouse($whBranch2->id))->toBeFalse();

    // 2. Update cashier to grant multi-warehouse permission
    $updateResponse = $this->actingAs($admin)->put(route('users.update', $cashier), [
        'name' => 'كاشير عام متنقل',
        'email' => 'cashier-ataba@kayan.test',
        'role_id' => $roleCashier->id,
        'warehouse_id' => $whBranch1->id,
        'can_access_all_warehouses' => 1,
        'is_active' => 1,
    ]);

    $updateResponse->assertRedirect(route('users.index'));

    $cashier->refresh();
    expect($cashier->can_access_all_warehouses)->toBeTrue();
    expect($cashier->canAccessWarehouse($whBranch2->id))->toBeTrue();
});

test('cashier interface restricts warehouse selection and enforces workplace authorization', function () {
    $roleCashier = Role::firstOrCreate(['slug' => 'cashier'], ['name' => 'كاشير']);

    $whBranch1 = Warehouse::create([
        'code' => 'WH-BR1',
        'name' => 'فرع شبرا',
        'location' => 'شبرا',
        'is_active' => true,
    ]);

    $whBranch2 = Warehouse::create([
        'code' => 'WH-BR2',
        'name' => 'فرع مدينة نصر',
        'location' => 'مدينة نصر',
        'is_active' => true,
    ]);

    $restrictedCashier = User::create([
        'name' => 'كاشير شبرا الملتزم',
        'email' => 'shoubra-pos@kayan.test',
        'password' => Hash::make('Cashier123!'),
        'role' => User::ROLE_CASHIER,
        'role_id' => $roleCashier->id,
        'warehouse_id' => $whBranch1->id,
        'can_access_all_warehouses' => false,
        'is_active' => true,
    ]);

    // Check POS screen renders with only allowed warehouse
    $posResponse = $this->actingAs($restrictedCashier)->get(route('sales.create'));
    $posResponse->assertOk();
    $posResponse->assertSee('فرع شبرا');
    $posResponse->assertDontSee('فرع مدينة نصر');

    // Attempt to post sale from unauthorized branch 2
    $customer = Customer::firstOrCreate(['name' => 'عميل اختبار'], ['phone' => '0100000001', 'customer_type' => 'retail', 'pricing_tier' => 'retail']);
    $cat = Category::firstOrCreate(['name' => 'تصنيف تجريبي']);
    $product = Product::create([
        'category_id' => $cat->id,
        'name' => 'صنف تجريبي للمستودعات',
        'sku' => 'SKU-WH-TEST',
        'cost_price' => 10,
        'retail_price' => 20,
        'wholesale_price' => 15,
        'is_active' => true,
    ]);

    $postSaleResponse = $this->actingAs($restrictedCashier)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'warehouse_id' => $whBranch2->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 20,
            ],
        ],
    ]);

    $postSaleResponse->assertSessionHasErrors(['warehouse_id']);
});

test('quick customer add endpoint creates customer and returns JSON response for cashier modal', function () {
    $admin = User::create([
        'name' => 'مسؤول كاشير',
        'email' => 'admin-quick-cust@kayan.test',
        'password' => Hash::make('Admin123!'),
        'role' => User::ROLE_ADMIN,
    ]);

    $response = $this->actingAs($admin)->postJson(route('customers.store'), [
        'name' => 'محمود مصطفى تاجر',
        'phone' => '01234567890',
        'customer_type' => 'wholesale',
        'pricing_tier' => 'wholesale',
        'city' => 'طنطا',
    ]);

    $response->assertCreated();
    $response->assertJson([
        'success' => true,
        'customer' => [
            'name' => 'محمود مصطفى تاجر',
            'phone' => '01234567890',
            'pricing_tier' => 'wholesale',
        ],
    ]);

    expect(Customer::where('phone', '01234567890')->exists())->toBeTrue();
});

test('cashier granted create-customers-pos permission can add customer and lookup without manage-purchases', function () {
    $perm = Permission::firstOrCreate(
        ['slug' => 'create-customers-pos'],
        ['name' => 'إضافة عميل سريع', 'module' => 'المبيعات ونقاط البيع']
    );

    $cashierRole = Role::firstOrCreate(
        ['slug' => 'cashier'],
        ['name' => 'كاشير نقطة البيع', 'is_system' => true]
    );
    $cashierRole->permissions()->syncWithoutDetaching([$perm->id]);

    $cashier = User::create([
        'name' => 'كاشير المحل',
        'email' => 'cashier-cust@kayan.test',
        'password' => Hash::make('Cashier123!'),
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
    ]);

    // Cashier can lookup customers
    $lookupResponse = $this->actingAs($cashier)->getJson(route('customers.lookup', ['q' => 'محمود']));
    $lookupResponse->assertOk();

    // Cashier can add customer via JSON (POS quick add modal)
    $createResponse = $this->actingAs($cashier)->postJson(route('customers.store'), [
        'name' => 'عميل تجزئة جديد',
        'phone' => '01199887766',
        'customer_type' => 'retail',
        'pricing_tier' => 'retail',
    ]);

    $createResponse->assertCreated();
    $createResponse->assertJson([
        'success' => true,
        'customer' => [
            'name' => 'عميل تجزئة جديد',
            'phone' => '01199887766',
        ],
    ]);

    expect(Customer::where('phone', '01199887766')->exists())->toBeTrue();
});
