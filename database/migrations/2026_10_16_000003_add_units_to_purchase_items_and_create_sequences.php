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
        // 1. Add unit conversion tracking columns to purchase_invoice_items
        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoice_items', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('unit_id');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 14, 4)->nullable()->after('quantity');
                }
            });
        }

        // 2. Create document_sequences table for atomic, concurrency-safe sequential document numbering
        if (! Schema::hasTable('document_sequences')) {
            Schema::create('document_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 50)->unique();
                $table->string('prefix', 20)->default('PUR-');
                $table->unsignedBigInteger('next_number')->default(1);
                $table->unsignedSmallInteger('padding')->default(6);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_invoice_items', 'unit_id')) {
                    $table->dropForeign(['unit_id']);
                    $table->dropColumn('unit_id');
                }
                if (Schema::hasColumn('purchase_invoice_items', 'conversion_factor')) {
                    $table->dropColumn('conversion_factor');
                }
                if (Schema::hasColumn('purchase_invoice_items', 'base_quantity')) {
                    $table->dropColumn('base_quantity');
                }
            });
        }

        Schema::dropIfExists('document_sequences');
    }
};
