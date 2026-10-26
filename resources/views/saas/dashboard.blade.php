<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة إدارة المشتركين السحابية — Kayan SaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --odoo-primary: #714B67;
            --odoo-primary-dark: #56394e;
            --odoo-primary-light: #8e6283;
            --odoo-secondary: #00A09D;
            --odoo-secondary-dark: #008784;
            --odoo-bg: #f8fafc;
            --odoo-card-bg: #ffffff;
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
            font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--odoo-bg);
            color: var(--odoo-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Odoo Top Main Navbar */
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
        .odoo-nav-start {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .odoo-app-switcher {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .odoo-app-switcher:hover {
            background: rgba(255,255,255,0.22);
        }
        .odoo-brand {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .odoo-brand .badge-saas {
            background: var(--odoo-secondary);
            color: #ffffff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .odoo-nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-right: 24px;
        }
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
            transition: all 0.15s ease;
        }
        .odoo-nav-item:hover, .odoo-nav-item.active {
            color: #ffffff;
            background: rgba(255,255,255,0.16);
        }
        .odoo-nav-end {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .odoo-user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
        }

        /* Odoo Sub-Control Panel */
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
            font-size: 21px;
            font-weight: 800;
            color: var(--odoo-text-main);
            letter-spacing: -0.3px;
        }
        .odoo-cp-title p {
            font-size: 12px;
            color: var(--odoo-text-muted);
            margin-top: 2px;
        }
        .odoo-cp-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-odoo-primary {
            background-color: var(--odoo-primary);
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(113, 75, 103, 0.25);
            transition: all 0.15s ease;
        }
        .btn-odoo-primary:hover {
            background-color: var(--odoo-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(113, 75, 103, 0.35);
        }
        .btn-odoo-secondary {
            background-color: #ffffff;
            color: var(--odoo-text-main);
            border: 1px solid var(--odoo-border);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-odoo-secondary:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        /* Main Container */
        .odoo-content {
            padding: 24px 28px;
            max-width: 1650px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        /* Alert Banners */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecdd3;
        }

        /* Modern KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 10px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }
        .kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: var(--odoo-primary);
        }
        .kpi-card.success::before { background: var(--odoo-success); }
        .kpi-card.info::before { background: var(--odoo-info); }
        .kpi-card.warning::before { background: var(--odoo-warning); }

        .kpi-info {
            flex: 1;
            min-width: 0;
        }
        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--odoo-text-muted);
            margin-bottom: 4px;
        }
        .kpi-val {
            font-size: 26px;
            font-weight: 800;
            color: var(--odoo-text-main);
            line-height: 1.2;
            letter-spacing: -0.5px;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .kpi-val-unit {
            font-size: 14px;
            font-weight: 700;
            color: var(--odoo-text-muted);
        }
        .kpi-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .kpi-icon-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kpi-icon-purple {
            background: rgba(113, 75, 103, 0.1);
            color: var(--odoo-primary);
        }
        .kpi-icon-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--odoo-success);
        }
        .kpi-icon-info {
            background: rgba(2, 132, 199, 0.1);
            color: var(--odoo-info);
        }
        .kpi-icon-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--odoo-warning);
        }

        /* Odoo Card & Table Container */
        .odoo-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        .odoo-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--odoo-border);
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .odoo-card-header h2 {
            font-size: 15px;
            font-weight: 800;
            color: var(--odoo-text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        table.odoo-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1250px;
            text-align: right;
            font-size: 13px;
        }
        table.odoo-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            padding: 12px 14px;
            border-bottom: 2px solid var(--odoo-border);
            white-space: nowrap;
            letter-spacing: -0.2px;
        }
        table.odoo-table td {
            padding: 14px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 13px;
        }
        table.odoo-table tbody tr:hover td {
            background-color: #fbfcfe;
        }

        /* Pill and Status Styling */
        .company-meta {
            min-width: 220px;
        }
        .company-name {
            font-weight: 800;
            font-size: 14px;
            color: var(--odoo-text-main);
            line-height: 1.3;
        }
        .company-date {
            font-size: 11px;
            color: var(--odoo-text-muted);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .subdomain-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            color: var(--odoo-primary);
            border: 1px solid var(--odoo-border);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            direction: ltr;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .subdomain-link:hover {
            background: #f1f5f9;
            border-color: var(--odoo-primary-light);
            color: var(--odoo-primary-dark);
        }

        .db-code {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid var(--odoo-border);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 600;
            direction: ltr;
            white-space: nowrap;
        }

        .badge-plan {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            background: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .users-limit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            color: #334155;
            font-size: 12px;
        }
        .users-limit strong {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Modern Module Toggle Buttons */
        .btn-toggle-mod {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            background: none;
        }
        .btn-toggle-mod.active {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
            box-shadow: 0 1px 2px rgba(4, 120, 87, 0.06);
        }
        .btn-toggle-mod.active:hover {
            background: #d1fae5;
            border-color: #6ee7b7;
        }
        .btn-toggle-mod.inactive {
            background: #f8fafc;
            color: #94a3b8;
            border-color: #e2e8f0;
        }
        .btn-toggle-mod.inactive:hover {
            background: #f1f5f9;
            color: #64748b;
            border-color: #cbd5e1;
        }

        /* Subscription Status Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-status.active {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-status.frozen {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecdd3;
        }
        .badge-status.expired {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        /* Action Buttons */
        .actions-cell {
            white-space: nowrap;
            text-align: center;
        }
        .btn-action-primary {
            background-color: var(--odoo-primary);
            color: white;
            border: none;
            padding: 6px 13px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(113, 75, 103, 0.15);
            transition: all 0.15s ease;
        }
        .btn-action-primary:hover {
            background-color: var(--odoo-primary-dark);
            transform: translateY(-1px);
        }
        .btn-action-secondary {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid var(--odoo-border);
            padding: 6px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .btn-action-secondary:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(3px);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 620px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--odoo-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        .modal-header h3 { font-size: 16px; font-weight: 800; color: var(--odoo-primary); }
        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--odoo-text-muted);
            line-height: 1;
        }
        .modal-body {
            padding: 22px;
            overflow-y: auto;
            flex: 1;
        }
        .modal-footer {
            padding: 14px 22px;
            border-top: 1px solid var(--odoo-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8fafc;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--odoo-border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
            color: var(--odoo-text-main);
            background: #ffffff;
            transition: all 0.15s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--odoo-primary);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
        }
    </style>
</head>
<body>

    <!-- Odoo Enterprise Main Navbar -->
    <header class="odoo-navbar">
        <div class="odoo-nav-start">
            <div class="odoo-app-switcher" title="قائمة التطبيقات">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
            </div>
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
        <div class="odoo-nav-end">
            <div class="odoo-user-pill">
                <x-icon name="shield" size="15" />
                <span>مدير المنصة المركزية</span>
            </div>
        </div>
    </header>

    <!-- Odoo Control Panel -->
    <section class="odoo-control-panel">
        <div class="odoo-cp-title">
            <h1>لوحة إدارة المشتركين السحابية</h1>
            <p>إدارة الشركات، تخصيص الموديولات، وعزل قواعد البيانات بنظام Multi-Tenant</p>
        </div>
        <div class="odoo-cp-actions">
            <button class="btn-odoo-primary" onclick="openNewTenantModal()">
                <x-icon name="plus" size="15" />
                <span>إضافة مشترك جديد</span>
            </button>
            <a href="{{ route('saas.plans.index') }}" class="btn-odoo-secondary">
                <x-icon name="tag" size="14" />
                <span>إدارة الباقات</span>
            </a>
            <a href="{{ route('saas.subscriptions.index') }}" class="btn-odoo-secondary">
                <x-icon name="credit-card" size="14" />
                <span>تجديد الاشتراكات</span>
            </a>
        </div>
    </section>

    <!-- Main Content -->
    <main class="odoo-content">

        @if(session('message'))
            <div class="alert alert-success">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Odoo KPI Metrics -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info">
                    <div class="kpi-label">إجمالي المشتركين</div>
                    <div class="kpi-val">{{ $totalTenants }}</div>
                    <div class="kpi-sub">شركات ومتاجر مسجلة</div>
                </div>
                <div class="kpi-icon-box kpi-icon-purple">
                    <x-icon name="building" size="24" />
                </div>
            </div>

            <div class="kpi-card success">
                <div class="kpi-info">
                    <div class="kpi-label">الاشتراكات النشطة</div>
                    <div class="kpi-val" style="color: var(--odoo-success);">{{ $activeTenants }}</div>
                    <div class="kpi-sub">تراخيص سارية المفعول</div>
                </div>
                <div class="kpi-icon-box kpi-icon-success">
                    <x-icon name="check-circle" size="24" />
                </div>
            </div>

            <div class="kpi-card info">
                <div class="kpi-info">
                    <div class="kpi-label">العائد الشهري التقديري (MRR)</div>
                    <div class="kpi-val" style="color: var(--odoo-info);">
                        {{ number_format($mrr, 0) }}
                        <span class="kpi-val-unit">ج.م</span>
                    </div>
                    <div class="kpi-sub">إيراد الاشتراكات الشهرية</div>
                </div>
                <div class="kpi-icon-box kpi-icon-info">
                    <x-icon name="trending-up" size="24" />
                </div>
            </div>

            <div class="kpi-card warning">
                <div class="kpi-info">
                    <div class="kpi-label">عزل البيانات والأمان</div>
                    <div class="kpi-val" style="color: var(--odoo-primary);">
                        100%
                        <span class="kpi-val-unit">DB Isolated</span>
                    </div>
                    <div class="kpi-sub">قاعدة بيانات فيزيائية لكل مشترك</div>
                </div>
                <div class="kpi-icon-box kpi-icon-purple">
                    <x-icon name="database" size="24" />
                </div>
            </div>
        </div>

        <!-- Tenants Table -->
        <div class="odoo-card">
            <div class="odoo-card-header">
                <h2>
                    <x-icon name="users" size="18" />
                    <span>قائمة المشتركين الحالية والتحكم بالموديولات</span>
                </h2>
                <span class="badge-plan">{{ $totalTenants }} مشترك مسجل</span>
            </div>
            <div class="table-responsive">
                <table class="odoo-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">#</th>
                            <th>الشركة / المشترك</th>
                            <th>الرابط المخصص (Subdomain)</th>
                            <th>قاعدة البيانات المستقلة</th>
                            <th>الباقة الحالية</th>
                            <th>حد المستخدمين</th>
                            <th style="text-align: center;">المحاسبة</th>
                            <th style="text-align: center;">المناديب</th>
                            <th style="text-align: center;">الأقساط</th>
                            <th style="text-align: center;">المتجر الإلكتروني</th>
                            <th style="text-align: center;">حالة الاشتراك</th>
                            <th style="text-align: center;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $index => $t)
                            @php
                                $mods = $t->modules ?? [];
                                $host = request()->getHost();
                                $baseHost = str_replace(['www.', 'saas.'], '', $host);
                                if ($baseHost === 'localhost' || $baseHost === '127.0.0.1') {
                                    $baseHost = 'lvh.me';
                                }
                                $port = request()->getPort();
                                $portSuffix = ($port && !in_array($port, [80, 443])) ? ':' . $port : '';
                                $tenantUrl = request()->getScheme() . '://' . $t->subdomain . '.' . $baseHost . $portSuffix;
                            @endphp
                            <tr>
                                <td style="text-align: center; color: #94a3b8; font-weight: 700;">{{ $index + 1 }}</td>
                                <td>
                                    <div class="company-meta">
                                        <a href="{{ route('saas.tenants.show', $t) }}" class="company-name" style="text-decoration: none; color: var(--odoo-primary); display: inline-flex; align-items: center; gap: 5px;" title="عرض الملف الكامل وسجل السدادات">
                                            <span>{{ $t->name }}</span>
                                            <x-icon name="arrow-left" size="12" />
                                        </a>
                                        <div class="company-date">
                                            <x-icon name="calendar" size="12" />
                                            <span>تاريخ البدء: {{ $t->created_at->format('Y-m-d') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ $tenantUrl }}" target="_blank" class="subdomain-link" title="فتح الرابط في تبويب جديد">
                                        <span>{{ $t->subdomain }}.{{ $baseHost }}{{ $portSuffix }}</span>
                                        <x-icon name="external-link" size="12" />
                                    </a>
                                </td>
                                <td>
                                    <div class="db-code">
                                        <x-icon name="database" size="12" />
                                        <span>{{ $t->database_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-plan">{{ $t->plan_name }}</span>
                                </td>
                                <td>
                                    <div class="users-limit">
                                        <x-icon name="users" size="14" />
                                        <strong>{{ $t->max_users ?? 3 }}</strong>
                                        <span>مستخدمين</span>
                                    </div>
                                </td>

                                <!-- Accounting Toggle -->
                                <td style="text-align: center;">
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="accounting">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['accounting'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['accounting'] ?? false)
                                                <x-icon name="check" size="12" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="minus" size="12" />
                                                <span>معطل</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Sales Reps Toggle -->
                                <td style="text-align: center;">
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="sales_reps">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['sales_reps'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['sales_reps'] ?? false)
                                                <x-icon name="check" size="12" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="minus" size="12" />
                                                <span>معطل</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Installments Toggle -->
                                <td style="text-align: center;">
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="installments">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['installments'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['installments'] ?? false)
                                                <x-icon name="check" size="12" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="minus" size="12" />
                                                <span>معطل</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- E-Commerce Toggle -->
                                <td style="text-align: center;">
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="ecommerce">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['ecommerce'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['ecommerce'] ?? false)
                                                <x-icon name="check" size="12" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="minus" size="12" />
                                                <span>معطل</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Status / Expiry -->
                                <td style="text-align: center;">
                                    @if(! $t->is_active)
                                        <span class="badge-status frozen">
                                            <x-icon name="shield" size="12" />
                                            <span>مجمد</span>
                                        </span>
                                    @elseif($t->isExpired())
                                        <span class="badge-status expired">
                                            <x-icon name="alert-triangle" size="12" />
                                            <span>منتهي الصلاحية</span>
                                        </span>
                                    @else
                                        <span class="badge-status active">
                                            <x-icon name="check-circle" size="12" />
                                            <span>نشط</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="actions-cell">
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <a href="{{ route('saas.tenants.show', $t) }}" class="btn-action-secondary" style="color: var(--odoo-primary); font-weight: 700; background: #faf5ff; border-color: #e9d5ff;" title="عرض الملف الكامل وسجل السدادات والتفاصيل">
                                            <x-icon name="user" size="13" />
                                            <span>ملف المشترك</span>
                                        </a>
                                        <a href="{{ $tenantUrl }}" target="_blank" class="btn-action-primary" title="فتح لوحة تحكم ERP الخاصة بالمشترك">
                                            <x-icon name="external-link" size="13" />
                                            <span>فتح النظام</span>
                                        </a>
                                        <button type="button" class="btn-action-secondary" 
                                                onclick="openEditSubscriptionModal({{ json_encode([
                                                    'id' => $t->id,
                                                    'name' => $t->name,
                                                    'subdomain' => $t->subdomain,
                                                    'plan_id' => $t->plan_id,
                                                    'billing_cycle' => $t->billing_cycle,
                                                    'max_users' => $t->max_users,
                                                    'subscription_price' => $t->subscription_price,
                                                    'expires_at' => $t->expires_at ? $t->expires_at->format('Y-m-d') : '',
                                                    'is_active' => (bool)$t->is_active,
                                                    'notes' => $t->notes,
                                                ]) }})" 
                                                title="تعديل دورة الفاتورة وتاريخ الصلاحية والباقة">
                                            <x-icon name="credit-card" size="13" />
                                            <span>تعديل الاشتراك</span>
                                        </button>
                                        <form action="{{ route('saas.tenants.toggle-status', $t->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn-action-secondary" title="{{ $t->is_active ? 'إيقاف مؤقت' : 'إعادة تنشيط' }}">
                                                @if($t->is_active)
                                                    <x-icon name="shield" size="13" />
                                                    <span>تجميد</span>
                                                @else
                                                    <x-icon name="zap" size="13" />
                                                    <span>تنشيط</span>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 48px; color: var(--odoo-text-muted);">
                                    <x-icon name="inbox" size="32" style="opacity: 0.5; margin-bottom: 8px;" />
                                    <div>لا يوجد مشتركون حالياً. استخدم زر "إضافة مشترك جديد" لإنشاء أول شركة.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal: New Tenant Creation (Odoo Style) -->
    <div id="newTenantModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="building" size="18" />
                    <span>تسجيل شركة مشتركة جديدة (SaaS Onboarding)</span>
                </h3>
                <button class="modal-close" onclick="closeNewTenantModal()">&times;</button>
            </div>
            <form action="{{ route('saas.tenants.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>اسم الشركة أو المحل التجاري *</label>
                        <input type="text" name="name" class="form-control" required placeholder="مثال: شركة البركة للمستلزمات">
                    </div>

                    <div class="form-group">
                        <label>الدومين الفرعي المخصص (Subdomain) *</label>
                        <div style="display: flex; align-items: center; gap: 6px; direction: ltr;">
                            <input type="text" name="subdomain" class="form-control" required placeholder="albaraka" pattern="[a-z0-9\-]+" style="text-align: left; font-family: monospace;">
                            <span style="font-size: 13px; color: var(--odoo-text-muted); font-weight: 700;">.lvh.me:8080</span>
                        </div>
                        <span style="font-size: 11px; color: var(--odoo-text-muted); margin-top: 3px; display: block;">أحرف وأرقام إنجليزية وشرطات فقط</span>
                    </div>

                    <div class="form-group">
                        <label>اختيار باقة الاشتراك *</label>
                        <select name="plan_id" class="form-control" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} — {{ number_format($plan->price_monthly, 0) }} ج.م/شهرياً (حد أقصى {{ $plan->max_users }} مستخدمين)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Flexible Billing Cycle & Expiry Date -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>دورة الفاتورة *</label>
                            <select name="billing_cycle" id="newTenantBillingCycle" class="form-control" required onchange="onNewBillingCycleChange(this.value)">
                                <option value="monthly">اشتراك شهري (30 يوم)</option>
                                <option value="quarterly">اشتراك ربع سنوي (3 شهور)</option>
                                <option value="semi_annual">اشتراك نصف سنوي (6 شهور)</option>
                                <option value="yearly">اشتراك سنوي (12 شهر - خصم شهرين)</option>
                                <option value="trial">فترة تجريبية (14 يوم)</option>
                                <option value="custom">تاريخ مخصص...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>تاريخ انتهاء الاشتراك (قابل للتعديل)</label>
                            <input type="date" name="custom_expires_at" id="newTenantExpiresAt" class="form-control" value="{{ now()->addDays(30)->format('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- Admin User Credentials Section -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 15px;">
                        <div style="font-size: 13px; font-weight: 800; color: var(--odoo-primary); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <x-icon name="user-check" size="16" />
                            <span>بيانات دخول المدير إلى النظام (ERP Login Credentials)</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>اسم المسؤول / المدير</label>
                                <input type="text" name="admin_name" class="form-control" placeholder="مثال: مدير النظام" value="مدير النظام">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="color: var(--odoo-primary); font-weight: 800;">اسم المستخدم لتسجيل الدخول (Username) *</label>
                                <input type="text" name="admin_username" class="form-control" required value="admin" style="direction: ltr; font-family: monospace; font-weight: 700;">
                                <span style="font-size: 10px; color: var(--odoo-text-muted); margin-top: 2px; display: block;">يُستخدم للدخول إلى لوحة ERP</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>البريد الإلكتروني للمدير *</label>
                                <input type="email" name="admin_email" class="form-control" required placeholder="admin@client.com">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>كلمة المرور لحساب المدير *</label>
                                <input type="text" name="admin_password" class="form-control" required value="admin123" style="direction: ltr; font-family: monospace;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="أي تفاصيل خاصة بالعميل أو الاتفاق"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeNewTenantModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>إنشاء قاعدة البيانات وتفعيل المشترك</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Subscription / Billing Cycle -->
    <div id="editSubscriptionModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="credit-card" size="18" />
                    <span>تعديل دورة الفاتورة واشتراك المشترك</span>
                </h3>
                <button class="modal-close" onclick="closeEditSubscriptionModal()">&times;</button>
            </div>
            <form id="editSubscriptionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; color: var(--odoo-text-muted);">الشركة / المشترك:</span>
                            <strong id="editSubTenantName" style="font-size: 14px; color: var(--odoo-text-main); margin-right: 6px;"></strong>
                        </div>
                        <span id="editSubTenantSubdomain" class="subdomain-link" style="font-size: 11px;"></span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>باقة الاشتراك</label>
                            <select name="plan_id" id="editSubPlanId" class="form-control">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ number_format($plan->price_monthly, 0) }} ج.م/ش)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>دورة الفاتورة *</label>
                            <select name="billing_cycle" id="editSubBillingCycle" class="form-control" required onchange="onEditBillingCycleChange(this.value)">
                                <option value="monthly">اشتراك شهري (30 يوم)</option>
                                <option value="quarterly">اشتراك ربع سنوي (3 شهور)</option>
                                <option value="semi_annual">اشتراك نصف سنوي (6 شهور)</option>
                                <option value="yearly">اشتراك سنوي (12 شهر)</option>
                                <option value="trial">فترة تجريبية (14 يوم)</option>
                                <option value="custom">مخصص</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>تاريخ انتهاء الصلاحية * (قابل للتعديل)</label>
                            <input type="date" name="expires_at" id="editSubExpiresAt" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>الحد الأقصى للمستخدمين *</label>
                            <input type="number" name="max_users" id="editSubMaxUsers" class="form-control" required min="1" max="1000">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>سعر الاشتراك (ج.م)</label>
                            <input type="number" step="0.01" name="subscription_price" id="editSubPrice" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>حالة الاشتراك</label>
                            <select name="is_active" id="editSubIsActive" class="form-control">
                                <option value="1">نشط (ساري المفعول)</option>
                                <option value="0">معطل / مجمد</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية</label>
                        <textarea name="notes" id="editSubNotes" class="form-control" rows="2" placeholder="ملاحظات حول تجديد العميل أو الاتفاق"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeEditSubscriptionModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>حفظ تعديلات الاشتراك</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function calculateTargetDate(cycle) {
            const now = new Date();
            if (cycle === 'monthly') {
                now.setDate(now.getDate() + 30);
            } else if (cycle === 'quarterly') {
                now.setMonth(now.getMonth() + 3);
            } else if (cycle === 'semi_annual') {
                now.setMonth(now.getMonth() + 6);
            } else if (cycle === 'yearly') {
                now.setFullYear(now.getFullYear() + 1);
            } else if (cycle === 'trial') {
                now.setDate(now.getDate() + 14);
            }
            return now.toISOString().split('T')[0];
        }

        function onNewBillingCycleChange(cycle) {
            if (cycle !== 'custom') {
                document.getElementById('newTenantExpiresAt').value = calculateTargetDate(cycle);
            }
        }

        function onEditBillingCycleChange(cycle) {
            if (cycle !== 'custom') {
                document.getElementById('editSubExpiresAt').value = calculateTargetDate(cycle);
            }
        }

        function openNewTenantModal() {
            document.getElementById('newTenantModal').classList.add('active');
        }
        function closeNewTenantModal() {
            document.getElementById('newTenantModal').classList.remove('active');
        }

        function openEditSubscriptionModal(data) {
            document.getElementById('editSubscriptionForm').action = '/saas/tenants/' + data.id + '/update-subscription';
            document.getElementById('editSubTenantName').textContent = data.name;
            document.getElementById('editSubTenantSubdomain').textContent = data.subdomain + '.lvh.me:8080';
            
            if (data.plan_id) {
                document.getElementById('editSubPlanId').value = data.plan_id;
            }
            document.getElementById('editSubBillingCycle').value = data.billing_cycle || 'monthly';
            document.getElementById('editSubExpiresAt').value = data.expires_at || '';
            document.getElementById('editSubMaxUsers').value = data.max_users || 3;
            document.getElementById('editSubPrice').value = data.subscription_price || 0;
            document.getElementById('editSubIsActive').value = data.is_active ? '1' : '0';
            document.getElementById('editSubNotes').value = data.notes || '';

            document.getElementById('editSubscriptionModal').classList.add('active');
        }

        function closeEditSubscriptionModal() {
            document.getElementById('editSubscriptionModal').classList.remove('active');
        }

        window.onclick = function(e) {
            const newM = document.getElementById('newTenantModal');
            const editM = document.getElementById('editSubscriptionModal');
            if (e.target === newM) { closeNewTenantModal(); }
            if (e.target === editM) { closeEditSubscriptionModal(); }
        }
    </script>
</body>
</html>
