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
        if (Schema::hasTable('cashier_shifts')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                if (! Schema::hasColumn('cashier_shifts', 'opened_by')) {
                    $table->foreignId('opened_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cashier_shifts')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                if (Schema::hasColumn('cashier_shifts', 'opened_by')) {
                    $table->dropForeign(['opened_by']);
                    $table->dropColumn('opened_by');
                }
            });
        }
    }
};
