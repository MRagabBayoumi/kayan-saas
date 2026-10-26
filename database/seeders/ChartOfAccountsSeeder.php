<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\Treasury;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed standard commercial Chart of Accounts tailored for Kayan ERP.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Helper to insert or update account
            $createAccount = function (array $data) {
                $code = $data['code'];
                unset($data['code']);

                return Account::updateOrCreate(['code' => $code], $data);
            };

            // =========================================================================
            // 1. الأصول (ASSETS)
            // =========================================================================
            $assets = $createAccount([
                'code' => '1',
                'name' => 'الأصول',
                'name_en' => 'Assets',
                'parent_id' => null,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 1,
                'is_leaf' => false,
                'is_system' => true,
                'system_role' => null,
            ]);

            // 11. الأصول المتداولة
            $currentAssets = $createAccount([
                'code' => '11',
                'name' => 'الأصول المتداولة',
                'name_en' => 'Current Assets',
                'parent_id' => $assets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            // 1101. النقدية وما في حكمها
            $cashAndEquiv = $createAccount([
                'code' => '1101',
                'name' => 'النقدية وما في حكمها',
                'name_en' => 'Cash and Cash Equivalents',
                'parent_id' => $currentAssets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $mainCash = $createAccount([
                'code' => '110101',
                'name' => 'الخزينة الرئيسية',
                'name_en' => 'Main Cash Safe',
                'parent_id' => $cashAndEquiv->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'cash_main',
            ]);

            $createAccount([
                'code' => '110102',
                'name' => 'خزائن الفروع ونقاط البيع (الكاشير)',
                'name_en' => 'Branch & POS Safes',
                'parent_id' => $cashAndEquiv->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'cash_pos',
            ]);

            $createAccount([
                'code' => '110103',
                'name' => 'الحسابات البنكية ومحافظ الدفع',
                'name_en' => 'Bank Accounts & Wallets',
                'parent_id' => $cashAndEquiv->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'bank_main',
            ]);

            // 1102. العملاء والمدينون
            $arGroup = $createAccount([
                'code' => '1102',
                'name' => 'العملاء والمدينون',
                'name_en' => 'Accounts Receivable & Debtors',
                'parent_id' => $currentAssets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '110201',
                'name' => 'حساب مراقبة العملاء (ذمم مدينة)',
                'name_en' => 'Accounts Receivable Control',
                'parent_id' => $arGroup->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'accounts_receivable',
            ]);

            $createAccount([
                'code' => '110202',
                'name' => 'أوراق القبض وأقساط العملاء',
                'name_en' => 'Notes & Installments Receivable',
                'parent_id' => $arGroup->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'notes_receivable',
            ]);

            $createAccount([
                'code' => '110203',
                'name' => 'جاري وعهد مناديب المبيعات',
                'name_en' => 'Sales Reps Current Accounts',
                'parent_id' => $arGroup->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_reps_current',
            ]);

            // 1103. المخزون السلعي
            $invGroup = $createAccount([
                'code' => '1103',
                'name' => 'المخزون السلعي',
                'name_en' => 'Inventory',
                'parent_id' => $currentAssets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '110301',
                'name' => 'مخزون بضاعة بغرض البيع (المستودعات)',
                'name_en' => 'Merchandise Inventory',
                'parent_id' => $invGroup->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'inventory_merchandise',
            ]);

            $createAccount([
                'code' => '110302',
                'name' => 'بضاعة بالطريق واعتمادات استيراد الصين',
                'name_en' => 'Goods in Transit & China LCs',
                'parent_id' => $invGroup->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'goods_in_transit',
            ]);

            // 12. الأصول الثابتة
            $fixedAssets = $createAccount([
                'code' => '12',
                'name' => 'الأصول الثابتة',
                'name_en' => 'Fixed Assets',
                'parent_id' => $assets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => false,
            ]);

            $createAccount([
                'code' => '120101',
                'name' => 'أجهزة كمبيوتر وطابعات ومعدات شبكات',
                'name_en' => 'IT & Printing Equipment',
                'parent_id' => $fixedAssets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => false,
            ]);

            $createAccount([
                'code' => '120102',
                'name' => 'تجهيزات ومعدات مخازن ومعارض',
                'name_en' => 'Fixtures & Storage Equipment',
                'parent_id' => $fixedAssets->id,
                'type' => Account::TYPE_ASSET,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => false,
            ]);

            // =========================================================================
            // 2. الخصوم والالتزامات (LIABILITIES)
            // =========================================================================
            $liabilities = $createAccount([
                'code' => '2',
                'name' => 'الخصوم والالتزامات',
                'name_en' => 'Liabilities',
                'parent_id' => null,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 1,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            // 21. الخصوم المتداولة
            $currentLiab = $createAccount([
                'code' => '21',
                'name' => 'الخصوم المتداولة',
                'name_en' => 'Current Liabilities',
                'parent_id' => $liabilities->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            // 2101. الموردون والدائنون
            $apGroup = $createAccount([
                'code' => '2101',
                'name' => 'الموردون والدائنون',
                'name_en' => 'Accounts Payable & Creditors',
                'parent_id' => $currentLiab->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 3,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '210101',
                'name' => 'موردو بضاعة محليين (ذمم دائنة)',
                'name_en' => 'Local Accounts Payable Control',
                'parent_id' => $apGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'accounts_payable_local',
            ]);

            $createAccount([
                'code' => '210102',
                'name' => 'مصانع وموردو الصين (شحنات خارجية)',
                'name_en' => 'Overseas China Suppliers Payable',
                'parent_id' => $apGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'accounts_payable_china',
            ]);

            $createAccount([
                'code' => '210103',
                'name' => 'أوراق الدفع وأقساط الموردين',
                'name_en' => 'Notes & Installments Payable',
                'parent_id' => $apGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'notes_payable',
            ]);

            $createAccount([
                'code' => '210104',
                'name' => 'مكاتب وشركات الشحن والتخليص',
                'name_en' => 'Shipping & Clearance Offices Payable',
                'parent_id' => $apGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'shipping_offices_payable',
            ]);

            // 2102. مستحقات وأرصدة دائنة أخرى
            $accruedGroup = $createAccount([
                'code' => '2102',
                'name' => 'مستحقات وأرصدة دائنة أخرى',
                'name_en' => 'Other Accrued Liabilities',
                'parent_id' => $currentLiab->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 3,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '210201',
                'name' => 'رواتب وأجور مستحقة للعاملين',
                'name_en' => 'Accrued Payroll',
                'parent_id' => $accruedGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'accrued_payroll',
            ]);

            $createAccount([
                'code' => '210202',
                'name' => 'ضرائب مستحقة (القيمة المضافة)',
                'name_en' => 'Taxes Payable',
                'parent_id' => $accruedGroup->id,
                'type' => Account::TYPE_LIABILITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 4,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'tax_payable',
            ]);

            // =========================================================================
            // 3. حقوق الملكية (EQUITY)
            // =========================================================================
            $equity = $createAccount([
                'code' => '3',
                'name' => 'حقوق الملكية',
                'name_en' => 'Equity',
                'parent_id' => null,
                'type' => Account::TYPE_EQUITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 1,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '3101',
                'name' => 'رأس المال المدفوع',
                'name_en' => 'Paid-in Capital',
                'parent_id' => $equity->id,
                'type' => Account::TYPE_EQUITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'capital',
            ]);

            $createAccount([
                'code' => '3102',
                'name' => 'الأرباح والخسائر المرحلة (المتراكمة)',
                'name_en' => 'Retained Earnings',
                'parent_id' => $equity->id,
                'type' => Account::TYPE_EQUITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'retained_earnings',
            ]);

            $createAccount([
                'code' => '3103',
                'name' => 'جاري صاحب العمل / الشركاء',
                'name_en' => 'Owner Current / Drawings',
                'parent_id' => $equity->id,
                'type' => Account::TYPE_EQUITY,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'owner_equity',
            ]);

            // =========================================================================
            // 4. الإيرادات (REVENUES)
            // =========================================================================
            $revenues = $createAccount([
                'code' => '4',
                'name' => 'الإيرادات',
                'name_en' => 'Revenues',
                'parent_id' => null,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 1,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            // 41. إيرادات النشاط
            $salesGroup = $createAccount([
                'code' => '41',
                'name' => 'إيرادات المبيعات',
                'name_en' => 'Sales Revenues',
                'parent_id' => $revenues->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '410101',
                'name' => 'إيراد مبيعات قطاعي (التجزئة)',
                'name_en' => 'Retail Sales Revenue',
                'parent_id' => $salesGroup->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_retail',
            ]);

            $createAccount([
                'code' => '410102',
                'name' => 'إيراد مبيعات جملة ومحلات',
                'name_en' => 'Wholesale Sales Revenue',
                'parent_id' => $salesGroup->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_wholesale',
            ]);

            $createAccount([
                'code' => '410103',
                'name' => 'إيراد مبيعات كبار الموزعين والوكلاء',
                'name_en' => 'Distributor Sales Revenue',
                'parent_id' => $salesGroup->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_distributor',
            ]);

            $createAccount([
                'code' => '4102',
                'name' => 'مردودات ومسموحات المبيعات (مدين)',
                'name_en' => 'Sales Returns & Allowances',
                'parent_id' => $revenues->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_DEBIT, // Contra-revenue
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_returns',
            ]);

            $createAccount([
                'code' => '4103',
                'name' => 'خصم مسموح به للعملاء (مدين)',
                'name_en' => 'Sales Discounts Allowed',
                'parent_id' => $revenues->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_DEBIT, // Contra-revenue
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_discount',
            ]);

            $createAccount([
                'code' => '4201',
                'name' => 'إيرادات أخرى وخدمات شحن',
                'name_en' => 'Other Revenue & Shipping Fees',
                'parent_id' => $revenues->id,
                'type' => Account::TYPE_REVENUE,
                'nature' => Account::NATURE_CREDIT,
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'other_revenue',
            ]);

            // =========================================================================
            // 5. المصروفات وتكلفة النشاط (EXPENSES & COGS)
            // =========================================================================
            $expenses = $createAccount([
                'code' => '5',
                'name' => 'المصروفات وتكلفة النشاط',
                'name_en' => 'Expenses & Cost of Goods Sold',
                'parent_id' => null,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 1,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            // 51. تكلفة البضاعة المباعة
            $cogsGroup = $createAccount([
                'code' => '51',
                'name' => 'تكلفة البضاعة المباعة (COGS)',
                'name_en' => 'Cost of Goods Sold',
                'parent_id' => $expenses->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '510101',
                'name' => 'تكلفة مبيعات البضاعة (FIFO)',
                'name_en' => 'Cost of Merchandise Sold (FIFO)',
                'parent_id' => $cogsGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'cogs',
            ]);

            // 52. مصروفات البيع والتسويق
            $sellingGroup = $createAccount([
                'code' => '52',
                'name' => 'مصروفات البيع والتسويق والتوزيع',
                'name_en' => 'Selling & Marketing Expenses',
                'parent_id' => $expenses->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '520101',
                'name' => 'عمولات ومكافآت مندوبي المبيعات',
                'name_en' => 'Sales Commissions Expense',
                'parent_id' => $sellingGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'sales_commissions_expense',
            ]);

            $createAccount([
                'code' => '520102',
                'name' => 'نولون ومصاريف شحن ونقل البضاعة للعملاء',
                'name_en' => 'Customer Shipping & Delivery Expense',
                'parent_id' => $sellingGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'shipping_expense',
            ]);

            // 53. المصروفات العمومية والإدارية والتشغيلية
            $gaGroup = $createAccount([
                'code' => '53',
                'name' => 'المصروفات العمومية والإدارية والتشغيلية',
                'name_en' => 'General & Administrative Expenses',
                'parent_id' => $expenses->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 2,
                'is_leaf' => false,
                'is_system' => true,
            ]);

            $createAccount([
                'code' => '530101',
                'name' => 'إيجار المقرات والمخازن والمعارض',
                'name_en' => 'Rent Expense',
                'parent_id' => $gaGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'rent_expense',
            ]);

            $createAccount([
                'code' => '530102',
                'name' => 'رواتب وأجور موظفي الإدارة والكاشير',
                'name_en' => 'Staff Salaries Expense',
                'parent_id' => $gaGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'salaries_expense',
            ]);

            $createAccount([
                'code' => '530103',
                'name' => 'كهرباء ومياه وإنترنت ومرافق',
                'name_en' => 'Utilities & Internet Expense',
                'parent_id' => $gaGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'utilities_expense',
            ]);

            $generalExp = $createAccount([
                'code' => '530104',
                'name' => 'مصروفات نثرية وصيانة وبوفيه ونظافة',
                'name_en' => 'Maintenance & Petty Cash Expenses',
                'parent_id' => $gaGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'general_expense',
            ]);

            $createAccount([
                'code' => '530105',
                'name' => 'أدوات مكتبية ومطبوعات وملصقات باركود',
                'name_en' => 'Printing & Office Supplies',
                'parent_id' => $gaGroup->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_DEBIT,
                'level' => 3,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'stationery_expense',
            ]);

            $createAccount([
                'code' => '5401',
                'name' => 'خصم مكتسب من الموردين (دائن)',
                'name_en' => 'Purchase Discounts Received',
                'parent_id' => $expenses->id,
                'type' => Account::TYPE_EXPENSE,
                'nature' => Account::NATURE_CREDIT, // Contra-expense
                'level' => 2,
                'is_leaf' => true,
                'is_system' => true,
                'system_role' => 'purchase_discount',
            ]);

            // Link existing default treasury if exists
            Treasury::whereNull('account_id')->update(['account_id' => $mainCash->id]);

            // Link existing expense categories to matching accounts
            $catMap = [
                'إيجار' => 'rent_expense',
                'رواتب' => 'salaries_expense',
                'كهرباء' => 'utilities_expense',
                'شحن' => 'shipping_expense',
                'صيانة' => 'general_expense',
                'بوفيه' => 'general_expense',
            ];

            foreach ($catMap as $keyword => $role) {
                $acc = Account::byRole($role);
                if ($acc) {
                    ExpenseCategory::where('name', 'like', "%{$keyword}%")
                        ->whereNull('account_id')
                        ->update(['account_id' => $acc->id]);
                }
            }

            // Fallback for remaining unlinked expense categories
            ExpenseCategory::whereNull('account_id')->update(['account_id' => $generalExp->id]);
        });
    }
}
