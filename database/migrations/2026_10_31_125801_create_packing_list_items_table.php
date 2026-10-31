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
        Schema::create('packing_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_list_id')->constrained('packing_lists')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku', 100)->nullable()->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('product_name', 255);
            $table->string('category_name', 100)->nullable();
            $table->string('unit', 50)->default('قطعة');
            $table->decimal('quantity', 14, 2)->default(1);
            $table->integer('packages_count')->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('notes', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_list_items');
    }
};
