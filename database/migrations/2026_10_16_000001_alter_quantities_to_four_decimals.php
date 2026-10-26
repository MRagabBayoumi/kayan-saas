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
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->default(0.0000)->change();
            $table->decimal('reserved_quantity', 14, 4)->default(0.0000)->change();
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 14, 4)->change();
            $table->decimal('remaining_quantity', 14, 4)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->change();
            $table->decimal('running_balance', 14, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->default(0.00)->change();
            $table->decimal('reserved_quantity', 12, 2)->default(0.00)->change();
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 12, 2)->change();
            $table->decimal('remaining_quantity', 12, 2)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
            $table->decimal('running_balance', 12, 2)->nullable()->change();
        });
    }
};
