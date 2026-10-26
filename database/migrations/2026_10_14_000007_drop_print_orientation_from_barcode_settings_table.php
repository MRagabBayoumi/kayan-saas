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
        if (Schema::hasColumn('barcode_settings', 'print_orientation')) {
            Schema::table('barcode_settings', function (Blueprint $table) {
                $table->dropColumn('print_orientation');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('barcode_settings', 'print_orientation')) {
            Schema::table('barcode_settings', function (Blueprint $table) {
                $table->string('print_orientation', 20)->default('0')->after('print_price_type');
            });
        }
    }
};
