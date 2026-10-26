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
        Schema::create('customer_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->string('installment_title', 100);
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->string('status', 20)->default('pending'); // pending, partial, paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'due_date']);
            $table->index(['status', 'due_date']);
            $table->index(['sales_invoice_id']);
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->foreignId('customer_installment_id')->nullable()->after('sales_invoice_id')->constrained('customer_installments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign(['customer_installment_id']);
            $table->dropColumn('customer_installment_id');
        });

        Schema::dropIfExists('customer_installments');
    }
};
