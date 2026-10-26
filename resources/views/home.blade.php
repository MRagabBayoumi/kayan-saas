@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تطبيقات {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}" :breadcrumbs="['الرئيسية' => '#']">
        <x-slot:actions>
            @can('access-sales')
                <a href="{{ route('sales.create') }}" class="erp-btn erp-btn-primary">
                    <x-icon name="shopping-cart" size="16" />
                    <span>نقطة البيع (POS)</span>
                </a>
            @endcan
            @can('access-dashboard')
                <a href="{{ route('dashboard') }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="activity" size="16" />
                    <span>لوحة المؤشرات العامة</span>
                </a>
            @endcan
        </x-slot:actions>

        <x-slot:extra>
            @if(isset($activeShift) && $activeShift)
                <span class="erp-badge erp-badge-success font-numeric">
                    <x-icon name="check-circle" size="14" />
                    <span>وردية نشطة: {{ $activeShift->shift_number }} (درج: {{ number_format($activeShift->expected_closing_balance, 2) }} ج.م)</span>
                </span>
            @endif
            <span class="erp-badge erp-badge-neutral">
                <x-icon name="user" size="14" />
                <span>{{ auth()->user()->name }} ({{ auth()->user()->role_label }})</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="home-apps">
        <!-- Status Alerts -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Welcome Apps Hub Hero -->
        <div class="home-hero-card mb-5">
            <div class="flex items-center gap-4">
                @if(!empty($globalCompanySetting?->logo_url))
                    <img src="{{ $globalCompanySetting->logo_url }}" alt="{{ $globalCompanySetting?->company_name ?? 'Kayan' }}" class="h-16 w-16 object-contain rounded-lg border border-[var(--border-color)] bg-white p-1 shrink-0">
                @endif
                <div class="home-hero-content">
                    <div class="home-hero-badge">
                        <x-icon name="grid" size="14" />
                        <span>منظومة {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }} المتكاملة</span>
                    </div>
                    <h1 class="home-hero-title">مرحباً بك، {{ auth()->user()->name }}</h1>
                    <p class="home-hero-desc">
                        اختر التطبيق المطلوب للمتابعة. تظهر لك أدناه التطبيقات والصلاحيات المخصصة لحسابك ومكان عملك.
                    </p>
                </div>
            </div>
            <div class="home-hero-meta">
                <div class="home-hero-workplace">
                    <span class="text-xs text-[var(--text-muted)]">مكان العمل المحدد:</span>
                    <strong class="text-sm text-[var(--text-main)]">
                        @if(auth()->user()->can_access_all_warehouses || auth()->user()->isAdmin())
                            كافة المستودعات والفروع
                        @else
                            {{ auth()->user()->warehouse?->name ?? 'الفرع الافتراضي' }}
                        @endif
                    </strong>
                </div>
            </div>
        </div>

        <!-- Applications Grid -->
        <div class="apps-hub-grid">
            <!-- 1. POS Cashier Application -->
            @can('access-sales')
            <div class="app-card app-card-emerald">
                <div class="app-card-header">
                    <div class="app-icon-box bg-emerald-500/10 text-emerald-500">
                        <x-icon name="shopping-cart" size="28" />
                    </div>
                    <span class="app-badge badge-emerald">نقطة بيع سريعة</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">نقطة البيع (الكاشير - POS)</h3>
                    <p class="app-desc">شاشة كاشير فائقة السرعة للمبيعات السريعة، الباركود، الفئات السعرية، وإصدار الفواتير الفورية.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('sales.create') }}" class="erp-btn erp-btn-primary w-full justify-center">
                        <x-icon name="external-link" size="16" />
                        <span>فتح شاشة الكاشير</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 2. Sales Invoices Application -->
            @can('access-sales')
            <div class="app-card app-card-blue">
                <div class="app-card-header">
                    <div class="app-icon-box bg-blue-500/10 text-blue-500">
                        <x-icon name="file-text" size="28" />
                    </div>
                    <span class="app-badge badge-blue">المبيعات</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">فواتير المبيعات والعملاء</h3>
                    <p class="app-desc">سجل فواتير البيع، تكلفة البضاعة المباعة (FIFO)، هوامش الأرباح، وإلغاء وتعديل الفواتير.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('sales.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="list" size="16" />
                        <span>عرض فواتير المبيعات</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 3. Cashier Shifts & Drawer Application -->
            @can('manage-shifts')
            <div class="app-card app-card-purple">
                <div class="app-card-header">
                    <div class="app-icon-box bg-purple-500/10 text-purple-500">
                        <x-icon name="credit-card" size="28" />
                    </div>
                    @if(isset($activeShift) && $activeShift)
                        <span class="app-badge badge-emerald">وردية نشطة</span>
                    @else
                        <span class="app-badge badge-neutral">الخزينة والدرج</span>
                    @endif
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">ورديات وخزينة الكاشير</h3>
                    <p class="app-desc">فتح وإغلاق ورديات الكاشير، عهدة البداية، تسجيل المصروفات، وجرد النقدية ومطابقة الفروقات.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('shifts.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="clock" size="16" />
                        <span>إدارة الورديات والخزينة</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 4. Purchases & Supply Application -->
            @can('access-purchases')
            <div class="app-card app-card-amber">
                <div class="app-card-header">
                    <div class="app-icon-box bg-amber-500/10 text-amber-500">
                        <x-icon name="shopping-bag" size="28" />
                    </div>
                    <span class="app-badge badge-amber">التوريد</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">المشتريات والتوريدات</h3>
                    <p class="app-desc">إدخال فواتير الشراء، توليد دفعات التكلفة، تسعير التجزئة والجملة، وإدارة حسابات الموردين والشحن.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('purchases.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="truck" size="16" />
                        <span>دخول المشتريات</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 5. Inventory & Warehouses Application -->
            @can('access-inventory')
            <div class="app-card app-card-cyan">
                <div class="app-card-header">
                    <div class="app-icon-box bg-cyan-500/10 text-cyan-500">
                        <x-icon name="package" size="28" />
                    </div>
                    <span class="app-badge badge-cyan">المخازن</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">المستودعات والمخزون</h3>
                    <p class="app-desc">أرصدة المخازن الحية، بطاقات حركة الصنف، دفعات الـ FIFO، والنواقص والتحويلات الداخلية.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="database" size="16" />
                        <span>فحص المخزون</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 6. Products & Catalog Application -->
            @can('view-products')
            <div class="app-card app-card-indigo">
                <div class="app-card-header">
                    <div class="app-icon-box bg-indigo-500/10 text-indigo-500">
                        <x-icon name="tag" size="28" />
                    </div>
                    <span class="app-badge badge-indigo">الكتالوج</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">دليل الأصناف والمنتجات</h3>
                    <p class="app-desc">دليل الأصناف، أكواد الباركود، طباعة الملصقات الحرارية، التصنيفات، والاستيراد الذكي من إكسل.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="layers" size="16" />
                        <span>تصفح المنتجات</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 7. Customers Directory -->
            @if(auth()->user()->can('access-customers') || auth()->user()->can('manage-customers') || auth()->user()->can('access-sales'))
            <div class="app-card app-card-teal">
                <div class="app-card-header">
                    <div class="app-icon-box bg-teal-500/10 text-teal-500">
                        <x-icon name="users" size="28" />
                    </div>
                    <span class="app-badge badge-teal">العملاء</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">دليل العملاء وتجار الجملة</h3>
                    <p class="app-desc">بيانات العملاء، تحديد فئات الأسعار الافتراضية، وسجل التعاملات والاتصال.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="user-check" size="16" />
                        <span>إدارة العملاء</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 8. Suppliers Directory -->
            @can('access-purchases')
            <div class="app-card app-card-amber">
                <div class="app-card-header">
                    <div class="app-icon-box bg-amber-500/10 text-amber-600">
                        <x-icon name="briefcase" size="28" />
                    </div>
                    <span class="app-badge badge-amber">الموردين</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">دليل الموردين ومكاتب الشحن</h3>
                    <p class="app-desc">سجل الموردين المحليين وموردي الصين ومكاتب الشحن الدولي والتخليص الجمركي.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="truck" size="16" />
                        <span>إدارة الموردين</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 9. Finance, Treasuries & Expenses -->
            @if(auth()->user()->can('manage-treasuries') || auth()->user()->can('manage-expenses') || auth()->user()->can('manage-company-settings') || auth()->user()->isAdmin())
            <div class="app-card app-card-emerald">
                <div class="app-card-header">
                    <div class="app-icon-box bg-emerald-500/10 text-emerald-600">
                        <x-icon name="landmark" size="28" />
                    </div>
                    <span class="app-badge badge-emerald">المالية والحسابات</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">الخزائن والمالية والمصروفات</h3>
                    <p class="app-desc">إدارة أرصدة الخزائن والبنوك، التحويلات النقدية، تسجيل المصروفات وسندات الصرف، ومتابعة مدفوعات ومتحصلات العملاء والموردين.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('treasuries.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="banknote" size="16" />
                        <span>فتح الخزائن والمالية</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- 10. Management KPIs Dashboard (Hidden from cashiers) -->
            @can('access-dashboard')
            <div class="app-card app-card-rose">
                <div class="app-card-header">
                    <div class="app-icon-box bg-rose-500/10 text-rose-500">
                        <x-icon name="activity" size="28" />
                    </div>
                    <span class="app-badge badge-rose">لوحة الإدارة</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">لوحة المؤشرات والتحليلات (Dashboard)</h3>
                    <p class="app-desc">مؤشرات الأداء المالي، مبيعات اليوم، إحصائيات المخزون، والتقارير التنفيذية للشركة.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('dashboard') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="bar-chart-2" size="16" />
                        <span>فتح الداشبورد</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- Sales Representatives Management & Performance Analytics -->
            @hasModule('sales_reps')
            @can('manage-sales-reps')
            <div class="app-card app-card-indigo">
                <div class="app-card-header">
                    <div class="app-icon-box bg-indigo-500/10 text-indigo-600">
                        <x-icon name="user-check" size="28" />
                    </div>
                    <span class="app-badge badge-indigo">إدارة وتحليل المناديب</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إدارة وتحليل أداء مناديب المبيعات</h3>
                    <p class="app-desc">إضافة المناديب الجدد، ضبط نسب العمولات، متابعة مبيعات كل مندوب، فواتيره، عملائه، وصرف المستحقات المالية.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="bar-chart-2" size="16" />
                        <span>لوحة أداء المناديب</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- Sales Representative Field Mobile Portal -->
            @can('access-sales-portal')
            <div class="app-card app-card-sky">
                <div class="app-card-header">
                    <div class="app-icon-box bg-sky-500/10 text-sky-600">
                        <x-icon name="smartphone" size="28" />
                    </div>
                    <span class="app-badge badge-sky">موبايل وتطبيق مناديب</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">بوابة المندوب والمبيعات الميدانية</h3>
                    <p class="app-desc">واجهة سريعة للموبايل والتابلت لتسجيل العملاء الجدد ومواقعهم (GPS)، تصفح الكتالوج، إصدار الطلبات، ومتابعة العمولات.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('portal.index') }}" class="erp-btn erp-btn-primary w-full justify-center">
                        <x-icon name="external-link" size="16" />
                        <span>فتح بوابة المندوب</span>
                    </a>
                </div>
            </div>
            @endcan
            @endhasModule

            <!-- 10. Users & Employees Management -->
            @can('manage-users')
            <div class="app-card app-card-slate">
                <div class="app-card-header">
                    <div class="app-icon-box bg-slate-500/10 text-slate-500">
                        <x-icon name="user" size="28" />
                    </div>
                    <span class="app-badge badge-neutral">الموظفون</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إدارة المستخدمين والموظفين</h3>
                    <p class="app-desc">إنشاء حسابات الكاشير، تعيين أماكن العمل والفروع، وتحديد قيود الوصول للمستودعات.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="settings" size="16" />
                        <span>إدارة الموظفين</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 11. Roles & Permissions -->
            @can('manage-roles')
            <div class="app-card app-card-violet">
                <div class="app-card-header">
                    <div class="app-icon-box bg-violet-500/10 text-violet-500">
                        <x-icon name="shield" size="28" />
                    </div>
                    <span class="app-badge badge-violet">الأمان</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">الأدوار ومصفوفة الصلاحيات</h3>
                    <p class="app-desc">تصميم الأدوار الوظيفية الديناميكية، تخصيص مصفوفة الصلاحيات الدقيقة لكل موظف وقسم.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('roles.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="key" size="16" />
                        <span>مصفوفة الصلاحيات</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 12. Units of Measurement -->
            @can('manage-units')
            <div class="app-card app-card-sky">
                <div class="app-card-header">
                    <div class="app-icon-box bg-sky-500/10 text-sky-500">
                        <x-icon name="maximize-2" size="28" />
                    </div>
                    <span class="app-badge badge-neutral">القياس</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">وحدات القياس والتعبئة</h3>
                    <p class="app-desc">إدارة وحدات البيع والشراء (قطعة، دستة، كرتونة)، وضبط معاملات التحويل الرياضية.</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('units.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="sliders" size="16" />
                        <span>ضبط الوحدات</span>
                    </a>
                </div>
            </div>
            @endcan

            <!-- 13. System Settings & Print Templates -->
            @can('manage-company-settings')
            <div class="app-card app-card-stone">
                <div class="app-card-header">
                    <div class="app-icon-box bg-stone-500/10 text-stone-500">
                        <x-icon name="settings" size="28" />
                    </div>
                    <span class="app-badge badge-neutral">النظام</span>
                </div>
                <div class="app-card-body">
                    <h3 class="app-title">إعدادات المؤسسة والطباعة</h3>
                    <p class="app-desc">بيانات الشركة، الشعار، ومصمم قوالب الطباعة الذكية (A4، A5، والحراري 80mm).</p>
                </div>
                <div class="app-card-footer">
                    <a href="{{ route('settings.index') }}" class="erp-btn erp-btn-secondary w-full justify-center">
                        <x-icon name="sliders" size="16" />
                        <span>إعدادات النظام</span>
                    </a>
                </div>
            </div>
            @endcan
        </div>
    </div>
@endsection
