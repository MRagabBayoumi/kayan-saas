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
        Schema::create('supplier_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->string('installment_title', 100);
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->string('status', 20)->default('pending'); // pending, partial, paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'due_date']);
            $table->index(['status', 'due_date']);
            $table->index(['purchase_invoice_id']);
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreignId('supplier_installment_id')->nullable()->after('purchase_invoice_id')->constrained('supplier_installments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['supplier_installment_id']);
            $table->dropColumn('supplier_installment_id');
        });

        Schema::dropIfExists('supplier_installments');
    }
};
