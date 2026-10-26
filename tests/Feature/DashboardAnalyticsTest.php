<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UnitSeeder::class);
});

test('admin can access dashboard and view the 4 requested KPI cards and manager logs', function () {
    $warehouse = Warehouse::create([
        'name' => 'المستودع الرئيسي',
        'code' => 'WH-MAIN',
        'is_default' => true,
    ]);

    $category = Category::create([
        'name' => 'إكسسوارات',
        'code' => 'CAT-01',
    ]);

    $adminRole = Role::where('slug', 'مدير_للشركه')->first() ?? Role::where('slug', 'admin')->first();

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'role_id' => $adminRole ? $adminRole->id : null,
        'can_access_all_warehouses' => true,
    ]);

    // Create a product and stock
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TEST-001',
        'name' => 'منتج اختباري تجريبي',
        'cost_price' => 100.00,
        'retail_price' => 150.00,
        'wholesale_price' => 130.00,
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    StockBalance::create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
    ]);

    // Create customer and posted sales invoice
    $customer = Customer::create([
        'name' => 'عميل اختبار',
        'phone' => '01000000001',
        'customer_type' => 'retail',
        'current_balance' => 500.00,
    ]);

    SalesInvoice::create([
        'invoice_number' => 'INV-TEST-001',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'status' => SalesInvoice::STATUS_POSTED,
        'subtotal' => 300.00,
        'total_amount' => 300.00,
        'paid_amount' => 300.00,
        'remaining_amount' => 0.00,
        'total_cogs' => 200.00,
        'total_profit' => 100.00,
        'created_by' => $admin->id,
    ]);

    // Create supplier and purchase invoice
    $supplier = Supplier::create([
        'name' => 'مورد اختبار',
        'supplier_type' => 'china_factory',
        'currency' => 'EGP',
        'current_balance' => 0.00,
    ]);

    PurchaseInvoice::create([
        'invoice_number' => 'PUR-TEST-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => PurchaseInvoice::STATUS_POSTED,
        'subtotal' => 2000.00,
        'total_amount' => 2000.00,
        'paid_amount' => 2000.00,
        'remaining_amount' => 0.00,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();

    // Verify 4 main KPI cards exist
    $response->assertSee('إجمالي رصيد المخزن');
    $response->assertSee('إجمالي فواتير المشتريات');
    $response->assertSee('إجمالي فواتير المبيعات');
    $response->assertSee('الأرباح المتوقعة قبل المصاريف');

    // Verify Manager Operations Log exists
    $response->assertSee('سجل آخر عمليات للمدير');
    $response->assertSee('آخر المبيعات');
    $response->assertSee('حركات المخزن');
    $response->assertSee('آخر المشتريات');

    // Verify live invoice data is visible in recent operations
    $response->assertSee('INV-TEST-001');
    $response->assertSee('عميل اختبار');
    $response->assertSee('PUR-TEST-001');
    $response->assertSee('مورد اختبار');
});
