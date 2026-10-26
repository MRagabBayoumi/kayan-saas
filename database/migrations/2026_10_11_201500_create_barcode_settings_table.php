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
        Schema::create('barcode_settings', function (Blueprint $table) {
            $table->id();
            $table->string('barcode_prefix', 10)->default('02');
            $table->string('printer_name', 100)->default('Xprinter XP-233B');
            $table->unsignedSmallInteger('label_width_mm')->default(38);
            $table->unsignedSmallInteger('label_height_mm')->default(25);
            $table->unsignedSmallInteger('barcode_width_px')->default(260);
            $table->unsignedSmallInteger('barcode_height_px')->default(80);
            $table->unsignedSmallInteger('product_font_size')->default(14);
            $table->unsignedSmallInteger('details_font_size')->default(8);

            // Checkbox display options on the sticker
            $table->boolean('show_company_name')->default(true);
            $table->string('company_name_override')->nullable();
            $table->boolean('show_product_name')->default(true);
            $table->boolean('show_barcode_bars')->default(true);
            $table->boolean('show_item_sku')->default(true);
            $table->string('print_price_type', 20)->default('none'); // 'none', 'retail', 'wholesale', 'both'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_settings');
    }
};
