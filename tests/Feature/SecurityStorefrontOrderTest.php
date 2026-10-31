<?php

use App\Models\EcommerceSetting;
use App\Models\WebOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    EcommerceSetting::firstOrCreate([], [
        'store_name' => 'متجر كيان',
        'is_store_open' => true,
    ]);
});

test('order number generator produces randomized entropy to prevent sequential enumeration', function () {
    $num1 = WebOrder::generateOrderNumber();
    $num2 = WebOrder::generateOrderNumber();

    expect($num1)->not->toBe($num2);
    expect(strlen($num1))->toBeGreaterThanOrEqual(16);
    expect(str_starts_with($num1, 'WO-'.date('Ymd').'-'))->toBeTrue();
});

test('unauthenticated guest without session ownership has customer PII masked on order success page', function () {
    auth()->logout();

    $order = WebOrder::create([
        'order_number' => WebOrder::generateOrderNumber(),
        'customer_name' => 'أحمد محمد علي',
        'customer_phone' => '01012345678',
        'customer_address' => 'شارع التحرير مبنى 15 شقة 4',
        'customer_city' => 'القاهرة',
        'subtotal' => 200,
        'shipping_cost' => 30,
        'discount_amount' => 0,
        'total' => 230,
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'unpaid',
    ]);

    // Unauthenticated guest without session
    $response = $this->get(route('shop.order.success', $order->order_number));

    $response->assertOk();
    $response->assertDontSee('01012345678');
    $response->assertDontSee('شارع التحرير مبنى 15 شقة 4');
    $response->assertSee('****');
});

test('customer with session ownership can view their unmasked order confirmation', function () {
    auth()->logout();

    $order = WebOrder::create([
        'order_number' => WebOrder::generateOrderNumber(),
        'customer_name' => 'سارة إبراهيم',
        'customer_phone' => '01198765432',
        'customer_address' => 'المعادي مجمع النصر',
        'customer_city' => 'القاهرة',
        'subtotal' => 150,
        'shipping_cost' => 30,
        'discount_amount' => 0,
        'total' => 180,
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'unpaid',
    ]);

    // Simulating session of the customer who just placed the order
    $response = $this->withSession(['order_id' => $order->id])
        ->get(route('shop.order.success', $order->order_number));

    $response->assertOk();
    $response->assertSee('01198765432');
    $response->assertSee('المعادي مجمع النصر');
});
