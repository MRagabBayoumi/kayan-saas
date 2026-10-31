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
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->id();
            $table->string('packing_number', 50)->unique();
            $table->date('packing_date')->index();
            $table->string('customer_name', 150)->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('warehouse_name', 100)->nullable();
            $table->string('price_type', 30)->default('wholesale');
            $table->boolean('show_quantity')->default(true);
            $table->boolean('show_price')->default(true);
            $table->boolean('show_packages')->default(true);
            $table->decimal('total_quantity', 14, 2)->default(0);
            $table->integer('total_packages')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_lists');
    }
};
