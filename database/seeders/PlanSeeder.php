<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $starter = Plan::updateOrCreate(
            ['code' => 'starter'],
            [
                'name' => 'الباقة الأساسية (كاشير ومخزون)',
                'description' => 'مثالية للمحلات ونقاط البيع الفردية، تشمل نقاط البيع السريعة، إدارة المخازن، والطباعة الحرارية.',
                'price_monthly' => 299,
                'price_yearly' => 2990,
                'max_users' => 2,
                'modules' => ['accounting' => false, 'sales_reps' => false, 'installments' => false],
                'badge_text' => 'للمحلات الصغيرة',
                'is_featured' => false,
                'is_active' => true,
            ]
        );

        $pro = Plan::updateOrCreate(
            ['code' => 'professional'],
            [
                'name' => 'الباقة المتقدمة (محاسبة وأقساط)',
                'description' => 'للشركات التجارية والأنشطة المتنامية، شجرة حسابات متكاملة، قيود يومية، وجداول مواعيد أقساط العملاء والموردين.',
                'price_monthly' => 599,
                'price_yearly' => 5990,
                'max_users' => 5,
                'modules' => ['accounting' => true, 'sales_reps' => false, 'installments' => true],
                'badge_text' => 'الأكثر طلباً ⭐',
                'is_featured' => true,
                'is_active' => true,
            ]
        );

        $enterprise = Plan::updateOrCreate(
            ['code' => 'enterprise'],
            [
                'name' => 'الباقة الاحترافية (ERP شامل + مناديب)',
                'description' => 'المنظومة الكاملة: مبيعات، مخازن، محاسبة مالية، وبوابة مناديب المبيعات الميدانية مع تتبع المواقع GPS وتطبيق الموبايل.',
                'price_monthly' => 999,
                'price_yearly' => 9990,
                'max_users' => 15,
                'modules' => ['accounting' => true, 'sales_reps' => true, 'installments' => true],
                'badge_text' => 'كل المميزات VIP',
                'is_featured' => false,
                'is_active' => true,
            ]
        );

        // Update existing demo tenants
        $t1 = Tenant::where('subdomain', 'demo1')->first();
        if ($t1) {
            $t1->update([
                'plan_id' => $enterprise->id,
                'plan_name' => $enterprise->name,
                'max_users' => 15,
                'subscription_price' => 999,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);
        }

        $t2 = Tenant::where('subdomain', 'demo2')->first();
        if ($t2) {
            $t2->update([
                'plan_id' => $starter->id,
                'plan_name' => $starter->name,
                'max_users' => 2,
                'subscription_price' => 299,
                'starts_at' => now(),
                'expires_at' => now()->addDays(15),
            ]);
        }
    }
}
