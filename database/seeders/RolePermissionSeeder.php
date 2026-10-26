<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // 1. Products & Pricing (المنتجات والتسعير)
            ['name' => 'عرض المنتجات والأصناف', 'slug' => 'view-products', 'module' => 'المنتجات والتسعير', 'description' => 'إمكانية تصفح قائمة الأصناف والبحث بالباركود والتفاصيل'],
            ['name' => 'إدارة المنتجات والتصنيفات', 'slug' => 'manage-products', 'module' => 'المنتجات والتسعير', 'description' => 'إضافة أصناف جديدة، تعديل المواصفات العامة، وإدارة تصنيفات الأصناف'],
            ['name' => 'تحديد وتعديل أسعار المنتجات والتكلفة', 'slug' => 'edit-product-prices', 'module' => 'المنتجات والتسعير', 'description' => 'تعديل أسعار التجزئة والجملة والموزع وسعر التكلفة وتكلفة أول المدة'],
            ['name' => 'طباعة ملصقات الباركود والأسعار', 'slug' => 'print-product-barcodes', 'module' => 'المنتجات والتسعير', 'description' => 'توليد وطباعة باركود الأصناف وملصقات الرفوف والأجهزة الحرارية'],
            ['name' => 'حذف أو أرشفة الأصناف', 'slug' => 'delete-products', 'module' => 'المنتجات والتسعير', 'description' => 'حذف المنتجات غير المرتبطة بحركات فواتير سابقة'],

            // 2. Inventory & Warehouses (المخازن والمستودعات)
            ['name' => 'عرض المخزون والمستودعات', 'slug' => 'access-inventory', 'module' => 'المخازن والمستودعات', 'description' => 'الاطلاع على كميات المخزون، النواقص، وحركات المستودعات'],
            ['name' => 'إدارة التحويلات بين المخازن', 'slug' => 'manage-inventory', 'module' => 'المخازن والمستودعات', 'description' => 'إنشاء واعتماد حركات التحويل المخزني بين الفروع والمستودعات'],
            ['name' => 'تسويات الجرد المخزني والعجز والزيادة', 'slug' => 'manage-stock-adjustments', 'module' => 'المخازن والمستودعات', 'description' => 'إجراء تسويات الجرد السنوي والدوري ومعالجة الفروقات المخزنية'],
            ['name' => 'إدارة المستودعات والفروع', 'slug' => 'manage-warehouses', 'module' => 'المخازن والمستودعات', 'description' => 'إضافة وتعديل بيانات المستودعات وتعيين أمناء المخازن'],

            // 3. Sales & POS (المبيعات ونقاط البيع)
            ['name' => 'الوصول لنقطة البيع وفواتير المبيعات', 'slug' => 'access-sales', 'module' => 'المبيعات ونقاط البيع', 'description' => 'فتح شاشة الكاشير (POS) واستعراض فواتير البيع'],
            ['name' => 'تحرير وإدارة مسودات المبيعات', 'slug' => 'manage-sales', 'module' => 'المبيعات ونقاط البيع', 'description' => 'تعديل بنود الفواتير والكميات وفئات الأسعار قبل الترحيل'],
            ['name' => 'ترحيل واعتماد فواتير البيع وصرف FIFO', 'slug' => 'post-sales-invoice', 'module' => 'المبيعات ونقاط البيع', 'description' => 'صرف البضاعة بالـ FIFO وتحصيل المبالغ وتأثير الخزينة والمخزون'],
            ['name' => 'إلغاء فواتير البيع واسترجاع البضاعة', 'slug' => 'void-sales-invoice', 'module' => 'المبيعات ونقاط البيع', 'description' => 'إلغاء فواتير البيع المرحلة واسترجاع التكلفة والكميات للدفعات المخزنية'],
            ['name' => 'فك ترحيل الفواتير لتعديل بنودها', 'slug' => 'unpost-sales-invoice', 'module' => 'المبيعات ونقاط البيع', 'description' => 'إلغاء الترحيل مؤقتاً لتعديل الفاتورة وإعادة ترحيلها'],
            ['name' => 'منح خصومات إضافية على الفاتورة', 'slug' => 'apply-sales-discounts', 'module' => 'المبيعات ونقاط البيع', 'description' => 'إمكانية إدراج خصم قيمة أو نسبة مئوية على إجمالي الفاتورة أو بنودها'],
            ['name' => 'تجاوز الحد الائتماني للعميل', 'slug' => 'override-credit-limit', 'module' => 'المبيعات ونقاط البيع', 'description' => 'السماح بإصدار فواتير بيع آجل تتجاوز سقف المديونية المحدد للعميل'],
            ['name' => 'الاطلاع على تكلفة البضاعة وهوامش الربح (COGS)', 'slug' => 'view-cogs', 'module' => 'المبيعات ونقاط البيع', 'description' => 'كشف أرقام تكلفة المبيعات ومجمل أرباح الفواتير للمدراء'],
            ['name' => 'طباعة وإعادة طباعة الفواتير', 'slug' => 'print-sales-invoices', 'module' => 'المبيعات ونقاط البيع', 'description' => 'طباعة فواتير البيع بنماذج A4 و A5 والإيصالات الحرارية 80مم'],

            // 4. Sales Returns (مرتجعات المبيعات)
            ['name' => 'الوصول لقائمة وسجلات مرتجعات المبيعات', 'slug' => 'access-sales-returns', 'module' => 'مرتجعات المبيعات', 'description' => 'استعراض أذونات وحركات مرتجعات البيع السابقة'],
            ['name' => 'تحرير أذونات مرتجعات مبيعات جديدة', 'slug' => 'create-sales-returns', 'module' => 'مرتجعات المبيعات', 'description' => 'تسجيل إذن إرجاع مرتبط بفاتورة مبيعات مرحلة'],
            ['name' => 'ترحيل واعتماد مرتجعات المبيعات', 'slug' => 'post-sales-return', 'module' => 'مرتجعات المبيعات', 'description' => 'ترحيل المرتجع وإعادة رصيد الدفعة للمخزن ورد المبلغ أو تخفيض المديونية'],

            // 5. Cashier Shifts (ورديات وخزينة الكاشير)
            ['name' => 'استعراض سجل الورديات وجرد الدرج', 'slug' => 'access-shifts', 'module' => 'ورديات وخزينة الكاشير', 'description' => 'الاطلاع على تاريخ الورديات النقدية ومسؤولي الكاشير'],
            ['name' => 'فتح وردية كاشير جديدة لنفسه', 'slug' => 'open-shifts', 'module' => 'ورديات وخزينة الكاشير', 'description' => 'إمكانية فتح وردية كاشير وإدخال رصيد بداية العهدة بالدرج للبدء بالبيع'],
            ['name' => 'تسجيل حركات نقدية بالدرج (سحب/إيداع)', 'slug' => 'manage-shifts', 'module' => 'ورديات وخزينة الكاشير', 'description' => 'تسجيل السحب النقدي للمصروفات النثرية أو الإيداع الإضافي بالدرج'],
            ['name' => 'إغلاق ورديات الكاشير واعتماد جرد الدرج', 'slug' => 'close-shifts', 'module' => 'ورديات وخزينة الكاشير', 'description' => 'صلاحية إدارية لإغلاق الوردية واعتماد الجرد الفعلي للنقدية وحساب الفارق'],
            ['name' => 'إعادة فتح الورديات المغلقة وتعديل الجرد', 'slug' => 'reopen-shifts', 'module' => 'ورديات وخزينة الكاشير', 'description' => 'صلاحية إدارية عليا لإعادة فتح وردية بعد إغلاقها لتصحيح خطأ بالجرد'],

            // 6. Purchases & Returns (المشتريات والواردات)
            ['name' => 'الوصول لفواتير الشراء والتوريد', 'slug' => 'access-purchases', 'module' => 'المشتريات والواردات', 'description' => 'استعراض فواتير الشراء وإنشاء مسودات التوريد'],
            ['name' => 'إدارة فواتير الشراء والتوريد', 'slug' => 'manage-purchases', 'module' => 'المشتريات والواردات', 'description' => 'تسجيل وتعديل بيانات أوامر وفواتير الشراء ومصاريف الشحن'],
            ['name' => 'اعتماد وترحيل فواتير الشراء', 'slug' => 'post-purchase-invoice', 'module' => 'المشتريات والواردات', 'description' => 'ترحيل الفاتورة للتأثير المالي وتوليد دفعات FIFO المخزنية'],
            ['name' => 'إلغاء فواتير الشراء المرحلة', 'slug' => 'cancel-purchase-invoice', 'module' => 'المشتريات والواردات', 'description' => 'إلغاء الفواتير وإرجاع التأثير المالي والكميات المسحوبة'],
            ['name' => 'إدارة واعتماد مرتجعات المشتريات', 'slug' => 'manage-purchase-returns', 'module' => 'المشتريات والواردات', 'description' => 'تسجيل وترحيل إرجاع البضاعة للمورد واسترداد تكلفتها'],

            // 7. Customers & Receivables (العملاء والحسابات المدينة)
            ['name' => 'الوصول لدليل وسجلات العملاء', 'slug' => 'access-customers', 'module' => 'العملاء والحسابات المدينة', 'description' => 'استعراض قائمة العملاء وسجلاتهم والبحث في بياناتهم'],
            ['name' => 'إضافة عميل سريع من شاشة الكاشير (POS)', 'slug' => 'create-customers-pos', 'module' => 'العملاء والحسابات المدينة', 'description' => 'إمكانية إضافة عميل جديد مباشرة من شاشة البيع وتحديد فئته السعرية'],
            ['name' => 'إدارة العملاء وتجار الجملة والائتمان', 'slug' => 'manage-customers', 'module' => 'العملاء والحسابات المدينة', 'description' => 'إضافة وتعديل العملاء، سقف الائتمان، وفئات التسعير'],
            ['name' => 'استعراض وطباعة كشف حساب العميل', 'slug' => 'view-customer-statement', 'module' => 'العملاء والحسابات المدينة', 'description' => 'الاطلاع على الحركات المالية والمديونيات وسندات التحصيل للعميل'],
            ['name' => 'حذف سجلات العملاء', 'slug' => 'delete-customers', 'module' => 'العملاء والحسابات المدينة', 'description' => 'صلاحية حذف العملاء غير المرتبطين بفواتير بيع أو مديونيات'],

            // 8. Suppliers (الموردين ومكاتب الشحن)
            ['name' => 'الوصول لدليل الموردين ومكاتب الشحن', 'slug' => 'access-suppliers', 'module' => 'الموردين ومكاتب الشحن', 'description' => 'استعراض قائمة الموردين وبيانات الاتصال والشركات الناقلة'],
            ['name' => 'إدارة وتعديل بيانات الموردين', 'slug' => 'manage-suppliers', 'module' => 'الموردين ومكاتب الشحن', 'description' => 'إضافة وتعديل بيانات الموردين والشروط التجارية'],
            ['name' => 'استعراض وطباعة كشف حساب المورد', 'slug' => 'view-supplier-statement', 'module' => 'الموردين ومكاتب الشحن', 'description' => 'الاطلاع على مستحقات المورد ودفعات التوريد المسددة'],

            // 9. Finance & Treasuries (المالية والخزائن والمصروفات)
            ['name' => 'إدارة الخزائن والحسابات البنكية', 'slug' => 'manage-treasuries', 'module' => 'المالية والخزائن والمصروفات', 'description' => 'الاطلاع على الخزائن، متابعة الأرصدة، والتحويلات المالية بين الحسابات'],
            ['name' => 'إدارة وتسجيل المصروفات وسندات الصرف', 'slug' => 'manage-expenses', 'module' => 'المالية والخزائن والمصروفات', 'description' => 'تسجيل بنود المصروفات، صرف العهد، واستخراج أذونات الصرف'],
            ['name' => 'إدارة سندات القبض والدفع والتحصيل', 'slug' => 'manage-payments', 'module' => 'المالية والخزائن والمصروفات', 'description' => 'تسجيل إيصالات قبض العملاء ومدفوعات الموردين وتأثيرها على الخزائن'],
            ['name' => 'الاطلاع على التقارير المالية والتدفق النقدي', 'slug' => 'view-financial-reports', 'module' => 'المالية والخزائن والمصروفات', 'description' => 'استعراض قائمة الدخل، تقارير الخزائن، والأرباح والخسائر'],

            // 10. Sales Representatives & Field Operations (مندوبي المبيعات والعمل الميداني)
            ['name' => 'إدارة وتحليل أداء مناديب المبيعات', 'slug' => 'manage-sales-reps', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'إضافة المناديب الجدد، ضبط نسب العمولات، ومتابعة الأداء'],
            ['name' => 'الوصول لبوابة المندوب والمبيعات الميدانية', 'slug' => 'access-sales-portal', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'تصفح كتالوج المنتجات وتسجيل طلبات وفواتير العملاء من الموبايل'],
            ['name' => 'الاطلاع على العمولات الشخصية', 'slug' => 'view-own-commissions', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'استعراض تقرير العمولات والأرباح المحققة الخاصة بالمندوب'],
            ['name' => 'إدارة واعتماد وصرف عمولات المناديب', 'slug' => 'manage-commissions', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'تحديد نسب العمولات واعتمادها وصرفها للمناديب'],
            ['name' => 'اعتماد طلبات إضافة العملاء من المناديب', 'slug' => 'approve-rep-customers', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'مراجعة وتوثيق واعتماد العملاء الجدد المرفوعين من الميدان'],
            ['name' => 'متابعة الحضور والزيارات وتتبع GPS للمناديب', 'slug' => 'track-rep-gps', 'module' => 'مندوبي المبيعات والعمل الميداني', 'description' => 'متابعة الحضور الميداني بالبصمة الجغرافية والزيارات الفعلية على الخريطة'],

            // 11. System Administration & Settings (الإدارة والنظام العام)
            ['name' => 'الوصول للوحة التحكم والتقارير العامة', 'slug' => 'access-dashboard', 'module' => 'الإدارة والنظام العام', 'description' => 'الاطلاع على لوحة المؤشرات والإحصائيات والرسوم البيانية العامة للمؤسسة'],
            ['name' => 'إدارة إعدادات الشركة والفروع', 'slug' => 'manage-company-settings', 'module' => 'الإدارة والنظام العام', 'description' => 'تعديل البيانات التجارية للشركة، الشعار، وإعدادات النظام العامة'],
            ['name' => 'تصميم وإدارة قوالب الطباعة', 'slug' => 'manage-print-templates', 'module' => 'الإدارة والنظام العام', 'description' => 'تخصيص قوالب طباعة الفواتير والملصقات الحرارية'],
            ['name' => 'إدارة المستخدمين وصلاحيات الدخول', 'slug' => 'manage-users', 'module' => 'الإدارة والنظام العام', 'description' => 'إضافة المستخدمين وتعيين كلمات المرور وتحديد أدوارهم'],
            ['name' => 'إدارة الأدوار ومصفوفة الصلاحيات', 'slug' => 'manage-roles', 'module' => 'الإدارة والنظام العام', 'description' => 'إنشاء وتعديل الأدوار الديناميكية وتخصيص صلاحيات كل دور'],
            ['name' => 'إدارة وتخصيص وحدات القياس', 'slug' => 'manage-units', 'module' => 'الإدارة والنظام العام', 'description' => 'إنشاء وحدات القياس وضبط معاملات التحويل وكميات التعبئة'],
            ['name' => 'إدارة وتنزيل النسخ الاحتياطية', 'slug' => 'manage-backups', 'module' => 'الإدارة والنظام العام', 'description' => 'إنشاء نسخ احتياطية لقاعدة البيانات وتحميلها وتعيين مسارات النسخ'],
            ['name' => 'تصدير البيانات والتقارير إلى Excel و PDF', 'slug' => 'export-data', 'module' => 'الإدارة والنظام العام', 'description' => 'تصدير كشوف الحساب وسجلات الفواتير والمخزون لملفات خارجية'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        // 1. Admin Role
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'يمتلك كافة الصلاحيات الإدارية والمالية والمخزنية في النظام',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync($allPermissionIds);

        // 2. Warehouse Role
        $warehouseRole = Role::firstOrCreate(
            ['slug' => 'warehouse'],
            [
                'name' => 'أمين المستودع',
                'description' => 'إدارة المنتجات، المخازن، حركات الجرد، وفواتير التوريد بدون صلاحيات تعديل الأسعار',
                'is_system' => true,
            ]
        );
        $warehousePerms = Permission::whereIn('slug', [
            'view-products',
            'manage-products',
            'print-product-barcodes',
            'access-inventory',
            'manage-inventory',
            'manage-stock-adjustments',
            'access-purchases',
            'manage-purchases',
            'post-purchase-invoice',
            'manage-purchase-returns',
            'access-sales',
            'access-sales-returns',
        ])->pluck('id')->toArray();
        $warehouseRole->permissions()->sync($warehousePerms);

        // 3. Cashier Role (Cashier CAN open shifts, but CANNOT close without manager approval)
        $cashierRole = Role::firstOrCreate(
            ['slug' => 'cashier'],
            [
                'name' => 'كاشير نقطة البيع',
                'description' => 'عرض واستعلام المنتجات والباركود والأسعار لإصدار أوامر البيع',
                'is_system' => true,
            ]
        );
        $cashierPerms = Permission::whereIn('slug', [
            'view-products',
            'access-sales',
            'manage-sales',
            'post-sales-invoice',
            'access-sales-returns',
            'create-sales-returns',
            'print-sales-invoices',
            'access-shifts',
            'open-shifts',
            'manage-shifts',
            'create-customers-pos',
        ])->pluck('id')->toArray();
        $cashierRole->permissions()->sync($cashierPerms);

        // 4. Sales Representative Role (Field Agent / Delegate)
        $salesRepRole = Role::firstOrCreate(
            ['slug' => 'sales_rep'],
            [
                'name' => 'مندوب مبيعات خارجي / بياع',
                'description' => 'الوصول لبوابة المندوب من الموبايل لتسجيل العملاء وتصفح المنتجات وإصدار الطلبات ومتابعة العمولات',
                'is_system' => true,
            ]
        );
        $salesRepPerms = Permission::whereIn('slug', [
            'view-products',
            'create-customers-pos',
            'access-sales-portal',
            'view-own-commissions',
        ])->pluck('id')->toArray();
        $salesRepRole->permissions()->sync($salesRepPerms);

        // Sync all permissions to any company manager role
        $companyManagerRoles = Role::where('slug', 'مدير_للشركه')
            ->orWhere('name', 'like', '%مدير%')
            ->get();
        foreach ($companyManagerRoles as $cmr) {
            $cmr->permissions()->sync($allPermissionIds);
        }

        // Assign existing admin user to admin role
        $adminUser = User::where('role', User::ROLE_ADMIN)->first();
        if ($adminUser && ! $adminUser->role_id) {
            $adminUser->update(['role_id' => $adminRole->id]);
        }
    }
}
