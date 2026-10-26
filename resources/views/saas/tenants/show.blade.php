<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف المشترك — {{ $tenant->name }} | Kayan SaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --odoo-primary: #714B67;
            --odoo-primary-dark: #56394e;
            --odoo-primary-light: #8e6283;
            --odoo-secondary: #00A09D;
            --odoo-bg: #f8fafc;
            --odoo-border: #e2e8f0;
            --odoo-text-main: #0f172a;
            --odoo-text-muted: #64748b;
            --odoo-success: #10b981;
            --odoo-warning: #f59e0b;
            --odoo-danger: #ef4444;
            --odoo-info: #0284c7;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            background-color: var(--odoo-bg);
            color: var(--odoo-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Odoo Navbar */
        .odoo-navbar {
            background-color: var(--odoo-primary);
            color: #ffffff;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .odoo-nav-start { display: flex; align-items: center; gap: 20px; }
        .odoo-brand { font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .odoo-brand .badge-saas {
            background: var(--odoo-secondary);
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
        }
        .odoo-nav-links { display: flex; align-items: center; gap: 6px; margin-right: 20px; }
        .odoo-nav-item {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.15s;
        }
        .odoo-nav-item:hover, .odoo-nav-item.active {
            color: #ffffff;
            background: rgba(255,255,255,0.16);
        }

        /* Control Panel */
        .odoo-control-panel {
            background: #ffffff;
            border-bottom: 1px solid var(--odoo-border);
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .odoo-cp-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--odoo-text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .odoo-breadcrumbs {
            font-size: 12px;
            color: var(--odoo-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
        }
        .odoo-breadcrumbs a {
            color: var(--odoo-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .odoo-breadcrumbs a:hover { text-decoration: underline; }
        .odoo-cp-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Buttons */
        .btn-primary {
            background-color: var(--odoo-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(113,75,103,0.2);
        }
        .btn-primary:hover {
            background-color: var(--odoo-primary-dark);
            transform: translateY(-1px);
        }
        .btn-success {
            background-color: var(--odoo-success);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(16,185,129,0.2);
        }
        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background-color: #ffffff;
            color: var(--odoo-text-main);
            border: 1px solid var(--odoo-border);
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-secondary:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        /* Main Container */
        .odoo-content {
            padding: 24px 28px;
            max-width: 1650px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        /* Dossier Header Card (Like Customer Profile) */
        .profile-dossier-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        .profile-header-main {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            background: rgba(113, 75, 103, 0.1);
            color: var(--odoo-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .profile-title h2 {
            font-size: 22px;
            font-weight: 900;
            color: var(--odoo-text-main);
            letter-spacing: -0.3px;
        }
        .profile-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-status.active { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-status.frozen { background: #fef2f2; color: #b91c1c; border: 1px solid #fecdd3; }
        .badge-status.expired { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

        .subdomain-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            color: var(--odoo-primary);
            border: 1px solid var(--odoo-border);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            direction: ltr;
        }
        .subdomain-pill:hover { background: #f1f5f9; }

        .profile-financial-summary {
            text-align: left;
            background: #fafafa;
            border: 1px solid #f1f5f9;
            padding: 14px 20px;
            border-radius: 10px;
            min-width: 260px;
        }
        .pfs-label { font-size: 11px; color: var(--odoo-text-muted); font-weight: 700; }
        .pfs-val { font-size: 24px; font-weight: 900; color: #047857; margin-top: 2px; }
        .pfs-sub { font-size: 11px; color: var(--odoo-text-muted); margin-top: 3px; }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%;
            background: var(--odoo-primary);
        }
        .kpi-card.success::before { background: var(--odoo-success); }
        .kpi-card.info::before { background: var(--odoo-info); }
        .kpi-card.warning::before { background: var(--odoo-warning); }
        .kpi-label { font-size: 12px; font-weight: 700; color: var(--odoo-text-muted); margin-bottom: 4px; }
        .kpi-val { font-size: 22px; font-weight: 800; color: var(--odoo-text-main); }
        .kpi-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .kpi-icon {
            width: 44px; height: 44px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc; color: var(--odoo-primary);
            flex-shrink: 0;
        }

        /* Tabs Navigation */
        .tabs-header {
            display: flex;
            align-items: center;
            gap: 4px;
            border-bottom: 2px solid var(--odoo-border);
            margin-bottom: 20px;
            background: #ffffff;
            padding: 6px 12px 0 12px;
            border-radius: 10px 10px 0 0;
            overflow-x: auto;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            color: var(--odoo-text-muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--odoo-primary); }
        .tab-btn.active {
            color: var(--odoo-primary);
            border-bottom-color: var(--odoo-primary);
            background: rgba(113, 75, 103, 0.04);
            border-radius: 6px 6px 0 0;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Tables & Cards */
        .card-box {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        .card-box-header {
            padding: 16px 20px;
            background: #fafafa;
            border-bottom: 1px solid var(--odoo-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-box-header h3 { font-size: 15px; font-weight: 800; color: var(--odoo-text-main); display: flex; align-items: center; gap: 8px; }

        table.data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: right;
            font-size: 13px;
        }
        table.data-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            padding: 12px 16px;
            border-bottom: 2px solid var(--odoo-border);
            white-space: nowrap;
        }
        table.data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            white-space: nowrap;
        }
        table.data-table tr:hover td { background: #fbfcfe; }

        /* Modules Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            padding: 20px;
        }
        .module-card {
            border: 1px solid var(--odoo-border);
            border-radius: 10px;
            padding: 18px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
        }
        .module-card.enabled { border-color: #a7f3d0; background: #f0fdf4; }
        .module-card.disabled { border-color: #e2e8f0; background: #f8fafc; opacity: 0.8; }
        .module-info h4 { font-size: 14px; font-weight: 800; color: var(--odoo-text-main); }
        .module-info p { font-size: 11px; color: var(--odoo-text-muted); margin-top: 2px; }

        /* Modals */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none; align-items: center; justify-content: center;
            z-index: 100; backdrop-filter: blur(3px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #ffffff; border-radius: 12px;
            width: 100%; max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
            overflow: hidden; max-height: 90vh;
            display: flex; flex-direction: column;
        }
        .modal-header {
            padding: 16px 22px; border-bottom: 1px solid var(--odoo-border);
            display: flex; justify-content: space-between; align-items: center;
            background: #f8fafc;
        }
        .modal-header h3 { font-size: 16px; font-weight: 800; color: var(--odoo-primary); }
        .modal-close { background: none; border: none; font-size: 22px; cursor: pointer; color: var(--odoo-text-muted); }
        .modal-body { padding: 22px; overflow-y: auto; flex: 1; }
        .modal-footer {
            padding: 14px 22px; border-top: 1px solid var(--odoo-border);
            display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; }
        .form-control {
            width: 100%; padding: 9px 12px; border: 1px solid var(--odoo-border);
            border-radius: 6px; font-family: inherit; font-size: 13px;
        }
        .form-control:focus {
            outline: none; border-color: var(--odoo-primary);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
        }

        .alert-box {
            padding: 12px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecdd3; }
    </style>
</head>
<body>

    <!-- Odoo Navbar -->
    <header class="odoo-navbar">
        <div class="odoo-nav-start">
            <div class="odoo-brand">
                <span>Kayan ERP</span>
                <span class="badge-saas">Cloud SaaS</span>
            </div>
            <nav class="odoo-nav-links">
                <a href="{{ route('saas.index') }}" class="odoo-nav-item active">
                    <x-icon name="layout-dashboard" size="15" />
                    <span>لوحة التحكم</span>
                </a>
                <a href="{{ route('saas.plans.index') }}" class="odoo-nav-item">
                    <x-icon name="tag" size="15" />
                    <span>الباقات والأسعار</span>
                </a>
                <a href="{{ route('saas.subscriptions.index') }}" class="odoo-nav-item">
                    <x-icon name="credit-card" size="15" />
                    <span>الاشتراكات</span>
                </a>
            </nav>
        </div>
        <div>
            <a href="{{ route('saas.index') }}" class="btn-secondary" style="font-size: 12px; padding: 5px 12px;">
                <x-icon name="arrow-right" size="14" />
                <span>العودة لقائمة المشتركين</span>
            </a>
        </div>
    </header>

    <!-- Odoo Sub-Control Panel -->
    <section class="odoo-control-panel">
        <div class="odoo-cp-title">
            <h1>
                <x-icon name="building" size="20" />
                <span>ملف المشترك: {{ $tenant->name }}</span>
            </h1>
            <div class="odoo-breadcrumbs">
                <a href="{{ route('saas.index') }}">لوحة المشتركين المركزية</a>
                <span>/</span>
                <span>تفاصيل وسدادات المشترك</span>
            </div>
        </div>
        <div class="odoo-cp-actions">
            <!-- Open ERP Subdomain -->
            <a href="{{ $tenantUrl }}" target="_blank" class="btn-primary" title="فتح لوحة تحكم المشترك في تبويب جديد">
                <x-icon name="external-link" size="14" />
                <span>فتح النظام ({{ $tenant->subdomain }})</span>
            </a>

            <!-- Record Payment -->
            <button type="button" class="btn-success" onclick="openPaymentModal()" title="تسجيل دفعة سداد جديدة أو سند قبض">
                <x-icon name="dollar-sign" size="15" />
                <span>تسجيل سداد / سند قبض</span>
            </button>

            <!-- Edit Subscription -->
            <button type="button" class="btn-secondary" onclick="openEditSubModal()" title="تعديل دورة الفاتورة وتاريخ الصلاحية والباقة">
                <x-icon name="calendar" size="14" />
                <span>تعديل دورة الفاتورة</span>
            </button>

            <!-- Toggle Active / Suspend -->
            <form action="{{ route('saas.tenants.toggle-status', $tenant->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn-secondary" title="{{ $tenant->is_active ? 'إيقاف مؤقت للنظام' : 'إعادة تنشيط' }}">
                    @if($tenant->is_active)
                        <x-icon name="shield" size="14" />
                        <span>تجميد المشترك</span>
                    @else
                        <x-icon name="zap" size="14" />
                        <span>تنشيط المشترك</span>
                    @endif
                </button>
            </form>
        </div>
    </section>

    <!-- Main Content -->
    <main class="odoo-content">

        @if(session('message'))
            <div class="alert-box alert-success">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert-box alert-error">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- 1. Dossier Profile Card (Like Customer Profile Header) -->
        <div class="profile-dossier-card">
            <div class="profile-header-main">
                <div class="profile-avatar">
                    <x-icon name="building" size="32" />
                </div>
                <div class="profile-title">
                    <h2>{{ $tenant->name }}</h2>
                    <div class="profile-badges">
                        <a href="{{ $tenantUrl }}" target="_blank" class="subdomain-pill" title="فتح الرابط">
                            <span>{{ $tenant->subdomain }}.{{ $baseHost }}{{ $portSuffix }}</span>
                            <x-icon name="external-link" size="12" />
                        </a>
                        <span style="background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                            باقة: {{ $tenant->plan_name }}
                        </span>
                        @if(! $tenant->is_active)
                            <span class="badge-status frozen">
                                <x-icon name="shield" size="12" />
                                <span>مجمد</span>
                            </span>
                        @elseif($tenant->isExpired())
                            <span class="badge-status expired">
                                <x-icon name="alert-triangle" size="12" />
                                <span>منتهي الصلاحية</span>
                            </span>
                        @else
                            <span class="badge-status active">
                                <x-icon name="check-circle" size="12" />
                                <span>ساري ونشط</span>
                            </span>
                        @endif
                        <span style="font-size: 11px; color: var(--odoo-text-muted);">
                            تاريخ التسجيل: {{ $tenant->created_at->format('Y-m-d') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Box -->
            <div class="profile-financial-summary">
                <div class="pfs-label">إجمالي السدادات المسجلة</div>
                <div class="pfs-val">{{ number_format($totalPaid, 2) }} <span style="font-size: 14px;">ج.م</span></div>
                <div class="pfs-sub">
                    قيمة الاشتراك: <strong>{{ number_format($tenant->subscription_price, 2) }} ج.م</strong>
                    ({{ $tenant->billing_cycle === 'yearly' ? 'سنوياً' : 'شهرياً' }})
                </div>
            </div>
        </div>

        <!-- 2. KPI Metrics Grid -->
        <div class="kpi-grid">
            <div class="kpi-card {{ $tenant->isExpired() ? 'warning' : 'success' }}">
                <div>
                    <div class="kpi-label">تاريخ انتهاء الاشتراك</div>
                    <div class="kpi-val" style="font-size: 18px;">
                        {{ $tenant->expires_at ? $tenant->expires_at->format('Y-m-d') : 'غير محدد' }}
                    </div>
                    <div class="kpi-sub">
                        @if($tenant->isExpired())
                            <span style="color: var(--odoo-danger); font-weight: 700;">انتهت الصلاحية!</span>
                        @elseif($tenant->daysRemaining() !== null)
                            <span>متبقي <strong>{{ $tenant->daysRemaining() }}</strong> يوماً</span>
                        @else
                            <span>غير محدود</span>
                        @endif
                    </div>
                </div>
                <div class="kpi-icon">
                    <x-icon name="calendar" size="22" />
                </div>
            </div>

            <div class="kpi-card info">
                <div>
                    <div class="kpi-label">المستخدمين الحاليين في ERP</div>
                    <div class="kpi-val">{{ $userCount }} <span style="font-size: 14px; font-weight: 600; color: var(--odoo-text-muted);">/ {{ $tenant->max_users }}</span></div>
                    <div class="kpi-sub">حسابات نشطة داخل قاعدة بيانات العميل</div>
                </div>
                <div class="kpi-icon">
                    <x-icon name="users" size="22" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div class="kpi-label">عدد حركات السداد</div>
                    <div class="kpi-val">{{ $tenant->payments->count() }}</div>
                    <div class="kpi-sub">إيصالات وفواتير مسجلة للمشترك</div>
                </div>
                <div class="kpi-icon">
                    <x-icon name="receipt" size="22" />
                </div>
            </div>

            <div class="kpi-card">
                <div>
                    <div class="kpi-label">قاعدة البيانات المعزولة</div>
                    <div class="kpi-val" style="font-size: 13px; font-family: monospace; font-weight: 700; color: var(--odoo-primary);">
                        {{ $tenant->database_name }}
                    </div>
                    <div class="kpi-sub">عزل فيزيائي تام (DB-per-Tenant)</div>
                </div>
                <div class="kpi-icon">
                    <x-icon name="database" size="22" />
                </div>
            </div>
        </div>

        <!-- 3. Navigation Tabs -->
        <div class="tabs-header">
            <button type="button" class="tab-btn active" onclick="switchTab('tab-payments')">
                <x-icon name="credit-card" size="15" />
                <span>سجل السدادات والمقبوضات ({{ $tenant->payments->count() }})</span>
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('tab-modules')">
                <x-icon name="layers" size="15" />
                <span>الموديولات والخصائص المفعلة</span>
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('tab-database')">
                <x-icon name="database" size="15" />
                <span>عزل قاعدة البيانات والخادم</span>
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('tab-notes')">
                <x-icon name="file-text" size="15" />
                <span>ملاحظات وبيانات المشترك</span>
            </button>
        </div>

        <!-- TAB 1: PAYMENTS (السدادات) -->
        <div id="tab-payments" class="tab-content active">
            <div class="card-box">
                <div class="card-box-header">
                    <h3>
                        <x-icon name="receipt" size="18" />
                        <span>سجل سدادات واشتراكات المشترك</span>
                    </h3>
                    <button type="button" class="btn-success" onclick="openPaymentModal()" style="font-size: 12px; padding: 6px 12px;">
                        <x-icon name="plus" size="14" />
                        <span>تسجيل سداد جديد</span>
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الإيصال</th>
                                <th>تاريخ السداد</th>
                                <th>المبلغ المدفوع</th>
                                <th>طريقة الدفع</th>
                                <th>دورة الفاتورة</th>
                                <th>الشهور المغطاة</th>
                                <th>صلاحية الفترة</th>
                                <th>المرجع / العملية</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->payments as $index => $pay)
                                <tr>
                                    <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                                    <td>
                                        <strong style="color: var(--odoo-primary); font-family: monospace;">{{ $pay->payment_number }}</strong>
                                    </td>
                                    <td>{{ $pay->payment_date->format('Y-m-d') }}</td>
                                    <td>
                                        <strong style="color: #047857; font-size: 14px;">{{ number_format($pay->amount, 2) }} ج.م</strong>
                                    </td>
                                    <td>
                                        <span style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                            {{ $pay->payment_method_name }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $pay->billing_cycle === 'yearly' ? 'اشتراك سنوي' : 'اشتراك شهري' }}
                                    </td>
                                    <td>{{ $pay->months_covered }} شهر</td>
                                    <td>
                                        @if($pay->period_starts_at && $pay->period_ends_at)
                                            <span style="font-size: 11px; color: var(--odoo-text-muted);">
                                                من {{ $pay->period_starts_at->format('Y-m-d') }} إلى {{ $pay->period_ends_at->format('Y-m-d') }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="font-family: monospace; font-size: 11px;">
                                        {{ $pay->transaction_reference ?: '—' }}
                                    </td>
                                    <td>{{ $pay->notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px; color: var(--odoo-text-muted);">
                                        <x-icon name="receipt" size="32" style="opacity: 0.5; margin-bottom: 8px;" />
                                        <div>لا توجد سدادات مسجلة لهذا المشترك حتى الآن.</div>
                                        <button type="button" class="btn-success" onclick="openPaymentModal()" style="margin-top: 12px;">
                                            <x-icon name="plus" size="14" />
                                            <span>تسجيل أول دفعة سداد</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: MODULES (الموديولات) -->
        <div id="tab-modules" class="tab-content">
            <div class="card-box">
                <div class="card-box-header">
                    <h3>
                        <x-icon name="layers" size="18" />
                        <span>التحكم الفوري بموديولات المشترك</span>
                    </h3>
                    <span style="font-size: 12px; color: var(--odoo-text-muted);">التغييرات تنعكس فوراً على قائمة وشاشات المشترك</span>
                </div>

                @php $mods = $tenant->modules ?? []; @endphp
                <div class="modules-grid">
                    <!-- Accounting -->
                    <div class="module-card {{ ($mods['accounting'] ?? false) ? 'enabled' : 'disabled' }}">
                        <div class="module-info">
                            <h4>المحاسبة العامة وشجرة الحسابات</h4>
                            <p>دليل الحسابات، القيود اليومية، ميزان المراجعة والتقارير المالية</p>
                        </div>
                        <form action="{{ route('saas.tenants.toggle-module', $tenant->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="module" value="accounting">
                            <button type="submit" class="btn-secondary" style="font-size: 11px; padding: 5px 12px;">
                                @if($mods['accounting'] ?? false)
                                    <x-icon name="check" size="12" style="color: #047857;" />
                                    <span>مفعل (إغلاق)</span>
                                @else
                                    <x-icon name="x" size="12" style="color: #ef4444;" />
                                    <span>معطل (تفعيل)</span>
                                @endif
                            </button>
                        </form>
                    </div>

                    <!-- Sales Reps -->
                    <div class="module-card {{ ($mods['sales_reps'] ?? false) ? 'enabled' : 'disabled' }}">
                        <div class="module-info">
                            <h4>إدارة المناديب والتوزيع</h4>
                            <p>خطوط السير، زيارات العملاء، الحضور والانصراف وعمولات المبيعات</p>
                        </div>
                        <form action="{{ route('saas.tenants.toggle-module', $tenant->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="module" value="sales_reps">
                            <button type="submit" class="btn-secondary" style="font-size: 11px; padding: 5px 12px;">
                                @if($mods['sales_reps'] ?? false)
                                    <x-icon name="check" size="12" style="color: #047857;" />
                                    <span>مفعل (إغلاق)</span>
                                @else
                                    <x-icon name="x" size="12" style="color: #ef4444;" />
                                    <span>معطل (تفعيل)</span>
                                @endif
                            </button>
                        </form>
                    </div>

                    <!-- Installments -->
                    <div class="module-card {{ ($mods['installments'] ?? false) ? 'enabled' : 'disabled' }}">
                        <div class="module-info">
                            <h4>نظام بيع الأقساط</h4>
                            <p>جدولة الأقساط الشهرية، تحصيل الأقساط، وغرامات التأخير</p>
                        </div>
                        <form action="{{ route('saas.tenants.toggle-module', $tenant->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="module" value="installments">
                            <button type="submit" class="btn-secondary" style="font-size: 11px; padding: 5px 12px;">
                                @if($mods['installments'] ?? false)
                                    <x-icon name="check" size="12" style="color: #047857;" />
                                    <span>مفعل (إغلاق)</span>
                                @else
                                    <x-icon name="x" size="12" style="color: #ef4444;" />
                                    <span>معطل (تفعيل)</span>
                                @endif
                            </button>
                        </form>
                    </div>

                    <!-- E-Commerce -->
                    <div class="module-card {{ ($mods['ecommerce'] ?? false) ? 'enabled' : 'disabled' }}">
                        <div class="module-info">
                            <h4>المتجر الإلكتروني أونلاين</h4>
                            <p>واجهة متجر احترافية للعملاء، سلة شراء، وطلبات أونلاين</p>
                        </div>
                        <form action="{{ route('saas.tenants.toggle-module', $tenant->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="module" value="ecommerce">
                            <button type="submit" class="btn-secondary" style="font-size: 11px; padding: 5px 12px;">
                                @if($mods['ecommerce'] ?? false)
                                    <x-icon name="check" size="12" style="color: #047857;" />
                                    <span>مفعل (إغلاق)</span>
                                @else
                                    <x-icon name="x" size="12" style="color: #ef4444;" />
                                    <span>معطل (تفعيل)</span>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: DATABASE & ISOLATION -->
        <div id="tab-database" class="tab-content">
            <div class="card-box p-6" style="padding: 24px;">
                <h3 style="font-size: 16px; font-weight: 800; color: var(--odoo-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="database" size="18" />
                    <span>تفاصيل عزل قاعدة البيانات المستقلة (DB-per-Tenant)</span>
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                    <div style="background: #f8fafc; border: 1px solid var(--odoo-border); border-radius: 8px; padding: 16px;">
                        <span style="font-size: 12px; color: var(--odoo-text-muted); font-weight: 700;">اسم قاعدة البيانات الفيزيائية:</span>
                        <div style="font-family: monospace; font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                            {{ $tenant->database_name }}
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--odoo-border); border-radius: 8px; padding: 16px;">
                        <span style="font-size: 12px; color: var(--odoo-text-muted); font-weight: 700;">مستوى الأمان والعزل:</span>
                        <div style="color: #047857; font-size: 14px; font-weight: 800; margin-top: 4px;">
                            ✓ 100% قاعدة بيانات مخصصة ومعزولة تماماً
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--odoo-border); border-radius: 8px; padding: 16px;">
                        <span style="font-size: 12px; color: var(--odoo-text-muted); font-weight: 700;">رابط الدخول المباشر للمشترك:</span>
                        <div style="margin-top: 4px;">
                            <a href="{{ $tenantUrl }}" target="_blank" style="color: var(--odoo-primary); font-family: monospace; font-weight: 700; text-decoration: none;">
                                {{ $tenantUrl }} ↗
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: NOTES -->
        <div id="tab-notes" class="tab-content">
            <div class="card-box p-6" style="padding: 24px;">
                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 12px;">ملاحظات وتفاصيل التعاقد</h3>
                <div style="background: #f8fafc; border: 1px solid var(--odoo-border); border-radius: 8px; padding: 16px; min-height: 100px;">
                    {{ $tenant->notes ?: 'لا توجد ملاحظات مسجلة لهذا المشترك.' }}
                </div>
            </div>
        </div>

    </main>

    <!-- Modal: Record Payment (سند قبض اشتراك) -->
    <div id="paymentModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="dollar-sign" size="18" />
                    <span>تسجيل دفعة سداد جديدة للمشترك</span>
                </h3>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <form action="{{ route('saas.tenants.record-payment', $tenant->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px;">
                        <span style="font-size: 12px; color: var(--odoo-text-muted);">المشترك:</span>
                        <strong style="color: var(--odoo-text-main); margin-right: 6px;">{{ $tenant->name }}</strong>
                        <span style="font-size: 12px; color: var(--odoo-text-muted); margin-right: 12px;">الباقة: {{ $tenant->plan_name }}</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>المبلغ المدفوع (ج.م) *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required value="{{ $tenant->subscription_price }}">
                        </div>
                        <div class="form-group">
                            <label>تاريخ السداد *</label>
                            <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>طريقة الدفع *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">نقدي (كاش)</option>
                                <option value="instapay">إنستاباي (InstaPay)</option>
                                <option value="vodafone_cash">فودافون كاش / محفظة</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="visa">بطاقة بنكية / فيزا</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>عدد الشهور المغطاة *</label>
                            <input type="number" name="months_covered" class="form-control" required min="1" value="{{ $tenant->billing_cycle === 'yearly' ? 12 : 1 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>رقم العملية / المرجع البنكي (اختياري)</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="مثال: REF-10928372">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="auto_extend" value="1" checked style="width: 16px; height: 16px;">
                            <span>تحديث وتمديد تاريخ انتهاء الاشتراك تلقائياً بناءً على السداد</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="أي تفاصيل أو ملاحظات حول عملية التحصيل"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closePaymentModal()">إلغاء</button>
                    <button type="submit" class="btn-success">
                        <x-icon name="check-circle" size="16" />
                        <span>تأكيد تسجيل السداد</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Subscription -->
    <div id="editSubModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="calendar" size="18" />
                    <span>تعديل دورة الفاتورة واشتراك المشترك</span>
                </h3>
                <button class="modal-close" onclick="closeEditSubModal()">&times;</button>
            </div>
            <form action="{{ route('saas.tenants.update-subscription', $tenant->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>باقة الاشتراك</label>
                            <select name="plan_id" class="form-control">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ $tenant->plan_id == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} ({{ number_format($plan->price_monthly, 0) }} ج.م/ش)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>دورة الفاتورة *</label>
                            <select name="billing_cycle" class="form-control" required>
                                <option value="monthly" {{ $tenant->billing_cycle === 'monthly' ? 'selected' : '' }}>اشتراك شهري (30 يوم)</option>
                                <option value="quarterly" {{ $tenant->billing_cycle === 'quarterly' ? 'selected' : '' }}>اشتراك ربع سنوي (3 شهور)</option>
                                <option value="semi_annual" {{ $tenant->billing_cycle === 'semi_annual' ? 'selected' : '' }}>اشتراك نصف سنوي (6 شهور)</option>
                                <option value="yearly" {{ $tenant->billing_cycle === 'yearly' ? 'selected' : '' }}>اشتراك سنوي (12 شهر)</option>
                                <option value="trial" {{ $tenant->billing_cycle === 'trial' ? 'selected' : '' }}>فترة تجريبية (14 يوم)</option>
                                <option value="custom" {{ $tenant->billing_cycle === 'custom' ? 'selected' : '' }}>مخصص</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>تاريخ انتهاء الصلاحية * (قابل للتعديل)</label>
                            <input type="date" name="expires_at" class="form-control" required value="{{ $tenant->expires_at ? $tenant->expires_at->format('Y-m-d') : date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label>الحد الأقصى للمستخدمين *</label>
                            <input type="number" name="max_users" class="form-control" required min="1" value="{{ $tenant->max_users }}">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>سعر الاشتراك (ج.م)</label>
                            <input type="number" step="0.01" name="subscription_price" class="form-control" value="{{ $tenant->subscription_price }}">
                        </div>
                        <div class="form-group">
                            <label>حالة الاشتراك</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{ $tenant->is_active ? 'selected' : '' }}>نشط (ساري المفعول)</option>
                                <option value="0" {{ ! $tenant->is_active ? 'selected' : '' }}>معطل / مجمد</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2">{{ $tenant->notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditSubModal()">إلغاء</button>
                    <button type="submit" class="btn-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>حفظ التعديلات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function openPaymentModal() {
            document.getElementById('paymentModal').classList.add('active');
        }
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }

        function openEditSubModal() {
            document.getElementById('editSubModal').classList.add('active');
        }
        function closeEditSubModal() {
            document.getElementById('editSubModal').classList.remove('active');
        }

        window.onclick = function(e) {
            const pm = document.getElementById('paymentModal');
            const em = document.getElementById('editSubModal');
            if (e.target === pm) { closePaymentModal(); }
            if (e.target === em) { closeEditSubModal(); }
        }
    </script>
</body>
</html>
