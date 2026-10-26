<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاشتراكات والتجديدات — Kayan SaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --odoo-primary: #714B67;
            --odoo-primary-dark: #56394e;
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
        .odoo-nav-start { display: flex; align-items: center; gap: 16px; }
        .odoo-app-switcher {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px; background: rgba(255,255,255,0.12);
            cursor: pointer;
        }
        .odoo-brand {
            font-size: 16px; font-weight: 800;
            display: flex; align-items: center; gap: 8px;
        }
        .odoo-brand .badge-saas {
            background: var(--odoo-secondary);
            font-size: 11px; padding: 2px 8px;
            border-radius: 4px; font-weight: 700;
        }
        .odoo-nav-links { display: flex; align-items: center; gap: 4px; margin-right: 20px; }
        .odoo-nav-item {
            color: rgba(255,255,255,0.85); text-decoration: none;
            padding: 6px 12px; border-radius: 6px;
            font-size: 13px; font-weight: 600;
            transition: all 0.15s;
        }
        .odoo-nav-item:hover, .odoo-nav-item.active {
            color: #ffffff; background: rgba(255,255,255,0.15);
        }
        .odoo-user-pill {
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.1); padding: 4px 10px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
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
        .odoo-cp-title h1 { font-size: 20px; font-weight: 800; }
        .odoo-cp-title p { font-size: 12px; color: var(--odoo-text-muted); }
        .btn-odoo-primary {
            background-color: var(--odoo-primary); color: white;
            border: none; padding: 8px 16px; border-radius: 6px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
        }
        .btn-odoo-primary:hover { background-color: var(--odoo-primary-dark); }
        .btn-odoo-secondary {
            background-color: white; color: var(--odoo-text-main);
            border: 1px solid var(--odoo-border); padding: 8px 14px;
            border-radius: 6px; font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none;
        }
        .btn-odoo-secondary:hover { background-color: #f1f5f9; }

        .odoo-content {
            padding: 24px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

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
            border-radius: 8px;
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .kpi-card::before {
            content: ''; position: absolute;
            top: 0; right: 0; width: 4px; height: 100%;
            background: var(--odoo-primary);
        }
        .kpi-card.success::before { background: var(--odoo-success); }
        .kpi-card.warning::before { background: var(--odoo-warning); }
        .kpi-label { font-size: 12px; font-weight: 600; color: var(--odoo-text-muted); margin-bottom: 4px; }
        .kpi-val { font-size: 24px; font-weight: 800; color: var(--odoo-text-main); }
        .kpi-sub { font-size: 11px; color: var(--odoo-text-muted); margin-top: 4px; }

        /* Odoo Table */
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
        .odoo-card-header h2 { font-size: 15px; font-weight: 800; }
        .table-responsive { overflow-x: auto; }
        table.odoo-table {
            width: 100%; border-collapse: collapse; text-align: right; font-size: 13px;
        }
        table.odoo-table th {
            background-color: #f8fafc; color: #475569; font-weight: 700;
            padding: 12px 16px; border-bottom: 1px solid var(--odoo-border);
            white-space: nowrap;
        }
        table.odoo-table td {
            padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
        }
        table.odoo-table tr:hover { background-color: #fbfcfe; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-plan { background: #f3e8ff; color: #7e22ce; }

        /* Modal styling */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); display: none;
            align-items: center; justify-content: center; z-index: 100;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #ffffff; border-radius: 10px; width: 100%;
            max-width: 520px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .modal-header {
            padding: 16px 20px; border-bottom: 1px solid var(--odoo-border);
            display: flex; justify-content: space-between; align-items: center; background: #f8fafc;
        }
        .modal-header h3 { font-size: 16px; font-weight: 800; color: var(--odoo-primary); }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--odoo-text-muted); }
        .modal-body { padding: 20px; overflow-y: auto; }
        .modal-footer {
            padding: 14px 20px; border-top: 1px solid var(--odoo-border);
            display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;
        }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
        .form-control {
            width: 100%; padding: 8px 12px; border: 1px solid var(--odoo-border);
            border-radius: 6px; font-family: inherit; font-size: 13px;
        }
        .alert {
            padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; margin-bottom: 16px;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

    <!-- Odoo Enterprise Main Navbar -->
    <header class="odoo-navbar">
        <div class="odoo-nav-start">
            <div class="odoo-app-switcher">
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
                <a href="{{ route('saas.index') }}" class="odoo-nav-item">
                    <x-icon name="layout-dashboard" size="15" />
                    <span>لوحة التحكم</span>
                </a>
                <a href="{{ route('saas.plans.index') }}" class="odoo-nav-item">
                    <x-icon name="tag" size="15" />
                    <span>الباقات والأسعار</span>
                </a>
                <a href="{{ route('saas.subscriptions.index') }}" class="odoo-nav-item active">
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

    <!-- Odoo Sub-Control Panel -->
    <section class="odoo-control-panel">
        <div class="odoo-cp-title">
            <h1>إدارة الاشتراكات والتجديدات (Subscriptions & Billing Lifecycle)</h1>
            <p>متابعة تواريخ انتهاء الاشتراكات، تمديد التراخيص، وترقية أو تخفيض الباقات</p>
        </div>
        <div class="odoo-cp-actions">
            <a href="{{ route('saas.plans.index') }}" class="btn-odoo-secondary">
                <x-icon name="tag" size="14" />
                <span>مراجعة الباقات</span>
            </a>
            <a href="{{ route('saas.index') }}" class="btn-odoo-secondary">
                <x-icon name="arrow-right" size="14" />
                <span>العودة للوحة التحكم</span>
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

        <!-- KPI Metrics -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(113, 75, 103, 0.1); color: var(--odoo-primary); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="credit-card" size="24" />
                </div>
                <div class="kpi-label">إجمالي الاشتراكات</div>
                <div class="kpi-val">{{ $totalSubscriptions }}</div>
                <div class="kpi-sub">تراخيص في النظام</div>
            </div>
            <div class="kpi-card success">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--odoo-success); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="check-circle" size="24" />
                </div>
                <div class="kpi-label">اشتراكات سارية</div>
                <div class="kpi-val" style="color: var(--odoo-success);">{{ $activeSubscriptions }}</div>
                <div class="kpi-sub">تعمل بصلاحية سارية</div>
            </div>
            <div class="kpi-card warning">
                <div style="float: left; width: 44px; height: 44px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: var(--odoo-warning); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="calendar" size="24" />
                </div>
                <div class="kpi-label">تنتهي خلال 7 أيام</div>
                <div class="kpi-val" style="color: var(--odoo-warning);">{{ $expiringSoon }}</div>
                <div class="kpi-sub">تتطلب المتابعة للتجديد</div>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <div class="odoo-card">
            <div class="odoo-card-header">
                <h2>قائمة اشتراكات المشتركين</h2>
                <span class="badge badge-plan">تجديد فوري بنقرة زر</span>
            </div>
            <div class="table-responsive">
                <table class="odoo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الشركة / المشترك</th>
                            <th>الباقة الحالية</th>
                            <th>دورة الفاتورة</th>
                            <th>القيمة المسجلة</th>
                            <th>تاريخ البدء</th>
                            <th>تاريخ الانتهاء</th>
                            <th>المدة المتبقية</th>
                            <th>الحالة</th>
                            <th style="text-align: center;">إدارة الاشتراك</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $index => $t)
                            @php
                                $days = $t->daysRemaining();
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $t->name }}</strong>
                                    <div style="font-size: 11px; color: var(--odoo-text-muted); font-family: monospace;">
                                        {{ $t->subdomain }}.lvh.me:8080
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-plan">{{ $t->plan_name }}</span>
                                    <div style="font-size: 11px; color: var(--odoo-text-muted);">
                                        حد: {{ $t->max_users }} مستخدمين
                                    </div>
                                </td>
                                <td>
                                    {{ $t->billing_cycle === 'yearly' ? 'سنوي (12 شهر)' : 'شهري' }}
                                </td>
                                <td>
                                    <strong>{{ number_format($t->subscription_price ?: 0, 0) }}</strong> ج.م
                                </td>
                                <td>
                                    {{ $t->starts_at ? $t->starts_at->format('Y-m-d') : $t->created_at->format('Y-m-d') }}
                                </td>
                                <td>
                                    {{ $t->expires_at ? $t->expires_at->format('Y-m-d') : 'مفتوح (غير محدد)' }}
                                </td>
                                <td>
                                    @if($t->isExpired())
                                        <span class="badge badge-danger">
                                            <x-icon name="alert-triangle" size="12" />
                                            <span>منتهي الصلاحية</span>
                                        </span>
                                    @elseif($days !== null)
                                        @if($days <= 7)
                                            <span class="badge badge-warning">
                                                <x-icon name="calendar" size="12" />
                                                <span>متبقي {{ $days }} يوم</span>
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <x-icon name="check-circle" size="12" />
                                                <span>متبقي {{ $days }} يوم</span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-success">
                                            <x-icon name="check-circle" size="12" />
                                            <span>دائم</span>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if(! $t->is_active)
                                        <span class="badge badge-danger">
                                            <x-icon name="shield" size="12" />
                                            <span>مجمد</span>
                                        </span>
                                    @else
                                        <span class="badge badge-success">
                                            <x-icon name="check-circle" size="12" />
                                            <span>نشط</span>
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <button class="btn-odoo-primary" onclick="openRenewModal({{ json_encode($t) }})" style="padding: 5px 12px; font-size: 11px;">
                                        <x-icon name="refresh-cw" size="12" />
                                        <span>تجديد الاشتراك</span>
                                    </button>
                                    <button class="btn-odoo-secondary" onclick="openChangePlanModal({{ json_encode($t) }})" style="padding: 5px 12px; font-size: 11px; margin-right: 4px;">
                                        <x-icon name="zap" size="12" />
                                        <span>ترقية الباقة</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: var(--odoo-text-muted);">
                                    لا يوجد اشتراكات مسجلة حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal: Renew Subscription -->
    <div id="renewModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="refresh-cw" size="18" />
                    <span>تجديد اشتراك المشترك</span>
                </h3>
                <button class="modal-close" onclick="closeRenewModal()">&times;</button>
            </div>
            <form id="renewForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--odoo-border);">
                        <div style="font-size: 14px; font-weight: 800; color: var(--odoo-text-main);" id="renew_tenant_name"></div>
                        <div style="font-size: 12px; color: var(--odoo-text-muted); margin-top: 4px;">
                            تاريخ الانتهاء الحالي: <strong id="renew_current_expiry"></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>اختر مدة التجديد *</label>
                        <select name="months" class="form-control" required>
                            <option value="1">شهر واحد (+1 شهر)</option>
                            <option value="3">3 أشهر (+3 أشهر)</option>
                            <option value="6">6 أشهر (+6 أشهر)</option>
                            <option value="12" selected>سنة كاملة (+12 شهر)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeRenewModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>اعتماد التجديد وتحديث الصلاحية</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Change Plan -->
    <div id="changePlanModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="zap" size="18" />
                    <span>ترقية أو تعديل باقة المشترك</span>
                </h3>
                <button class="modal-close" onclick="closeChangePlanModal()">&times;</button>
            </div>
            <form id="changePlanForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--odoo-border);">
                        <div style="font-size: 14px; font-weight: 800; color: var(--odoo-text-main);" id="change_tenant_name"></div>
                        <div style="font-size: 12px; color: var(--odoo-text-muted); margin-top: 4px;">
                            الباقة الحالية: <strong id="change_current_plan"></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>اختر الباقة الجديدة *</label>
                        <select name="plan_id" class="form-control" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} — {{ number_format($plan->price_monthly, 0) }} ج.م/شهرياً (حد أقصى {{ $plan->max_users }} مستخدمين)
                                </option>
                            @endforeach
                        </select>
                        <span style="font-size: 11px; color: var(--odoo-text-muted); display: block; margin-top: 4px;">
                            سيتم فوراً تحديث موديولات المشترك وحد المستخدمين وفقاً للباقة الجديدة.
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeChangePlanModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>تطبيق الباقة الجديدة</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRenewModal(tenant) {
            document.getElementById('renewForm').action = '/saas/subscriptions/' + tenant.id + '/renew';
            document.getElementById('renew_tenant_name').innerText = tenant.name;
            document.getElementById('renew_current_expiry').innerText = tenant.expires_at ? tenant.expires_at.substring(0, 10) : 'غير محدد';
            document.getElementById('renewModal').classList.add('active');
        }
        function closeRenewModal() {
            document.getElementById('renewModal').classList.remove('active');
        }

        function openChangePlanModal(tenant) {
            document.getElementById('changePlanForm').action = '/saas/subscriptions/' + tenant.id + '/change-plan';
            document.getElementById('change_tenant_name').innerText = tenant.name;
            document.getElementById('change_current_plan').innerText = tenant.plan_name;
            document.getElementById('changePlanModal').classList.add('active');
        }
        function closeChangePlanModal() {
            document.getElementById('changePlanModal').classList.remove('active');
        }

        window.onclick = function(e) {
            if (e.target === document.getElementById('renewModal')) closeRenewModal();
            if (e.target === document.getElementById('changePlanModal')) closeChangePlanModal();
        }
    </script>
</body>
</html>
