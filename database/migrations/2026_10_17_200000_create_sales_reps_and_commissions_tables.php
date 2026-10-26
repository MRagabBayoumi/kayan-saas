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
        // 1. Add phone and commission fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 35)->nullable()->after('email');
            $table->decimal('commission_rate', 5, 2)->default(0.00)->after('can_access_all_warehouses');
            $table->string('commission_type', 20)->default('percentage')->after('commission_rate');
        });

        // 2. Add sales_rep_id and GPS coordinates to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // 3. Add sales_rep_id and commission fields to sales_invoices table
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $table->decimal('commission_rate', 5, 2)->default(0.00)->after('total_profit');
            $table->decimal('commission_amount', 15, 2)->default(0.00)->after('commission_rate');
        });

        // 4. Create dedicated sales_commissions tracking table
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('invoice_total', 15, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('commission_amount', 15, 2)->default(0.00);
            $table->string('status', 20)->default('pending'); // pending, approved, paid, canceled
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sales_rep_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_commissions');

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_rep_id']);
            $table->dropColumn(['sales_rep_id', 'commission_rate', 'commission_amount']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['sales_rep_id']);
            $table->dropColumn(['sales_rep_id', 'latitude', 'longitude']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'commission_rate', 'commission_type']);
        });
    }
};
