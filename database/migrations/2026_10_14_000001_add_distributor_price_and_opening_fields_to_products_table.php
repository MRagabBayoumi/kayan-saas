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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('distributor_price', 12, 2)->nullable()->after('retail_price');
            $table->unsignedInteger('pieces_per_package')->nullable()->after('unit');
            $table->decimal('opening_quantity', 12, 2)->default(0.00)->after('min_stock_alert');
            $table->decimal('opening_cost', 12, 2)->nullable()->after('opening_quantity');
            $table->foreignId('opening_warehouse_id')->nullable()->after('opening_cost')->constrained('warehouses')->nullOnDelete();
            $table->date('opening_balance_date')->nullable()->after('opening_warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['opening_warehouse_id']);
            $table->dropColumn([
                'distributor_price',
                'pieces_per_package',
                'opening_quantity',
                'opening_cost',
                'opening_warehouse_id',
                'opening_balance_date',
            ]);
        });
    }
};
