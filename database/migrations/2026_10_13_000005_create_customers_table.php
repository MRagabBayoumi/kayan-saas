<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('company_name')->nullable();
            $table->string('phone')->index();
            $table->string('phone_alt')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('address')->nullable();
            $table->enum('customer_type', ['wholesale', 'retail', 'distributor'])->default('wholesale')->index();
            $table->enum('pricing_tier', ['wholesale', 'retail', 'distributor'])->default('wholesale');
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->decimal('current_balance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
