<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light" data-density="comfortable">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'تسجيل الدخول' }} | {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</title>

    @if(!empty($globalCompanySetting?->logo_url))
        <link rel="icon" type="image/png" href="{{ $globalCompanySetting->logo_url }}">
        <link rel="apple-touch-icon" href="{{ $globalCompanySetting->logo_url }}">
    @endif
    <!-- PWA & Desktop App Settings -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="كيان ERP">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/js/theme-init.js', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="erp-auth-body">
    <div class="erp-auth-canvas">
        <div class="erp-auth-card">
            <!-- Brand Header -->
            <div class="erp-auth-brand">
                @if(!empty($globalCompanySetting?->logo_url))
                    <img src="{{ $globalCompanySetting->logo_url }}" alt="{{ $globalCompanySetting?->company_name ?? 'Kayan ERP' }}" class="erp-company-logo-img max-h-14 mb-3">
                @else
                    <div class="erp-auth-logo-badge">K</div>
                @endif
                <h1 class="erp-auth-title">{{ $globalCompanySetting?->company_name ?? 'شركة كيان للاستيراد والتوزيع' }}</h1>
                <p class="erp-auth-subtitle">نظام كيان المتكامل لإدارة موارد المؤسسات والمخازن</p>
            </div>

            <!-- Flash & Error Alerts -->
            @if(session('status') === 'success' && session('message'))
                <div class="erp-alert erp-alert-success">
                    <x-icon name="check-circle" size="18" />
                    <div>{{ session('message') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="erp-alert erp-alert-danger">
                    <x-icon name="alert-triangle" size="18" />
                    <div>
                        @if($errors->count() === 1)
                            <span>{{ $errors->first() }}</span>
                        @else
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="erp-auth-form">
                @csrf

                <div class="erp-form-group">
                    <label for="loginInput" class="erp-form-label">اسم المستخدم أو البريد الإلكتروني</label>
                    <div class="erp-auth-input-wrapper">
                        <span class="erp-auth-input-icon"><x-icon name="user" size="16" /></span>
                        <input
                            type="text"
                            id="loginInput"
                            name="login"
                            value="{{ old('login', old('email')) }}"
                            class="erp-input erp-auth-input @if($errors->has('login') || $errors->has('email')) is-invalid @endif"
                            placeholder="اسم المستخدم أو admin@kayan-erp.com"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="erp-form-group">
                    <label for="password" class="erp-form-label">كلمة المرور</label>
                    <div class="erp-auth-input-wrapper">
                        <span class="erp-auth-input-icon"><x-icon name="lock" size="16" /></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="erp-input erp-auth-input erp-auth-input-with-toggle @error('password') is-invalid @enderror"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="erp-auth-pwd-toggle" data-action="toggle-password" data-target="password" title="إظهار / إخفاء كلمة المرور" aria-label="إظهار أو إخفاء كلمة المرور">
                            <x-icon name="eye" size="16" />
                        </button>
                    </div>
                </div>

                <div class="erp-auth-row-options">
                    <label class="erp-auth-checkbox-label">
                        <input type="checkbox" name="remember" id="remember" value="1" class="erp-auth-checkbox" {{ old('remember') ? 'checked' : '' }}>
                        <span>تذكرني على هذا الجهاز</span>
                    </label>

                    <div class="erp-auth-security-badge" title="اتصال مشفر ومحمي">
                        <x-icon name="shield" size="14" />
                        <span>دخول مشفر ومحمي</span>
                    </div>
                </div>

                <button type="submit" class="erp-btn erp-btn-primary erp-auth-submit-btn">
                    <x-icon name="log-in" size="18" />
                    <span>تسجيل الدخول للنظام</span>
                </button>
            </form>

            <!-- Roles Info Helper -->
            <div class="erp-auth-hint-card">
                <div class="erp-auth-hint-title">
                    <x-icon name="info" size="14" />
                    <span>أدوار ومستويات الوصول للنظام:</span>
                </div>
                <div class="erp-auth-hint-roles">
                    <span class="erp-badge erp-badge-primary">مدير النظام (Admin)</span>
                    <span class="erp-badge erp-badge-warning">أمين المستودع (Warehouse)</span>
                    <span class="erp-badge erp-badge-neutral">الكاشير (Cashier)</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="erp-auth-footer">
                <span>{{ $globalCompanySetting?->company_name ?? 'شركة كيان للاستيراد والتوزيع' }} &copy; {{ date('Y') }} — جميع الحقوق محفوظة</span>
            </div>
        </div>
    </div>
</body>
</html>
