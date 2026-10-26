<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 30)->default('cash'); // cash, bank, wallet
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 64)->unique();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->string('type', 20); // deposit, withdrawal
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->date('transaction_date');
            $table->string('source_type', 50)->default('manual'); // opening_balance, manual, customer_payment, supplier_payment, expense, transfer_in, transfer_out, cashier_shift
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['treasury_id', 'transaction_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('treasury_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 64)->unique();
            $table->foreignId('from_treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->foreignId('to_treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed default Main Treasury if empty
        if (DB::table('treasuries')->count() === 0) {
            DB::table('treasuries')->insert([
                'name' => 'الخزينة الرئيسية',
                'type' => 'cash',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'is_default' => true,
                'is_active' => true,
                'notes' => 'الخزينة النقدية الرئيسية للشركة',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transfers');
        Schema::dropIfExists('treasury_transactions');
        Schema::dropIfExists('treasuries');
    }
};
