<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
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

test('product can be deactivated and activated via toggleStatus and update', function () {
    $adminRole = Role::where('slug', 'مدير_للشركه')->first() ?? Role::where('slug', 'admin')->first();
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'role_id' => $adminRole ? $adminRole->id : null,
    ]);

    $category = Category::create(['name' => 'تصنيف تجريبي', 'code' => 'TEST-CAT']);
    $product = Product::create([
        'category_id' => $category->id,
        'sku' => 'TOGGLE-001',
        'name' => 'منتج للتعطيل والتنشيط',
        'cost_price' => 50,
        'retail_price' => 80,
        'wholesale_price' => 70,
        'unit' => 'قطعة',
        'pieces_per_package' => 1,
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    // 1. Toggle status to deactivate
    $response = $this->actingAs($admin)->post(route('products.toggle-status', $product));
    $response->assertRedirect();
    $product->refresh();
    expect($product->is_active)->toBeFalse();

    // 2. Toggle status again to activate
    $response = $this->actingAs($admin)->post(route('products.toggle-status', $product));
    $response->assertRedirect();
    $product->refresh();
    expect($product->is_active)->toBeTrue();

    // 3. Update without is_active in request (simulating unchecked checkbox)
    $response = $this->actingAs($admin)->put(route('products.update', $product), [
        'category_id' => $category->id,
        'sku' => 'TOGGLE-001',
        'name' => 'منتج للتعطيل والتنشيط معدل',
        'cost_price' => 50,
        'retail_price' => 80,
        'wholesale_price' => 70,
        'unit' => 'قطعة',
        'pieces_per_package' => 1,
        'min_stock_alert' => 5,
        // is_active omitted -> should deactivate!
    ]);
    $response->assertRedirect(route('products.index'));
    $product->refresh();
    expect($product->is_active)->toBeFalse();
});

test('draft sales invoice can be cancelled and deleted', function () {
    $adminRole = Role::where('slug', 'مدير_للشركه')->first() ?? Role::where('slug', 'admin')->first();
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'role_id' => $adminRole ? $adminRole->id : null,
    ]);

    $warehouse = Warehouse::create(['name' => 'مخزن رئيسي', 'code' => 'WH-TEST', 'is_default' => true]);
    $customer = Customer::create(['name' => 'عميل تجريبي', 'phone' => '01012345678', 'customer_type' => 'retail']);

    $invoice = SalesInvoice::create([
        'invoice_number' => 'INV-DRAFT-001',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'price_tier' => 'retail',
        'payment_type' => 'cash',
        'status' => SalesInvoice::STATUS_DRAFT,
        'subtotal' => 100,
        'total_amount' => 100,
        'created_by' => $admin->id,
    ]);

    // 1. Cancel draft
    $response = $this->actingAs($admin)->post(route('sales.void', $invoice));
    $response->assertRedirect();
    $invoice->refresh();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_VOIDED);

    // 2. Delete invoice
    $response = $this->actingAs($admin)->delete(route('sales.destroy', $invoice));
    $response->assertRedirect(route('sales.index'));
    expect(SalesInvoice::find($invoice->id))->toBeNull();
});

test('cancelled purchase invoice can be deleted', function () {
    $adminRole = Role::where('slug', 'مدير_للشركه')->first() ?? Role::where('slug', 'admin')->first();
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'role_id' => $adminRole ? $adminRole->id : null,
    ]);

    $warehouse = Warehouse::create(['name' => 'مخزن رئيسي', 'code' => 'WH-TEST', 'is_default' => true]);
    $supplier = Supplier::create(['name' => 'مورد تجريبي', 'supplier_type' => 'china_factory', 'currency' => 'EGP']);

    $purchase = PurchaseInvoice::create([
        'invoice_number' => 'PUR-TEST-999',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'invoice_date' => now()->toDateString(),
        'payment_type' => 'cash',
        'status' => PurchaseInvoice::STATUS_CANCELLED,
        'subtotal' => 500,
        'total_amount' => 500,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->delete(route('purchases.destroy', $purchase));
    $response->assertRedirect(route('purchases.index'));
    expect(PurchaseInvoice::find($purchase->id))->toBeNull();
});
