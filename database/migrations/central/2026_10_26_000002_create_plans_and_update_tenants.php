<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('central')->create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->integer('max_users')->default(3);
            $table->json('modules')->nullable();
            $table->string('badge_text')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan_name');
            $table->integer('max_users')->default(3)->after('plan_id');
            $table->string('billing_cycle')->default('monthly')->after('max_users');
            $table->decimal('subscription_price', 10, 2)->default(0)->after('billing_cycle');
            $table->timestamp('starts_at')->nullable()->after('subscription_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['plan_id', 'max_users', 'billing_cycle', 'subscription_price', 'starts_at']);
        });

        Schema::connection('central')->dropIfExists('plans');
    }
};
