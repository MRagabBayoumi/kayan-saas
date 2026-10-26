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
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('retail_price', 12, 2)->nullable()->after('cost_price');
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('retail_price');
            $table->decimal('distributor_price', 12, 2)->nullable()->after('wholesale_price');
            $table->decimal('total_initial_cost', 12, 2)->default(0.00)->after('distributor_price');
            $table->decimal('accumulated_cogs_deducted', 12, 2)->default(0.00)->after('total_initial_cost');
            $table->unsignedBigInteger('unit_id')->nullable()->after('accumulated_cogs_deducted');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('unit_id');
            $table->string('source_type', 50)->default('purchase_invoice')->after('conversion_factor');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn([
                'retail_price',
                'wholesale_price',
                'distributor_price',
                'total_initial_cost',
                'accumulated_cogs_deducted',
                'unit_id',
                'conversion_factor',
                'source_type',
                'source_id',
            ]);
        });
    }
};
