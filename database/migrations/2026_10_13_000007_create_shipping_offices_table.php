<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->enum('shipping_type', ['international_china', 'domestic_egypt'])->default('international_china')->index();
            $table->string('contact_person')->nullable();
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->string('address')->nullable();
            $table->decimal('rate_per_cbm_or_kg', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_offices');
    }
};
