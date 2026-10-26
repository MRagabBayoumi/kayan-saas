<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديد الباقات والأسعار — Kayan SaaS</title>
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
        }
        .odoo-brand {
            font-size: 16px;
            font-weight: 800;
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
        }
        .odoo-cp-title p {
            font-size: 12px;
            color: var(--odoo-text-muted);
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
        }
        .btn-odoo-primary:hover { background-color: var(--odoo-primary-dark); }
        .btn-odoo-secondary {
            background-color: white;
            color: var(--odoo-text-main);
            border: 1px solid var(--odoo-border);
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-odoo-secondary:hover { background-color: #f1f5f9; }

        .odoo-content {
            padding: 24px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        /* Pricing Cards Grid */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        .plan-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
        }
        .plan-card.featured {
            border: 2px solid var(--odoo-primary);
        }
        .plan-header {
            padding: 24px;
            background: #fafafa;
            border-bottom: 1px solid var(--odoo-border);
            position: relative;
        }
        .plan-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--odoo-primary);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .plan-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--odoo-text-main);
            margin-bottom: 6px;
        }
        .plan-desc {
            font-size: 13px;
            color: var(--odoo-text-muted);
            line-height: 1.5;
            min-height: 40px;
        }
        .plan-price-wrap {
            margin-top: 16px;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .plan-price {
            font-size: 32px;
            font-weight: 900;
            color: var(--odoo-primary);
        }
        .plan-period {
            font-size: 13px;
            color: var(--odoo-text-muted);
            font-weight: 600;
        }
        .plan-yearly-hint {
            font-size: 12px;
            color: var(--odoo-success);
            font-weight: 700;
            margin-top: 4px;
        }

        .plan-body {
            padding: 24px;
            flex: 1;
        }
        .feature-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--odoo-text-muted);
            margin-bottom: 14px;
            letter-spacing: 0.5px;
        }
        .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
        }
        .feature-item.included { color: #1e293b; }
        .feature-item.excluded { color: #94a3b8; text-decoration: line-through; }
        .feature-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .feature-item.included .feature-icon { background: #dcfce7; color: #166534; }
        .feature-item.excluded .feature-icon { background: #f1f5f9; color: #94a3b8; }

        .plan-footer {
            padding: 16px 24px;
            background: #fafafa;
            border-top: 1px solid var(--odoo-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #ffffff;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
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
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--odoo-text-muted); }
        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }
        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--odoo-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8fafc;
        }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--odoo-border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
        }
        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 6px;
        }
        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .alert {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
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
                <a href="{{ route('saas.plans.index') }}" class="odoo-nav-item active">
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

    <!-- Odoo Sub-Control Panel -->
    <section class="odoo-control-panel">
        <div class="odoo-cp-title">
            <h1>إدارة باقات الاشتراك والأسعار (Subscription Plans & Pricing)</h1>
            <p>تحديد أسعار الاشتراكات الشهرية والسنوية، حد أقصى للمستخدمين، وتعيين الموديولات المفتوحة لكل باقة</p>
        </div>
        <div class="odoo-cp-actions">
            <button class="btn-odoo-primary" onclick="openNewPlanModal()">
                <x-icon name="plus" size="15" />
                <span>إضافة باقة جديدة</span>
            </button>
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

        <!-- Pricing Cards -->
        <div class="pricing-grid">
            @foreach($plans as $plan)
                @php
                    $mods = $plan->modules ?? [];
                @endphp
                <div class="plan-card {{ $plan->is_featured ? 'featured' : '' }}">
                    <div class="plan-header">
                        @if($plan->badge_text)
                            <div class="plan-badge">{{ $plan->badge_text }}</div>
                        @endif
                        <div class="plan-name">{{ $plan->name }}</div>
                        <div class="plan-desc">{{ $plan->description }}</div>
                        <div class="plan-price-wrap">
                            <span class="plan-price">{{ number_format($plan->price_monthly, 0) }}</span>
                            <span class="plan-period">ج.م / شهرياً</span>
                        </div>
                        <div class="plan-yearly-hint">
                            أو {{ number_format($plan->price_yearly, 0) }} ج.م سنوياً (خصم شهرين مجاناً)
                        </div>
                    </div>

                    <div class="plan-body">
                        <div class="feature-title">المميزات والحدود المتاحة:</div>
                        <ul class="feature-list">
                            <li class="feature-item included">
                                <span class="feature-icon"><x-icon name="check" size="12" /></span>
                                <span>الحد الأقصى للمستخدمين: <strong>{{ $plan->max_users }} مستخدمين</strong></span>
                            </li>
                            <li class="feature-item included">
                                <span class="feature-icon"><x-icon name="check" size="12" /></span>
                                <span>نقاط البيع والفواتير والمخازن والباركود</span>
                            </li>
                            <li class="feature-item {{ ($mods['accounting'] ?? false) ? 'included' : 'excluded' }}">
                                <span class="feature-icon">
                                    @if($mods['accounting'] ?? false)
                                        <x-icon name="check" size="12" />
                                    @else
                                        <x-icon name="x" size="12" />
                                    @endif
                                </span>
                                <span>موديول المحاسبة وشجرة الحسابات والقيود</span>
                            </li>
                            <li class="feature-item {{ ($mods['sales_reps'] ?? false) ? 'included' : 'excluded' }}">
                                <span class="feature-icon">
                                    @if($mods['sales_reps'] ?? false)
                                        <x-icon name="check" size="12" />
                                    @else
                                        <x-icon name="x" size="12" />
                                    @endif
                                </span>
                                <span>موديول المناديب الميدانيين وتتبع GPS وبوابة الموبايل</span>
                            </li>
                            <li class="feature-item {{ ($mods['installments'] ?? false) ? 'included' : 'excluded' }}">
                                <span class="feature-icon">
                                    @if($mods['installments'] ?? false)
                                        <x-icon name="check" size="12" />
                                    @else
                                        <x-icon name="x" size="12" />
                                    @endif
                                </span>
                                <span>موديول أقساط العملاء وجداول الاستحقاق</span>
                            </li>
                            <li class="feature-item {{ ($mods['ecommerce'] ?? false) ? 'included' : 'excluded' }}">
                                <span class="feature-icon">
                                    @if($mods['ecommerce'] ?? false)
                                        <x-icon name="check" size="12" />
                                    @else
                                        <x-icon name="x" size="12" />
                                    @endif
                                </span>
                                <span>متجر إلكتروني متكامل وسلة شراء أونلاين</span>
                            </li>
                            <li class="feature-item included">
                                <span class="feature-icon"><x-icon name="check" size="12" /></span>
                                <span>قاعدة بيانات فيزيائية مستقلة 100%</span>
                            </li>
                        </ul>
                    </div>

                    <div class="plan-footer">
                        <span style="font-size: 12px; font-weight: 700; color: var(--odoo-text-muted);">
                            المشتركون بالباقة: <strong>{{ $plan->tenants_count ?? 0 }}</strong>
                        </span>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn-odoo-secondary" onclick="openEditPlanModal({{ json_encode($plan) }})" style="padding: 5px 12px; font-size: 12px;">
                                <x-icon name="edit" size="13" />
                                <span>تعديل</span>
                            </button>
                            <form action="{{ route('saas.plans.toggle', $plan->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-odoo-secondary" style="padding: 5px 12px; font-size: 12px; color: {{ $plan->is_active ? 'var(--odoo-danger)' : 'var(--odoo-success)' }};">
                                    <x-icon name="{{ $plan->is_active ? 'shield' : 'zap' }}" size="13" />
                                    <span>{{ $plan->is_active ? 'تعطيل' : 'تفعيل' }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </main>

    <!-- Modal: Add New Plan -->
    <div id="newPlanModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="tag" size="18" />
                    <span>إضافة باقة اشتراك جديدة</span>
                </h3>
                <button class="modal-close" onclick="closeNewPlanModal()">&times;</button>
            </div>
            <form action="{{ route('saas.plans.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>اسم الباقة *</label>
                            <input type="text" name="name" class="form-control" required placeholder="مثال: الباقة الذهبية">
                        </div>
                        <div class="form-group">
                            <label>الكود الرمزي *</label>
                            <input type="text" name="code" class="form-control" required placeholder="gold" style="font-family: monospace;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>وصف الباقة</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="شرح مختصر لمميزات الباقة والفئة المستهدفة"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>السعر الشهري (ج.م) *</label>
                            <input type="number" step="1" name="price_monthly" class="form-control" required value="499">
                        </div>
                        <div class="form-group">
                            <label>السعر السنوي (ج.م) *</label>
                            <input type="number" step="1" name="price_yearly" class="form-control" required value="4990">
                        </div>
                        <div class="form-group">
                            <label>حد المستخدمين *</label>
                            <input type="number" name="max_users" class="form-control" required value="5" min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>شارة ترويجية (Badge)</label>
                        <input type="text" name="badge_text" class="form-control" placeholder="مثال: الأكثر مبيعاً أو مميز">
                    </div>

                    <div class="form-group">
                        <label>الموديولات المتاحة في هذه الباقة:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_accounting" value="1">
                                <span>موديول المحاسبة وشجرة الحسابات</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_sales_reps" value="1">
                                <span>موديول المناديب وبوابة الموبايل</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_installments" value="1" checked>
                                <span>موديول الأقساط</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="has_ecommerce" value="1" checked>
                                <span>موديول المتجر الإلكتروني (E-Commerce)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_featured" value="1">
                            <span>تمييز هذه الباقة في واجهة الاختيار (Featured)</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeNewPlanModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>حفظ الباقة الجديدة</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Plan -->
    <div id="editPlanModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="edit" size="18" />
                    <span>تعديل باقة الاشتراك</span>
                </h3>
                <button class="modal-close" onclick="closeEditPlanModal()">&times;</button>
            </div>
            <form id="editPlanForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>اسم الباقة *</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>وصف الباقة</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>السعر الشهري (ج.م) *</label>
                            <input type="number" step="1" id="edit_price_monthly" name="price_monthly" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>السعر السنوي (ج.م) *</label>
                            <input type="number" step="1" id="edit_price_yearly" name="price_yearly" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>حد المستخدمين *</label>
                            <input type="number" id="edit_max_users" name="max_users" class="form-control" required min="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>شارة ترويجية (Badge)</label>
                        <input type="text" id="edit_badge_text" name="badge_text" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>الموديولات المتاحة في هذه الباقة:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="edit_has_accounting" name="has_accounting" value="1">
                                <span>موديول المحاسبة وشجرة الحسابات</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="edit_has_sales_reps" name="has_sales_reps" value="1">
                                <span>موديول المناديب وبوابة الموبايل</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="edit_has_installments" name="has_installments" value="1">
                                <span>موديول الأقساط</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" id="edit_has_ecommerce" name="has_ecommerce" value="1">
                                <span>موديول المتجر الإلكتروني (E-Commerce)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="edit_is_featured" name="is_featured" value="1">
                            <span>تمييز هذه الباقة (Featured)</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-odoo-secondary" onclick="closeEditPlanModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>حفظ التعديلات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openNewPlanModal() {
            document.getElementById('newPlanModal').classList.add('active');
        }
        function closeNewPlanModal() {
            document.getElementById('newPlanModal').classList.remove('active');
        }
        function openEditPlanModal(plan) {
            document.getElementById('editPlanForm').action = '/saas/plans/' + plan.id;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_description').value = plan.description || '';
            document.getElementById('edit_price_monthly').value = parseInt(plan.price_monthly);
            document.getElementById('edit_price_yearly').value = parseInt(plan.price_yearly);
            document.getElementById('edit_max_users').value = plan.max_users || 3;
            document.getElementById('edit_badge_text').value = plan.badge_text || '';
            document.getElementById('edit_is_featured').checked = plan.is_featured;

            const mods = plan.modules || {};
            document.getElementById('edit_has_accounting').checked = !!mods.accounting;
            document.getElementById('edit_has_sales_reps').checked = !!mods.sales_reps;
            document.getElementById('edit_has_installments').checked = !!mods.installments;
            document.getElementById('edit_has_ecommerce').checked = !!mods.ecommerce;

            document.getElementById('editPlanModal').classList.add('active');
        }
        function closeEditPlanModal() {
            document.getElementById('editPlanModal').classList.remove('active');
        }
        window.onclick = function(e) {
            if (e.target === document.getElementById('newPlanModal')) closeNewPlanModal();
            if (e.target === document.getElementById('editPlanModal')) closeEditPlanModal();
        }
    </script>
</body>
</html>
