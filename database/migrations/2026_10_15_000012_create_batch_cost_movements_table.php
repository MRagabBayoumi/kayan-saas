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
        Schema::create('batch_cost_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('inventory_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('movement_type', 40)->index(); // opening_in, purchase_in, sale_out, sale_cancellation_reversal, sale_return_in, purchase_return_out, cost_adjustment
            $table->string('reference_type', 80)->nullable()->index(); // sales_invoice, purchase_invoice, sales_return, product
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->decimal('quantity_base', 12, 4); // signed: positive for inward, negative for outward
            $table->decimal('unit_cost_base', 12, 4);
            $table->decimal('total_cost', 12, 2); // signed: positive for addition, negative for reduction
            $table->decimal('running_batch_quantity', 12, 4); // remaining quantity in this batch after movement
            $table->decimal('running_batch_cost_balance', 12, 2); // remaining financial valuation of this batch
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_cost_movements');
    }
};
