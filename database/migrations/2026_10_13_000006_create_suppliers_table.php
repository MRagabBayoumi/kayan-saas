<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->enum('supplier_type', ['china_factory', 'local_supplier', 'shipping_agent'])->default('china_factory')->index();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('wechat_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('country')->default('China')->index();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('currency', 10)->default('CNY'); // CNY, EGP, USD
            $table->decimal('current_balance', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
