<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Tenant E-Commerce Storefront.
     */
    public function up(): void
    {
        // 1. Add E-commerce fields to products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_available_online')->default(false)->after('is_active')->index();
            $table->decimal('online_price', 12, 2)->nullable()->after('is_available_online');
            $table->text('online_description')->nullable()->after('online_price');
            $table->boolean('is_featured_online')->default(false)->after('online_description');
        });

        // 2. Create E-Commerce Store Settings table
        Schema::create('ecommerce_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->nullable();
            $table->string('store_tagline')->nullable();
            $table->string('store_logo')->nullable();
            $table->string('store_banner')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->decimal('shipping_flat_rate', 10, 2)->default(30.00);
            $table->decimal('free_shipping_threshold', 10, 2)->default(500.00);
            $table->boolean('is_store_open')->default(true);
            $table->boolean('allow_cod')->default(true);
            $table->string('announcement_text')->nullable();
            $table->timestamps();
        });

        // 3. Create Web Orders table
        Schema::create('web_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->string('customer_city')->nullable();
            $table->text('customer_address');
            $table->text('customer_notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 30)->default('pending')->index(); // pending, confirmed, processing, shipped, delivered, cancelled
            $table->string('payment_method', 30)->default('cod'); // cod, google_pay, online
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, paid
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Create Web Order Items table
        Schema::create('web_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_order_id')->constrained('web_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->string('unit')->default('قطعة');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_order_items');
        Schema::dropIfExists('web_orders');
        Schema::dropIfExists('ecommerce_settings');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_available_online', 'online_price', 'online_description', 'is_featured_online']);
        });
    }
};
