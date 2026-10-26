<header class="erp-topbar">
    <div class="erp-topbar-start">
        <!-- Sidebar Toggle (Mobile Off-canvas Drawer Trigger) -->
        <button class="erp-top-btn erp-sidebar-toggle-btn" id="sidebarToggleBtn" type="button" title="القائمة الجانبية" aria-label="تبديل القائمة الجانبية">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- BayoSoft ERP Apps Launcher Trigger -->
        <a href="{{ route('home') }}" class="erp-app-launcher-btn" title="تطبيقات النظام (الرئيسية)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <rect x="3" y="3" width="4" height="4" rx="1"/>
                <rect x="10" y="3" width="4" height="4" rx="1"/>
                <rect x="17" y="3" width="4" height="4" rx="1"/>
                <rect x="3" y="10" width="4" height="4" rx="1"/>
                <rect x="10" y="10" width="4" height="4" rx="1"/>
                <rect x="17" y="10" width="4" height="4" rx="1"/>
                <rect x="3" y="17" width="4" height="4" rx="1"/>
                <rect x="10" y="17" width="4" height="4" rx="1"/>
                <rect x="17" y="17" width="4" height="4" rx="1"/>
            </svg>
        </a>

        <!-- Brand Name -->
        <a href="{{ url('/') }}" class="erp-brand">
            @if(!empty($globalCompanySetting?->logo_url))
                <img src="{{ $globalCompanySetting->logo_url }}" alt="{{ $globalCompanySetting?->company_name ?? 'Kayan ERP' }}" class="h-6 w-6 object-contain rounded">
            @else
                <span class="erp-brand-badge">K</span>
            @endif
            <span>{{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</span>
        </a>

        <div class="erp-topbar-divider"></div>

        <span class="erp-module-badge">{{ $moduleName ?? 'لوحة التحكم' }}</span>
    </div>

    <div class="erp-topbar-end">
        <!-- Global Search Input with Shortcut -->
        <div class="erp-global-search">
            <input type="search" id="globalSearchInput" class="erp-global-search-input" placeholder="بحث شامل في النظام...">
            <span class="erp-global-search-icon"><x-icon name="search" size="14" /></span>
            <span class="erp-search-kbd">Ctrl+K</span>
        </div>

        <!-- Density Switcher (Comfortable vs Enterprise Table View) -->
        <button class="erp-top-btn" id="kayanDensityToggle" type="button" title="تبديل كثافة العرض (مؤسسي / افتراضي)">
            <x-icon name="sliders-horizontal" size="14" />
            <span id="densityLabel" class="text-[11px]">افتراضي مريح</span>
        </button>

        <!-- Theme Switcher (Light / Dark) -->
        <button class="erp-top-btn" id="kayanThemeToggle" type="button" title="تبديل الوضع الليلي / النهاري">
            <span id="themeSunIcon"><x-icon name="sun" size="16" /></span>
            <span id="themeMoonIcon"><x-icon name="moon" size="16" /></span>
        </button>

        <!-- Notifications -->
        <div class="erp-dropdown">
            <button class="erp-top-btn" type="button" data-dropdown-toggle="notificationsMenu" title="الإشعارات والأنشطة">
                <x-icon name="bell" size="16" />
                <span class="erp-top-badge">3</span>
            </button>
            <div id="notificationsMenu" class="erp-dropdown-menu w-[280px] left-0">
                <div class="erp-dropdown-header">
                    <strong class="text-xs text-[var(--text-main)]">الإشعارات والأنشطة</strong>
                </div>
                <a href="#" class="erp-dropdown-item">
                    <span class="text-[var(--warning)] inline-flex items-center"><x-icon name="alert-triangle" size="15" /></span>
                    <div>
                        <div class="font-semibold">نواقص مخزون إكسسوارات</div>
                        <small class="text-[var(--text-muted)] text-[10px]">5 أصناف بلغت الحد الأدنى</small>
                    </div>
                </a>
                <a href="#" class="erp-dropdown-item">
                    <span class="text-[var(--info)] inline-flex items-center"><x-icon name="package" size="15" /></span>
                    <div>
                        <div class="font-semibold">شحنة واردة من الصين</div>
                        <small class="text-[var(--text-muted)] text-[10px]">في مرحلة التخليص الجمركي</small>
                    </div>
                </a>
                <a href="#" class="erp-dropdown-item">
                    <span class="text-[var(--success)] inline-flex items-center"><x-icon name="check" size="15" /></span>
                    <div>
                        <div class="font-semibold">قاعدة البيانات جاهزة</div>
                        <small class="text-[var(--text-muted)] text-[10px]">متصل بنجاح مع kayan_erp</small>
                    </div>
                </a>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="erp-dropdown">
            <button class="erp-user-dropdown-btn" type="button" data-dropdown-toggle="userProfileMenu">
                <span class="erp-avatar">{{ mb_substr(auth()->user()?->name ?? 'ك', 0, 1) }}</span>
                <span>{{ auth()->user()?->name ?? 'مدير النظام' }}</span>
                <x-icon name="chevron-down" size="12" class="opacity-80" />
            </button>
            <div id="userProfileMenu" class="erp-dropdown-menu">
                <div class="erp-dropdown-header">
                    <div class="font-bold text-[var(--text-main)]">{{ auth()->user()?->name ?? 'مدير النظام' }}</div>
                    <small class="text-[var(--text-muted)]">{{ auth()->user()?->role_label ?? 'مسؤول إدارة كيان ERP' }}</small>
                </div>
                @can('manage-users')
                <a href="{{ route('users.edit', auth()->user() ?? 1) }}" class="erp-dropdown-item">
                    <x-icon name="user" size="14" />
                    <span>الملف الشخصي</span>
                </a>
                @else
                <a href="#" class="erp-dropdown-item">
                    <x-icon name="user" size="14" />
                    <span>الملف الشخصي</span>
                </a>
                @endcan
                @can('manage-company-settings')
                <a href="{{ route('settings.index') }}" class="erp-dropdown-item">
                    <x-icon name="settings" size="14" />
                    <span>إعدادات النظام</span>
                </a>
                @endcan
                @can('access-sales-portal')
                <a href="{{ route('portal.index') }}" class="erp-dropdown-item text-sky-600 font-semibold">
                    <x-icon name="smartphone" size="14" />
                    <span>بوابة المندوب الميداني (الموبايل)</span>
                </a>
                @endcan
                <button type="button" class="erp-dropdown-item font-semibold text-emerald-600" data-action="install-pwa-app" style="display: none;">
                    <x-icon name="download" size="14" />
                    <span>تثبيت البرنامج على الكمبيوتر</span>
                </button>
                <div class="erp-dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="erp-dropdown-item w-full text-right text-[var(--danger)]">
                        <x-icon name="log-out" size="14" />
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
