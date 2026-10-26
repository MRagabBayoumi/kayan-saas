<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `customers` MODIFY COLUMN `pricing_tier` ENUM('wholesale', 'retail', 'distributor') NOT NULL DEFAULT 'wholesale'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `customers` MODIFY COLUMN `pricing_tier` ENUM('wholesale', 'retail') NOT NULL DEFAULT 'wholesale'");
        }
    }
};
