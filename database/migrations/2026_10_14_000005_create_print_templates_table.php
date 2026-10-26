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
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40); // sales_invoice, purchase_invoice, customer_statement, supplier_statement
            $table->string('paper_format', 20);  // a4, thermal_80mm
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->json('settings');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['document_type', 'paper_format']);
            $table->index(['document_type', 'paper_format', 'is_default']);
            $table->unique(['document_type', 'paper_format', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
