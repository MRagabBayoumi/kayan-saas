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
        if (Schema::hasTable('treasuries')) {
            Schema::table('treasuries', function (Blueprint $table) {
                if (! Schema::hasColumn('treasuries', 'account_id')) {
                    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('expense_categories')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('expense_categories', 'account_id')) {
                    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'account_id')) {
                    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (! Schema::hasColumn('suppliers', 'account_id')) {
                    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('treasuries') && Schema::hasColumn('treasuries', 'account_id')) {
            Schema::table('treasuries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }

        if (Schema::hasTable('expense_categories') && Schema::hasColumn('expense_categories', 'account_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'account_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }

        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'account_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }
    }
};
