<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 64)->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->nullOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('payment_method', 30)->default('cash'); // cash, bank_transfer, cheque, card
            $table->string('paid_to', 150)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_attachment', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_category_id', 'expense_date']);
            $table->index(['treasury_id']);
            $table->index(['cashier_shift_id']);
        });

        // Seed default expense categories
        $defaultCategories = [
            ['name' => 'إيجارات ومقرات', 'code' => 'EXP-RENT', 'description' => 'إيجار المقرات، المعارض، والمخازن'],
            ['name' => 'رواتب وأجور ومكافآت', 'code' => 'EXP-SALARY', 'description' => 'رواتب الموظفين، المكافآت، وبدلات العمل'],
            ['name' => 'بوفيه ونثريات وضيافة', 'code' => 'EXP-HOSPITALITY', 'description' => 'مشروبات، مأكولات، ومستلزمات الضيافة'],
            ['name' => 'فواتير ومرافق (كهرباء / إنترنت / مياه)', 'code' => 'EXP-UTILITIES', 'description' => 'فواتير الكهرباء، المياه، والإنترنت والاتصالات'],
            ['name' => 'شحن ونقل ونوالين', 'code' => 'EXP-SHIPPING', 'description' => 'تكاليف الشحن الداخلي، النوالين، وسيارات النقل'],
            ['name' => 'تسويق وإعلانات', 'code' => 'EXP-MARKETING', 'description' => 'الحملات الإعلانية والمطبوعات والتسويق الرقمي'],
            ['name' => 'صيانة ومهمات ومستلزمات', 'code' => 'EXP-MAINTENANCE', 'description' => 'صيانة الأجهزة، أدوات النظافة، وأدوات مكتبية'],
            ['name' => 'ضرائب ورسوم وتراخيص', 'code' => 'EXP-GOV', 'description' => 'الرسوم الحكومية، التراخيص، والاشتراكات'],
            ['name' => 'مصروفات متنوعة أخرى', 'code' => 'EXP-MISC', 'description' => 'أي نفقات ومصروفات تشغيلية أخرى'],
        ];

        foreach ($defaultCategories as $cat) {
            DB::table('expense_categories')->insert(array_merge($cat, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
