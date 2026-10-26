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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('thermal_printer_name', 255)->nullable()->default('XP-80C')->after('custom_backup_path');
            $table->string('document_printer_name', 255)->nullable()->default('TOSHIBA Generic Printer XL')->after('thermal_printer_name');
            $table->string('receipt_print_mode', 50)->default('browser_kiosk')->after('document_printer_name'); // 'browser_kiosk', 'server_python'
            $table->string('printer_network_user', 100)->nullable()->after('receipt_print_mode');
            $table->string('printer_network_password', 255)->nullable()->after('printer_network_user');
            $table->boolean('auto_print_on_save')->default(true)->after('printer_network_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'thermal_printer_name',
                'document_printer_name',
                'receipt_print_mode',
                'printer_network_user',
                'printer_network_password',
                'auto_print_on_save',
            ]);
        });
    }
};
