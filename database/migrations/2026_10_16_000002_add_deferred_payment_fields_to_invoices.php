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
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('cash')->after('price_tier'); // cash, credit, partial
            $table->decimal('paid_amount', 12, 2)->default(0.00)->after('total_amount');
            $table->decimal('remaining_amount', 12, 2)->default(0.00)->after('paid_amount');
            $table->date('due_date')->nullable()->after('remaining_amount');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('cash')->after('status'); // cash, credit, partial
            $table->decimal('paid_amount', 12, 2)->default(0.00)->after('total_amount');
            $table->decimal('remaining_amount', 12, 2)->default(0.00)->after('paid_amount');
            $table->date('due_date')->nullable()->after('remaining_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'paid_amount', 'remaining_amount', 'due_date']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'paid_amount', 'remaining_amount', 'due_date']);
        });
    }
};
