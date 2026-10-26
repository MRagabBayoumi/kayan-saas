<!-- Odoo-Style App Launcher Modal -->
<div id="appLauncherBackdrop" class="erp-app-launcher-backdrop" aria-hidden="true">
    <div class="erp-app-launcher-card" role="dialog" aria-modal="true" aria-labelledby="launcherTitle">
        <div class="erp-launcher-header">
            <div class="erp-launcher-title" id="launcherTitle">
                @if(!empty($globalCompanySetting?->logo_url))
                    <img src="{{ $globalCompanySetting->logo_url }}" alt="Logo" class="h-5 w-5 object-contain rounded inline-block">
                @else
                    <x-icon name="grid" size="18" />
                @endif
                <span>تطبيقات {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</span>
            </div>
            <button id="appLauncherClose" type="button" class="erp-btn erp-btn-ghost erp-btn-icon" title="إغلاق (Esc)">
                <x-icon name="x" size="16" />
            </button>
        </div>

        <div class="erp-launcher-grid">
            <!-- Home Applications Hub -->
            <a href="{{ route('home') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <x-icon name="grid" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">تطبيقات النظام</div>
                <div class="erp-launcher-desc">الرئيسية والوصول السريع لجميع البرامج</div>
            </a>

            <!-- Dashboard -->
            @can('access-dashboard')
            <a href="{{ route('dashboard') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                    <x-icon name="layout-dashboard" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">لوحة التحكم</div>
                <div class="erp-launcher-desc">المؤشرات العامة وملخص النشاط التجاري</div>
            </a>
            @endcan

            <!-- POS Cashier -->
            @can('access-sales')
            <a href="{{ route('sales.create') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <x-icon name="shopping-cart" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">نقطة البيع (POS)</div>
                <div class="erp-launcher-desc">شاشة الكاشير السريعة وإصدار الفواتير</div>
            </a>

            <!-- Sales Invoices -->
            <a href="{{ route('sales.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <x-icon name="file-text" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">فواتير المبيعات</div>
                <div class="erp-launcher-desc">إدارة أوامر البيع وحسابات الأرباح</div>
            </a>
            @endcan

            <!-- Customers -->
            @if(auth()->user()->can('access-customers') || auth()->user()->can('manage-customers') || auth()->user()->can('access-sales'))
            <a href="{{ route('customers.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                    <x-icon name="users" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">العملاء وتجار الجملة</div>
                <div class="erp-launcher-desc">سجل العملاء، كشوف الحساب، والمديونيات</div>
            </a>
            @endif

            <!-- Products -->
            @can('view-products')
            <a href="{{ route('products.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <x-icon name="package" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">المنتجات والأصناف</div>
                <div class="erp-launcher-desc">الأصناف، طباعة الباركود، والكتالوج</div>
            </a>
            @endcan

            <!-- Inventory -->
            @can('access-inventory')
            <a href="{{ route('inventory.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400">
                    <x-icon name="warehouse" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">المخزون والمستودعات</div>
                <div class="erp-launcher-desc">أرصدة المخازن، حركات النقل، والجرد</div>
            </a>
            @endcan

            <!-- Purchases -->
            @can('access-purchases')
            <a href="{{ route('purchases.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <x-icon name="shopping-bag" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">المشتريات والتوريدات</div>
                <div class="erp-launcher-desc">فواتير الوارد، دفعات التكلفة، ومتابعة الاستيراد</div>
            </a>

            <!-- Suppliers -->
            <a href="{{ route('suppliers.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                    <x-icon name="factory" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">الموردون ومكاتب الشحن</div>
                <div class="erp-launcher-desc">دليل الموردين المحليين والصين والشحن</div>
            </a>
            @endcan

            <!-- Finance & Treasuries -->
            @if(auth()->user()->can('manage-treasuries') || auth()->user()->can('manage-company-settings') || auth()->user()->isAdmin())
            <a href="{{ route('treasuries.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <x-icon name="landmark" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">الخزائن والمالية</div>
                <div class="erp-launcher-desc">الحسابات البنكية، الخزائن، والمقبوضات</div>
            </a>

            <!-- Expenses -->
            <a href="{{ route('expenses.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                    <x-icon name="banknote" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">المصروفات والعهد</div>
                <div class="erp-launcher-desc">تسجيل المصروفات وبنود الصرف النثري</div>
            </a>
            @endif

            <!-- Sales Reps -->
            @can('manage-sales-reps')
            <a href="{{ route('sales-reps.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <x-icon name="user-check" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">إدارة وتحليل المناديب</div>
                <div class="erp-launcher-desc">متابعة الزيارات، مبيعات المناديب والعمولات</div>
            </a>
            @endcan

            <!-- Mobile Portal -->
            @can('access-sales-portal')
            <a href="{{ route('portal.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                    <x-icon name="smartphone" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">بوابة المندوب الميداني</div>
                <div class="erp-launcher-desc">تطبيق الموبايل لتسجيل الزيارات والطلبيات</div>
            </a>
            @endcan

            <!-- System Settings -->
            @can('manage-company-settings')
            <a href="{{ route('settings.index') }}" class="erp-launcher-item">
                <div class="erp-launcher-icon bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <x-icon name="settings" size="26" strokeWidth="1.75" />
                </div>
                <div class="erp-launcher-label">إعدادات النظام</div>
                <div class="erp-launcher-desc">بيانات الشركة، قوالب الطباعة، والنسخ الاحتياطي</div>
            </a>
            @endcan
        </div>
    </div>
</div>
