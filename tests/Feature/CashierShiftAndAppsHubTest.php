<?php

use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
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

test('cashier cannot access dashboard and is redirected to home', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = $this->actingAs($cashier)->get('/dashboard');
    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error');
});

test('company manager or admin can access cashier pos screen', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $companyManagerRole = Role::where('slug', 'مدير_للشركه')->first();

    $manager = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'role_id' => $companyManagerRole ? $companyManagerRole->id : null,
        'can_access_all_warehouses' => true,
    ]);

    $response = $this->actingAs($manager)->get(route('sales.create'));
    $response->assertOk();
    $response->assertSee('شاشة نقاط البيع وفاتورة المبيعات (POS)');
});

test('home page displays applications hub filtered by user permissions', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = $this->actingAs($cashier)->get(route('home'));
    $response->assertOk();
    $response->assertSee('تطبيقات BayoSoft ERP');
    $response->assertSee('نقطة البيع (الكاشير - POS)');
    $response->assertDontSee('لوحة المؤشرات والتحليلات (Dashboard)');
});

test('cashier can open shift, record movements and close shift with variance tracking', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    // 1. Open Shift
    $openResponse = $this->actingAs($cashier)->post(route('shifts.store'), [
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 350.00,
        'opening_notes' => 'استلام العهدة الصباحية',
    ]);

    $openResponse->assertRedirect(route('sales.create'));
    $this->assertDatabaseHas('cashier_shifts', [
        'user_id' => $cashier->id,
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 350.00,
        'status' => 'open',
    ]);

    $shift = CashierShift::getActiveShift($cashier->id);
    expect($shift)->not->toBeNull();
    expect($shift->expected_closing_balance)->toEqual(350.00);

    // 2. Record Cash In
    $this->actingAs($cashier)->post(route('shifts.movement', $shift), [
        'movement_type' => 'cash_in',
        'amount' => 100.00,
        'notes' => 'إيداع فكة إضافية',
    ]);

    $shift->refresh();
    expect($shift->cash_additions)->toEqual(100.00);
    expect($shift->expected_closing_balance)->toEqual(450.00);

    // 3. Manager to supervise closing
    $manager = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'password' => bcrypt('password123'),
    ]);

    // Cashier without manager approval is blocked
    $blockedResponse = $this->actingAs($cashier)->post(route('shifts.close', $shift), [
        'actual_closing_balance' => 455.00,
        'closing_notes' => 'محاولة إغلاق بدون اعتماد المشرف',
    ]);
    $blockedResponse->assertSessionHas('error');
    expect($shift->fresh()->status)->toBe('open');

    // 4. Close Shift with supervisor approval
    $closeResponse = $this->actingAs($cashier)->post(route('shifts.close', $shift), [
        'actual_closing_balance' => 455.00,
        'closing_notes' => 'جرد نهاية الوردية',
        'supervisor_email' => $manager->email,
        'supervisor_password' => 'password123',
    ]);

    $closeResponse->assertRedirect(route('shifts.show', $shift));
    $shift->refresh();
    expect($shift->status)->toBe('closed');
    expect($shift->actual_closing_balance)->toEqual(455.00);
    expect($shift->difference)->toEqual(5.00);
    expect($shift->closed_by)->toBe($manager->id);
});

test('manager can open shift on behalf of an eligible cashier', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'name' => 'كاشير أحمد',
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $manager = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'can_access_all_warehouses' => true,
    ]);

    $response = $this->actingAs($manager)->post(route('shifts.store'), [
        'user_id' => $cashier->id,
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 200.00,
        'opening_notes' => 'فتح وردية للكاشير أحمد بواسطة المشرف',
    ]);

    $response->assertRedirect(route('shifts.index'));
    $this->assertDatabaseHas('cashier_shifts', [
        'user_id' => $cashier->id,
        'opened_by' => $manager->id,
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 200.00,
        'status' => 'open',
    ]);
});

test('regular cashier cannot open shift on behalf of another user', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier1 = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $cashier2 = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    // Cashier1 tries to pass user_id = cashier2
    $response = $this->actingAs($cashier1)->post(route('shifts.store'), [
        'user_id' => $cashier2->id,
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 150.00,
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('cashier_shifts', [
        'user_id' => $cashier2->id,
    ]);
});

test('manager cannot open second active shift for a cashier who already has one', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $manager = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'can_access_all_warehouses' => true,
    ]);

    CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $cashier->id,
        'warehouse_id' => $warehouse->id,
        'opened_at' => now(),
        'opening_balance' => 100.00,
        'status' => 'open',
    ]);

    $response = $this->actingAs($manager)->post(route('shifts.store'), [
        'user_id' => $cashier->id,
        'warehouse_id' => $warehouse->id,
        'opening_balance' => 200.00,
    ]);

    $response->assertSessionHas('error');
});

test('shift report can be printed in a4, a5 and thermal formats', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $manager = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $shift = CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $manager->id,
        'warehouse_id' => $warehouse->id,
        'opened_at' => now(),
        'opening_balance' => 500.00,
        'status' => 'open',
    ]);

    foreach (['a4', 'a5', 'thermal'] as $format) {
        $response = $this->actingAs($manager)->get(route('shifts.print', [$shift, 'format' => $format]));
        $response->assertOk();
        $response->assertSee('وردية');
    }
});

test('cashier shifts index page renders daily expense button and responsive filters', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $manager = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($manager)->get(route('shifts.index'));

    $response->assertOk();
    $response->assertSee('مصاريف يوم (من الوردية)');
    $response->assertSee('تسجيل مصروف يومي من درج الكاشير');
    $response->assertSee('name="date"', false);
    $response->assertSee('name="status"', false);
});

test('cashier can record daily expense paid directly out of active shift cash drawer', function () {
    $warehouse = Warehouse::create(['name' => 'المستودع الرئيسي', 'code' => 'WH-MAIN', 'is_default' => true]);
    $cashierRole = Role::where('slug', 'cashier')->first();

    $cashier = User::factory()->create([
        'role' => User::ROLE_CASHIER,
        'role_id' => $cashierRole->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $shift = CashierShift::create([
        'shift_number' => CashierShift::generateShiftNumber(),
        'user_id' => $cashier->id,
        'warehouse_id' => $warehouse->id,
        'opened_at' => now(),
        'opening_balance' => 300.00,
        'status' => 'open',
    ]);

    $category = ExpenseCategory::create([
        'name' => 'مصاريف بوفية وضيافة',
        'code' => 'EXP-CAT-01',
        'is_active' => true,
    ]);

    $response = $this->actingAs($cashier)->post(route('expenses.store'), [
        'expense_category_id' => $category->id,
        'amount' => 45.00,
        'expense_date' => now()->format('Y-m-d'),
        'payment_method' => 'cash',
        'pay_from_shift' => '1',
        'cashier_shift_id' => $shift->id,
        'paid_to' => 'بوفية الشاي',
        'notes' => 'شراء سكر وشاي للوردية',
    ]);

    $response->assertSessionHas('success');

    $shift->refresh();
    expect((float) $shift->cash_expenses)->toBe(45.00);
    expect((float) $shift->expected_closing_balance)->toBe(255.00);

    $expense = Expense::latest('id')->first();
    expect($expense)->not->toBeNull();
    expect((float) $expense->amount)->toBe(45.00);
    expect($expense->cashier_shift_id)->toBe($shift->id);
    expect($expense->paid_to)->toBe('بوفية الشاي');
});

test('shifts index blade template strictly enforces zero inline js and zero inline css separation', function () {
    $content = file_get_contents(resource_path('views/shifts/index.blade.php'));
    $eventHandlers = ['click', 'change', 'submit', 'input', 'keydown', 'keyup', 'keypress', 'load', 'error', 'mouseover', 'mouseout'];
    $eventPattern = '/\bon(?:'.implode('|', $eventHandlers).')\s*=/i';

    expect(preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $content))->toBe(0);
    expect(preg_match($eventPattern, $content))->toBe(0);
    expect(preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $content))->toBe(0);
    expect(preg_match('/<[^>]*\bstyle\s*=\s*["\'][^"\']*["\'][^>]*>/is', $content))->toBe(0);
});
