<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة وتوجيه تطبيقات وموديولات النظام — Kayan SaaS</title>
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
            padding: 0 20px;
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
        .odoo-brand {
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ffffff;
            text-decoration: none;
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
            gap: 6px;
            margin-right: 20px;
        }
        .odoo-nav-item {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .odoo-nav-item:hover, .odoo-nav-item.active {
            color: #ffffff;
            background: rgba(255,255,255,0.18);
        }
        .odoo-user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
            gap: 14px;
        }
        .odoo-cp-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .odoo-cp-title p {
            font-size: 12px;
            color: var(--odoo-text-muted);
        }
        .btn-odoo-primary {
            background-color: var(--odoo-secondary);
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-odoo-primary:hover {
            opacity: 0.92;
        }

        /* Main Container */
        .odoo-content {
            padding: 24px 28px;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kpi-label {
            font-size: 12px;
            color: var(--odoo-text-muted);
            font-weight: 600;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
        }
        .kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Modules Grid (Odoo Apps Style) */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 20px;
        }
        .module-card {
            background: #ffffff;
            border: 1px solid var(--odoo-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
        }
        .module-card.inactive {
            opacity: 0.65;
            background: #fafafa;
        }
        .module-card-header {
            padding: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .module-avatar {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 12px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .module-header-meta {
            flex: 1;
            min-width: 0;
        }
        .module-code-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            font-family: monospace;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            margin-bottom: 4px;
        }
        .module-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .module-card-body {
            padding: 18px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .module-desc {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }
        .module-pricing-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .price-label {
            font-size: 11px;
            color: var(--odoo-text-muted);
            margin-bottom: 2px;
        }
        .price-num {
            font-size: 16px;
            font-weight: 900;
            color: var(--odoo-primary);
        }
        .module-features-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12px;
            color: #334155;
        }
        .module-features-list li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        .module-features-list li span.check {
            color: var(--odoo-success);
            font-weight: 900;
        }
        .module-card-footer {
            padding: 14px 20px;
            background: #fdfdfd;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Action Buttons */
        .btn-edit {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-edit:hover {
            background: #e2e8f0;
        }
        .btn-toggle {
            background: none;
            border: 1px solid transparent;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }
        .btn-toggle.active {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }
        .btn-toggle.inactive {
            background: #fff1f2;
            color: #e11d48;
            border-color: #fecdd3;
        }

        /* Modal Styles */
        .modal {
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
            padding: 20px;
            backdrop-filter: blur(3px);
        }
        .modal.active {
            display: flex;
        }
        .modal-card {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--odoo-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #94a3b8;
            cursor: pointer;
        }
        .modal-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--odoo-border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8fafc;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: #0f172a;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--odoo-primary);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.12);
        }
    </style>
</head>
<body>

    <!-- Odoo Top Navbar -->
    <header class="odoo-navbar">
        <div class="odoo-nav-start">
            <a href="{{ route('saas.index') }}" class="odoo-brand">
                <span>Kayan SaaS</span>
                <span class="badge-saas">CONTROL PLANE</span>
            </a>
            <nav class="odoo-nav-links">
                <a href="{{ route('saas.index') }}" class="odoo-nav-item">
                    <x-icon name="layout-dashboard" size="15" />
                    <span>لوحة التحكم</span>
                </a>
                <a href="{{ route('saas.modules.index') }}" class="odoo-nav-item active">
                    <x-icon name="grid" size="15" />
                    <span>متجر الموديولات والتطبيقات</span>
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
        <div class="odoo-user-pill">
            <x-icon name="shield-check" size="14" style="color: #10b981;" />
            <span>{{ auth()->user()->name ?? 'مدير النظام السحابي' }}</span>
        </div>
    </header>

    <!-- Sub Control Panel -->
    <div class="odoo-control-panel">
        <div class="odoo-cp-title">
            <h1>كتالوج الموديولات والتطبيقات (Odoo-like App Catalog)</h1>
            <p>أنت كصاحب النظام تتحكم بنسبة 100% في تحديد اسم كل تطبيق، ما يحتويه من وظائف، وسعره الشهري والسنوي للعملاء.</p>
        </div>
        <div>
            <button type="button" class="btn-odoo-primary" onclick="openAddModal()">
                <x-icon name="plus" size="16" />
                <span>إضافة تطبيق / موديول جديد</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('message'))
        <div style="margin: 16px 28px 0 28px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px 18px; color: #065f46; font-size: 13px; font-weight: 700;">
            {{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div style="margin: 16px 28px 0 28px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 12px 18px; color: #be123c; font-size: 13px; font-weight: 700;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Content -->
    <main class="odoo-content">

        <!-- KPI Metrics -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">إجمالي التطبيقات المعرفة</div>
                    <div class="kpi-value">{{ $metrics['total'] }}</div>
                </div>
                <div class="kpi-icon" style="background: #f3e8ff; color: #9333ea;">🧩</div>
            </div>
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">تطبيقات معروضة للبيع</div>
                    <div class="kpi-value">{{ $metrics['active'] }}</div>
                </div>
                <div class="kpi-icon" style="background: #ecfdf5; color: #059669;">✅</div>
            </div>
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">إجمالي قيمة الحزمة كاملة (شهرياً)</div>
                    <div class="kpi-value">{{ number_format($metrics['monthly_sum'], 0) }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">ج.م</span></div>
                </div>
                <div class="kpi-icon" style="background: #f0fdf4; color: #16a34a;">💰</div>
            </div>
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">قيمة الحزمة كاملة (سنوياً)</div>
                    <div class="kpi-value">{{ number_format($metrics['yearly_sum'], 0) }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">ج.م</span></div>
                </div>
                <div class="kpi-icon" style="background: #eff6ff; color: #2563eb;">💎</div>
            </div>
        </div>

        <!-- Modules Cards Grid -->
        <div class="modules-grid">
            @foreach($modules as $module)
                <div class="module-card {{ $module->is_active ? '' : 'inactive' }}">
                    <div class="module-card-header">
                        <div class="module-avatar" style="background-color: {{ $module->color ?? '#714B67' }};">
                            @if($module->icon === 'calculator') 🧮
                            @elseif($module->icon === 'box') 📦
                            @elseif($module->icon === 'truck') 🚚
                            @elseif($module->icon === 'clipboard') 📋
                            @elseif($module->icon === 'book-open') 📖
                            @elseif($module->icon === 'credit-card') 💳
                            @elseif($module->icon === 'users') 👥
                            @elseif($module->icon === 'store') 🛍️
                            @else 🧩
                            @endif
                        </div>
                        <div class="module-header-meta">
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <span class="module-code-pill">{{ $module->code }}</span>
                                @if($module->is_core)
                                    <span style="font-size: 10px; background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-weight: 800;">أساسي</span>
                                @endif
                            </div>
                            <h2 class="module-name">{{ $module->name }}</h2>
                        </div>
                    </div>

                    <div class="module-card-body">
                        <p class="module-desc">{{ $module->description }}</p>

                        <!-- Pricing Box -->
                        <div class="module-pricing-box">
                            <div>
                                <div class="price-label">السعر الشهري</div>
                                <div class="price-num">{{ number_format($module->price_monthly, 0) }} ج.م</div>
                            </div>
                            <div style="border-left: 1px solid #e2e8f0;"></div>
                            <div>
                                <div class="price-label">السعر السنوي</div>
                                <div class="price-num">{{ number_format($module->price_yearly, 0) }} ج.م</div>
                            </div>
                        </div>

                        <!-- Features Checklist -->
                        @if(!empty($module->features_list))
                            <div>
                                <div style="font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">الوظائف والمميزات المشمولة:</div>
                                <ul class="module-features-list">
                                    @foreach($module->features_list as $feature)
                                        <li>
                                            <span class="check">✓</span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="module-card-footer">
                        <!-- Toggle Form -->
                        <form action="{{ route('saas.modules.toggle', $module) }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn-toggle {{ $module->is_active ? 'active' : 'inactive' }}" title="انقر لتغيير حالة البيع">
                                {{ $module->is_active ? '● معروض للبيع' : '○ متوقف حالياً' }}
                            </button>
                        </form>

                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn-edit" onclick='openEditModal(@json($module))'>
                                <x-icon name="edit" size="13" />
                                <span>تعديل السعر والميزات</span>
                            </button>

                            @if(!$module->is_core)
                                <form action="{{ route('saas.modules.destroy', $module) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموديول من الكتالوج؟');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 6px;" title="حذف">
                                        <x-icon name="trash-2" size="15" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </main>

    <!-- Modal: Add New Module -->
    <div id="addModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>إضافة تطبيق / موديول جديد إلى النظام</h3>
                <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form action="{{ route('saas.modules.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">كود الموديول البرمجي (Code) *</label>
                            <input type="text" name="code" class="form-input" placeholder="مثال: hr, analytics, loyalty" required dir="ltr">
                            <small style="color: #64748b; font-size: 11px;">يستخدم في حماية المسارات برمجياً (أحرف إنجليزية وأرقام فقط)</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">اسم التطبيق التجاري *</label>
                            <input type="text" name="name" class="form-input" placeholder="مثال: إدارة شؤون الموظفين والرواتب" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">السعر الشهري (ج.م) *</label>
                            <input type="number" step="0.01" name="price_monthly" class="form-input" placeholder="150" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">السعر السنوي (ج.م)</label>
                            <input type="number" step="0.01" name="price_yearly" class="form-input" placeholder="اتركه فارغاً ليتم حسابه تلقائياً">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">أيقونة التطبيق</label>
                            <select name="icon" class="form-select">
                                <option value="box">📦 صندوق مخازن</option>
                                <option value="calculator">🧮 حاسبة / كاشير</option>
                                <option value="truck">🚚 شاحنة / مشتريات</option>
                                <option value="clipboard">📋 قائمة تعبئة</option>
                                <option value="book-open">📖 دفتر حسابات</option>
                                <option value="credit-card">💳 بطاقة ائتمان / أقساط</option>
                                <option value="users">👥 فريق عمل / مناديب</option>
                                <option value="store">🛍️ متجر إلكتروني</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">لون بطاقة التطبيق</label>
                            <input type="color" name="color" class="form-input" value="#714B67" style="height: 42px; padding: 2px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">وصف التطبيق للعميل</label>
                        <textarea name="description" rows="2" class="form-textarea" placeholder="اشرح للعميل لماذا يحتاج هذا التطبيق وما فائدته لشركته..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">قائمة الوظائف والميزات (سطر لكل ميزة)</label>
                        <textarea name="features" rows="4" class="form-textarea" placeholder="تسجيل الحضور والانصراف الذكي&#10;مسيرات الرواتب والسلف والخصومات&#10;طباعة مفردات المرتب"></textarea>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            <input type="checkbox" name="is_core" value="1">
                            <span>تطبيق أساسي إجباري في كل اشتراك</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>مفعل ومعروض للبيع فوراً</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeAddModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">حفظ وإضافة إلى الكتالوج</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Module -->
    <div id="editModal" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>تعديل بيانات وأسعار الموديول</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">اسم التطبيق التجاري *</label>
                        <input type="text" id="edit_name" name="name" class="form-input" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">السعر الشهري (ج.م) *</label>
                            <input type="number" step="0.01" id="edit_price_monthly" name="price_monthly" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">السعر السنوي (ج.م) *</label>
                            <input type="number" step="0.01" id="edit_price_yearly" name="price_yearly" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">أيقونة التطبيق</label>
                            <select id="edit_icon" name="icon" class="form-select">
                                <option value="box">📦 صندوق مخازن</option>
                                <option value="calculator">🧮 حاسبة / كاشير</option>
                                <option value="truck">🚚 شاحنة / مشتريات</option>
                                <option value="clipboard">📋 قائمة تعبئة</option>
                                <option value="book-open">📖 دفتر حسابات</option>
                                <option value="credit-card">💳 بطاقة ائتمان / أقساط</option>
                                <option value="users">👥 فريق عمل / مناديب</option>
                                <option value="store">🛍️ متجر إلكتروني</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">لون بطاقة التطبيق</label>
                            <input type="color" id="edit_color" name="color" class="form-input" style="height: 42px; padding: 2px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">وصف التطبيق للعميل</label>
                        <textarea id="edit_description" name="description" rows="2" class="form-textarea"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">قائمة الوظائف والميزات (سطر لكل ميزة)</label>
                        <textarea id="edit_features" name="features" rows="4" class="form-textarea"></textarea>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            <input type="checkbox" id="edit_is_core" name="is_core" value="1">
                            <span>تطبيق أساسي إجباري</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; cursor: pointer;">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <span>معروض للبيع</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-edit" onclick="closeEditModal()">إلغاء</button>
                    <button type="submit" class="btn-odoo-primary">حفظ التعديلات والأسعار</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openEditModal(module) {
            document.getElementById('editForm').action = '/saas/modules/' + module.id;
            document.getElementById('edit_name').value = module.name;
            document.getElementById('edit_price_monthly').value = module.price_monthly;
            document.getElementById('edit_price_yearly').value = module.price_yearly;
            document.getElementById('edit_icon').value = module.icon || 'box';
            document.getElementById('edit_color').value = module.color || '#714B67';
            document.getElementById('edit_description').value = module.description || '';

            const features = Array.isArray(module.features) ? module.features : [];
            document.getElementById('edit_features').value = features.join('\n');

            document.getElementById('edit_is_core').checked = !!module.is_core;
            document.getElementById('edit_is_active').checked = !!module.is_active;

            document.getElementById('editModal').classList.add('active');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        window.onclick = function(e) {
            const addM = document.getElementById('addModal');
            const editM = document.getElementById('editModal');
            if (e.target === addM) closeAddModal();
            if (e.target === editM) closeEditModal();
        };
    </script>
</body>
</html>
