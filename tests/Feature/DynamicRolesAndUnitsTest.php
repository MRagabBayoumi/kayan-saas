<?php

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UnitSeeder::class);

    $this->admin = User::firstOrCreate(
        ['email' => 'admin@kayan-erp.com'],
        [
            'name' => 'مدير النظام',
            'password' => Hash::make('Admin@Kayan2026!'),
            'role' => User::ROLE_ADMIN,
            'role_id' => Role::where('slug', 'admin')->first()->id,
        ]
    );
});

test('admin can access users index and create a new user with an assigned role', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('users.index'));
    $response->assertOk()
        ->assertSee('إدارة المستخدمين وحسابات الدخول')
        ->assertSee('مدير النظام');

    $warehouseRole = Role::where('slug', 'warehouse')->first();

    $createResponse = $this->post(route('users.store'), [
        'name' => 'محمد أمين المستودع',
        'email' => 'mohamed.wh@kayan.test',
        'password' => 'Warehouse123!',
        'role_id' => $warehouseRole->id,
        'is_active' => true,
    ]);

    $createResponse->assertRedirect(route('users.index'));

    $user = User::where('email', 'mohamed.wh@kayan.test')->firstOrFail();
    expect($user->role_id)->toBe($warehouseRole->id);
    expect($user->isWarehouse())->toBeTrue();
    expect($user->isAdmin())->toBeFalse();
    expect($user->is_active)->toBeTrue();
});

test('admin can create a custom dynamic role and grant specific permissions', function () {
    $this->actingAs($this->admin);

    $viewProducts = Permission::where('slug', 'view-products')->first();
    $accessInventory = Permission::where('slug', 'access-inventory')->first();

    $response = $this->post(route('roles.store'), [
        'name' => 'مراقب مخزون ميداني',
        'description' => 'دور مخصص للاطلاع على أرصدة المخازن والمنتجات فقط',
        'permissions' => [$viewProducts->id, $accessInventory->id],
    ]);

    $response->assertRedirect(route('roles.index'));

    $customRole = Role::where('name', 'مراقب مخزون ميداني')->firstOrFail();
    expect($customRole->permissions)->toHaveCount(2);
    expect($customRole->hasPermission('view-products'))->toBeTrue();
    expect($customRole->hasPermission('access-inventory'))->toBeTrue();
    expect($customRole->hasPermission('edit-product-prices'))->toBeFalse();
    expect($customRole->hasPermission('manage-company-settings'))->toBeFalse();
});

test('user assigned to a custom role has exactly the dynamically assigned permissions', function () {
    $customRole = Role::create([
        'name' => 'مسؤول استعلامات',
        'slug' => 'inquiry_officer',
        'description' => 'عرض المنتجات فقط',
        'is_system' => false,
    ]);

    $viewProducts = Permission::where('slug', 'view-products')->first();
    $customRole->permissions()->sync([$viewProducts->id]);

    $user = User::create([
        'name' => 'موظف استعلامات',
        'email' => 'inquiry@kayan.test',
        'password' => Hash::make('Secret123!'),
        'role_id' => $customRole->id,
        'role' => $customRole->slug,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // Permitted through dynamic role
    $this->get(route('products.index'))->assertOk();

    // Forbidden actions not in dynamic role
    $this->get(route('settings.index'))->assertForbidden();
    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('roles.index'))->assertForbidden();
    $this->get(route('units.index'))->assertForbidden();
    $this->get(route('inventory.index'))->assertForbidden();
    $this->get(route('purchases.index'))->assertForbidden();
});

test('system roles cannot be deleted', function () {
    $this->actingAs($this->admin);

    $adminRole = Role::where('slug', 'admin')->first();
    $warehouseRole = Role::where('slug', 'warehouse')->first();

    $response1 = $this->delete(route('roles.destroy', $adminRole));
    $response1->assertRedirect(route('roles.index'));
    expect(Role::where('slug', 'admin')->exists())->toBeTrue();

    $response2 = $this->delete(route('roles.destroy', $warehouseRole));
    $response2->assertRedirect(route('roles.index'));
    expect(Role::where('slug', 'warehouse')->exists())->toBeTrue();
});

test('admin can manage dynamic units of measurement', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('units.index'));
    $response->assertOk()
        ->assertSee('إدارة وحدات القياس ومعاملات التحويل')
        ->assertSee('قطعة')
        ->assertSee('دستة');

    // Create a new dynamic unit: Dozen 12
    $piece = Unit::where('name', 'قطعة')->first();

    $createResponse = $this->post(route('units.store'), [
        'name' => 'كرتونة 48 قطعة',
        'code' => 'CTN48',
        'base_unit_id' => $piece->id,
        'operator' => '*',
        'conversion_factor' => 48.0000,
        'is_active' => true,
        'notes' => 'كرتونة استيراد كبرى تحتوي على 48 قطعة',
    ]);

    $createResponse->assertRedirect(route('units.index'));

    $unit = Unit::where('name', 'كرتونة 48 قطعة')->firstOrFail();
    expect((float) $unit->conversion_factor)->toEqual(48.0000);
    expect($unit->toBaseQuantity(2))->toEqual(96.00);

    // Update unit
    $updateResponse = $this->put(route('units.update', $unit), [
        'name' => 'كرتونة 48 قطعة معدلة',
        'code' => 'CTN48-MOD',
        'base_unit_id' => $piece->id,
        'operator' => '*',
        'conversion_factor' => 50.0000,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect(route('units.index'));
    $unit->refresh();
    expect($unit->name)->toBe('كرتونة 48 قطعة معدلة');
    expect((float) $unit->conversion_factor)->toEqual(50.0000);
});

test('base default unit cannot be deleted', function () {
    $this->actingAs($this->admin);

    $piece = Unit::where('name', 'قطعة')->first();
    expect($piece->is_default)->toBeTrue();

    $response = $this->delete(route('units.destroy', $piece));
    $response->assertRedirect(route('units.index'));
    expect(Unit::where('name', 'قطعة')->exists())->toBeTrue();
});

test('unit linked to products cannot be deleted', function () {
    $this->actingAs($this->admin);

    $unit = Unit::where('name', 'دستة')->first();

    // Create category and product using this unit
    $category = Category::create(['name' => 'تصنيف وحدات', 'code' => 'CAT-UNIT']);
    Product::create([
        'sku' => 'PRD-UNIT-TEST',
        'name' => 'صنف مرتبط بوحدة',
        'category_id' => $category->id,
        'cost_price' => 10.00,
        'wholesale_price' => 15.00,
        'retail_price' => 20.00,
        'unit' => 'دستة',
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $response = $this->delete(route('units.destroy', $unit));
    $response->assertRedirect(route('units.index'));
    expect(Unit::where('name', 'دستة')->exists())->toBeTrue();
});
