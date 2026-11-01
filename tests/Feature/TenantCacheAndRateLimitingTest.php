<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('identify tenant middleware caches tenant resolution to avoid repeated database queries', function () {
    $subdomain = 'corp'.uniqid();
    $cacheKey = "tenant_subdomain_{$subdomain}";

    Cache::forget($cacheKey);

    $tenant = Tenant::create([
        'name' => 'شركة تجريبية',
        'subdomain' => $subdomain,
        'database_name' => 'kayan_tenant_'.$subdomain,
        'is_active' => true,
    ]);

    expect(Cache::has($cacheKey))->toBeFalse();

    // First request triggers DB resolution and populates cache
    $this->get("http://{$subdomain}.lvh.me/shop");

    expect(Cache::has($cacheKey))->toBeTrue();
    $cached = Cache::get($cacheKey);
    expect($cached)->not->toBeNull();
    expect($cached->subdomain)->toBe($subdomain);

    // Modifying tenant triggers cache purge via model booted hook
    $tenant->update(['name' => 'شركة تجريبية محدثة']);
    expect(Cache::has($cacheKey))->toBeFalse();

    // Cleanup
    $tenant->delete();
    Cache::forget($cacheKey);
});

test('tenant login route is protected by rate limiting against brute force attempts', function () {
    $subdomain = 'tenant'.uniqid();
    $tenant = Tenant::create([
        'name' => 'مستأجر للاختبار',
        'subdomain' => $subdomain,
        'database_name' => 'kayan_tenant_'.$subdomain,
        'is_active' => true,
    ]);

    auth()->logout();

    // Fire 10 attempts on tenant subdomain
    for ($i = 0; $i < 10; $i++) {
        $this->postJson("http://{$subdomain}.lvh.me/login", [
            'login' => 'wrong_user_'.$i,
            'password' => 'wrong_pass',
        ]);
    }

    // 11th attempt within the same minute should be throttled (HTTP 429 Too Many Requests)
    $response = $this->postJson("http://{$subdomain}.lvh.me/login", [
        'login' => 'wrong_user_11',
        'password' => 'wrong_pass',
    ]);

    $response->assertStatus(429);

    $tenant->delete();
});
