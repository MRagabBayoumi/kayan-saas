<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SaasSuperUserAndCompanySettingSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_generate_sso_token_and_impersonate_tenant(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $subdomain = 'naqaa' . strtolower(uniqid());

        $tenant = Tenant::create([
            'name' => 'شركة النقاء التجارية',
            'subdomain' => $subdomain,
            'database_name' => 'kayan_tenant_' . $subdomain,
            'plan_name' => 'باقة متقدمة',
            'is_active' => true,
            'billing_cycle' => 'monthly',
            'max_users' => 5,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('saas.tenants.impersonate', $tenant));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString($subdomain, $targetUrl);
        $this->assertStringContainsString('/saas/sso-login?token=', $targetUrl);
    }

    public function test_tenant_sso_login_authenticates_superuser_and_redirects_to_dashboard(): void
    {
        $subdomain = 'sso' . strtolower(uniqid());

        $tenant = Tenant::create([
            'name' => 'شركة النقاء للتجارة',
            'subdomain' => $subdomain,
            'database_name' => 'kayan_tenant_' . $subdomain,
            'is_active' => true,
        ]);

        $token = 'test_sso_token_' . uniqid();
        cache()->put("sso_impersonate_{$token}", [
            'tenant_id' => $tenant->id,
            'subdomain' => $tenant->subdomain,
            'admin_id' => 1,
            'admin_name' => 'مدير SaaS العام',
            'admin_email' => 'admin@kayan-saas.com',
            'created_at' => now()->timestamp,
        ], 120);

        $response = $this->get("http://{$tenant->subdomain}.lvh.me/saas/sso-login?token={$token}");

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertTrue(session('is_impersonating'));
    }

    public function test_tenant_login_screen_displays_tenant_company_name(): void
    {
        $subdomain = 'salam' . strtolower(uniqid());

        $tenant = Tenant::create([
            'name' => 'مؤسسة السلام العالمية',
            'subdomain' => $subdomain,
            'database_name' => 'kayan_tenant_' . $subdomain,
            'is_active' => true,
        ]);

        $response = $this->get("http://{$tenant->subdomain}.lvh.me/login");

        $response->assertStatus(200);
        $response->assertSee('مؤسسة السلام العالمية');
    }
}
