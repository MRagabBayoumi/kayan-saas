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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 60)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('invoice_date')->index();
            $table->string('price_tier', 30)->default('retail')->index(); // retail, wholesale, distributor
            $table->string('status', 30)->default('draft')->index(); // draft, posted, voided
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->string('discount_type', 20)->default('fixed'); // fixed, percentage
            $table->decimal('discount_value', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('total_cogs', 12, 2)->default(0.00);
            $table->decimal('total_profit', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('conversion_factor', 10, 4)->default(1.0000);
            $table->decimal('quantity', 12, 4); // transaction unit quantity
            $table->decimal('base_quantity', 12, 4); // base unit quantity
            $table->decimal('unit_price', 12, 2)->default(0.00); // transaction unit price
            $table->decimal('line_discount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('total_cogs', 12, 2)->default(0.00);
            $table->decimal('total_profit', 12, 2)->default(0.00);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('sales_invoice_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_item_id')->constrained('sales_invoice_items')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->restrictOnDelete();
            $table->decimal('quantity_base', 12, 4);
            $table->decimal('unit_cost_base', 12, 4);
            $table->decimal('total_cogs', 12, 2);
            $table->decimal('batch_tier_price_base', 12, 4)->default(0.00);
            $table->decimal('actual_selling_price_base', 12, 4)->default(0.00);
            $table->decimal('subtotal_sold', 12, 2)->default(0.00);
            $table->decimal('profit_margin', 12, 2)->default(0.00);
            $table->boolean('is_reversed')->default(false)->index();
            $table->dateTime('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_item_batches');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
