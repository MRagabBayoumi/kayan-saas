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
            --odoo-bg: #f8f9fa;
            --odoo-card-bg: #ffffff;
            --odoo-border: #e2e8f0;
            --odoo-text-main: #1e293b;
            --odoo-text-muted: #64748b;
            --odoo-success: #10b981;
            --odoo-warning: #f59e0b;
            --odoo-danger: #ef4444;
            --odoo-info: #0284c7;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--odoo-bg);
            color: var(--odoo-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Odoo Top Main Navbar */
        .odoo-navbar {
            background-color: var(--odoo-primary);
            color: #ffffff;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .odoo-nav-start {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .odoo-app-switcher {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: rgba(255,255,255,0.12);
            cursor: pointer;
            transition: background 0.2s;
        }
        .odoo-app-switcher:hover {
            background: rgba(255,255,255,0.22);
        }
        .odoo-brand {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .odoo-brand .badge-saas {
            background: var(--odoo-secondary);
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
        }
        .odoo-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-right: 20px;
        }
        .odoo-nav-item {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s;
        }
        .odoo-nav-item:hover, .odoo-nav-item.active {
            color: #ffffff;
            background: rgba(255,255,255,0.15);
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
            background: rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Odoo Sub-Control Panel */
        .odoo-control-panel {
            background: #ffffff;
            border-bottom: 1px solid var(--odoo-border);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .odoo-cp-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--odoo-text-main);
        }
        .odoo-cp-title p {
            font-size: 12px;
            color: var(--odoo-text-muted);
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
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-odoo-primary:hover {
            background-color: var(--odoo-primary-dark);
        }
        .btn-odoo-secondary {
            background-color: white;
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
            transition: all 0.15s;
        }
        .btn-odoo-secondary:hover {
            background-color: #f1f5f9;
        }

        /* Main Container */
        .odoo-content {
            padding: 24px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        /* KPI Cards Grid (Odoo Metric Box) */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 8px;
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
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
        .kpi-card.warning::before { background: var(--odoo-warning); }
        .kpi-card.info::before { background: var(--odoo-info); }
        .kpi-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--odoo-text-muted);
            margin-bottom: 6px;
        }
        .kpi-val {
            font-size: 24px;
            font-weight: 800;
            color: var(--odoo-text-main);
            font-family: inherit;
        }
        .kpi-sub {
            font-size: 11px;
            color: var(--odoo-text-muted);
            margin-top: 4px;
        }

        /* Odoo Data Table */
        .odoo-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        .odoo-card-header {
            padding: 14px 20px;
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
        }
        .table-responsive {
            overflow-x: auto;
        }
        table.odoo-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 13px;
        }
        table.odoo-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 1px solid var(--odoo-border);
            white-space: nowrap;
        }
        table.odoo-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        table.odoo-table tr:hover {
            background-color: #fbfcfe;
        }

        /* Status & Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-plan { background: #f3e8ff; color: #7e22ce; }

        /* Toggle Buttons */
        .btn-toggle-mod {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-toggle-mod.active {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        .btn-toggle-mod.inactive {
            background: #f1f5f9;
            color: #94a3b8;
            border-color: #e2e8f0;
            text-decoration: line-through;
        }

        /* Modal styling (Odoo Dialog) */
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
            backdrop-filter: blur(2px);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 10px;
            width: 100%;
            max-width: 620px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 20px;
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
            font-size: 20px;
            cursor: pointer;
            color: var(--odoo-text-muted);
        }
        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--odoo-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8fafc;
        }
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--odoo-border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--odoo-primary);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
        }
        .alert {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecdd3; }
    </style>
</head>
<body>

    <!-- Odoo Enterprise Main Navbar -->
    <header class="odoo-navbar">
        <div class="odoo-nav-start">
            <div class="odoo-app-switcher" title="قائمة التطبيقات">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
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
                <x-icon name="check-circle" size="16" />
                <span>{{ session('message') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">
                <x-icon name="alert-triangle" size="16" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Odoo KPI Metrics -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(113, 75, 103, 0.1); color: var(--odoo-primary); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="building" size="24" />
                </div>
                <div class="kpi-label">إجمالي المشتركين</div>
                <div class="kpi-val">{{ $totalTenants }}</div>
                <div class="kpi-sub">شركات ومتاجر مسجلة</div>
            </div>
            <div class="kpi-card success">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--odoo-success); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="check-circle" size="24" />
                </div>
                <div class="kpi-label">الاشتراكات النشطة</div>
                <div class="kpi-val" style="color: var(--odoo-success);">{{ $activeTenants }}</div>
                <div class="kpi-sub">تراخيص سارية المفعول</div>
            </div>
            <div class="kpi-card info">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(2, 132, 199, 0.1); color: var(--odoo-info); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="trending-up" size="24" />
                </div>
                <div class="kpi-label">العائد الشهري التقديري (MRR)</div>
                <div class="kpi-val" style="color: var(--odoo-info);">{{ number_format($mrr, 0) }} <span style="font-size: 14px;">ج.م</span></div>
                <div class="kpi-sub">إيراد الاشتراكات الشهرية</div>
            </div>
            <div class="kpi-card warning">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(113, 75, 103, 0.1); color: var(--odoo-primary); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="database" size="24" />
                </div>
                <div class="kpi-label">عزل البيانات والأمان</div>
                <div class="kpi-val" style="font-size: 16px; color: var(--odoo-primary); padding-top: 6px;">100% DB-per-Tenant</div>
                <div class="kpi-sub">قاعدة بيانات فيزيائية لكل عميل</div>
            </div>
        </div>

        <!-- Tenants Table -->
        <div class="odoo-card">
            <div class="odoo-card-header">
                <h2>قائمة المشتركين الحالية والتحكم بالموديولات</h2>
                <span class="badge badge-plan">{{ $totalTenants }} مشترك مسجل</span>
            </div>
            <div class="table-responsive">
                <table class="odoo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الشركة / المشترك</th>
                            <th>الرابط المخصص (Subdomain)</th>
                            <th>قاعدة البيانات المستقلة</th>
                            <th>الباقة الحالية</th>
                            <th>حد المستخدمين</th>
                            <th>المحاسبة</th>
                            <th>المناديب</th>
                            <th>الأقساط</th>
                            <th>المتجر الإلكتروني</th>
                            <th>حالة الاشتراك</th>
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
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $t->name }}</strong>
                                    <div style="font-size: 11px; color: var(--odoo-text-muted);">
                                        تاريخ البدء: {{ $t->created_at->format('Y-m-d') }}
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ $tenantUrl }}" target="_blank" style="color: var(--odoo-primary); font-family: monospace; font-weight: 700; text-decoration: none;">
                                        {{ $t->subdomain }}.{{ $baseHost }} ↗
                                    </a>
                                </td>
                                <td>
                                    <code style="font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0f172a;">{{ $t->database_name }}</code>
                                </td>
                                <td>
                                    <span class="badge badge-plan">{{ $t->plan_name }}</span>
                                </td>
                                <td>
                                    <span style="font-weight: 700; font-family: monospace;">{{ $t->max_users ?? 3 }}</span> مستخدم
                                </td>

                                <!-- Accounting Toggle -->
                                <td>
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="accounting">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['accounting'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['accounting'] ?? false)
                                                <x-icon name="check" size="11" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="x" size="11" />
                                                <span>مغلق</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Sales Reps Toggle -->
                                <td>
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="sales_reps">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['sales_reps'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['sales_reps'] ?? false)
                                                <x-icon name="check" size="11" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="x" size="11" />
                                                <span>مغلق</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Installments Toggle -->
                                <td>
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="installments">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['installments'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['installments'] ?? false)
                                                <x-icon name="check" size="11" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="x" size="11" />
                                                <span>مغلق</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- E-Commerce Toggle -->
                                <td>
                                    <form action="{{ route('saas.tenants.toggle-module', $t->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="module" value="ecommerce">
                                        <button type="submit" class="btn-toggle-mod {{ ($mods['ecommerce'] ?? false) ? 'active' : 'inactive' }}" title="اضغط لتغيير الحالة">
                                            @if($mods['ecommerce'] ?? false)
                                                <x-icon name="check" size="11" />
                                                <span>مفعل</span>
                                            @else
                                                <x-icon name="x" size="11" />
                                                <span>مغلق</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <!-- Status / Expiry -->
                                <td>
                                    @if(! $t->is_active)
                                        <span class="badge badge-danger">
                                            <x-icon name="shield" size="12" />
                                            <span>مجمد</span>
                                        </span>
                                    @elseif($t->isExpired())
                                        <span class="badge badge-danger">
                                            <x-icon name="alert-triangle" size="12" />
                                            <span>منتهي الصلاحية</span>
                                        </span>
                                    @else
                                        <span class="badge badge-success">
                                            <x-icon name="check-circle" size="12" />
                                            <span>نشط</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="{{ $tenantUrl }}" target="_blank" class="btn-odoo-primary" style="padding: 5px 12px; font-size: 11px;">
                                        <x-icon name="external-link" size="13" />
                                        <span>فتح النظام</span>
                                    </a>
                                    <form action="{{ route('saas.tenants.toggle-status', $t->id) }}" method="POST" style="display:inline; margin-right: 4px;">
                                        @csrf
                                        <button type="submit" class="btn-odoo-secondary" style="padding: 5px 10px; font-size: 11px;" title="{{ $t->is_active ? 'إيقاف مؤقت' : 'إعادة تنشيط' }}">
                                            @if($t->is_active)
                                                <x-icon name="shield" size="12" />
                                                <span>تجميد</span>
                                            @else
                                                <x-icon name="zap" size="12" />
                                                <span>تنشيط</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 40px; color: var(--odoo-text-muted);">
                                    لا يوجد مشتركون حالياً. استخدم زر "إضافة مشترك جديد" لإنشاء أول شركة.
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
                        <span style="font-size: 11px; color: var(--odoo-text-muted);">أحرف وأرقام إنجليزية وشرطات فقط</span>
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

                    <div class="form-group">
                        <label>دورة الفاتورة *</label>
                        <select name="billing_cycle" class="form-control" required>
                            <option value="monthly">اشتراك شهري</option>
                            <option value="yearly">اشتراك سنوي (خصم شهرين مجاناً)</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>البريد الإلكتروني للمدير *</label>
                            <input type="email" name="admin_email" class="form-control" required placeholder="admin@client.com">
                        </div>
                        <div class="form-group">
                            <label>كلمة المرور لحساب المدير *</label>
                            <input type="text" name="admin_password" class="form-control" required value="admin123">
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

    <script>
        function openNewTenantModal() {
            document.getElementById('newTenantModal').classList.add('active');
        }
        function closeNewTenantModal() {
            document.getElementById('newTenantModal').classList.remove('active');
        }
        window.onclick = function(e) {
            const m = document.getElementById('newTenantModal');
            if (e.target === m) { closeNewTenantModal(); }
        }
    </script>
</body>
</html>
