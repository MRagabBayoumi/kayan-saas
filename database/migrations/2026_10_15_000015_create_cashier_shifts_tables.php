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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_number', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('status', 20)->default('open'); // open, closed
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->decimal('cash_sales_amount', 12, 2)->default(0.00);
            $table->decimal('cash_returns_amount', 12, 2)->default(0.00);
            $table->decimal('cash_additions', 12, 2)->default(0.00);
            $table->decimal('cash_expenses', 12, 2)->default(0.00);
            $table->decimal('expected_closing_balance', 12, 2)->default(0.00);
            $table->decimal('actual_closing_balance', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->default(0.00);
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('cashier_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('movement_type', 30); // opening, sale, return, cash_in, cash_out, closing
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cashier_shift_id', 'movement_type']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('cashier_shifts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_shift_id');
        });

        Schema::dropIfExists('cashier_cash_movements');
        Schema::dropIfExists('cashier_shifts');
    }
};
