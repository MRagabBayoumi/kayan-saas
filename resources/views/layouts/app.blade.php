<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light" data-density="comfortable">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'الرئيسية' }} | {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</title>

    @if(!empty($globalCompanySetting?->logo_url))
        <link rel="apple-touch-icon" href="{{ $globalCompanySetting->logo_url }}">
    @endif
    <!-- PWA & Desktop App Settings -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="كيان ERP">
    <meta name="turbo-cache-control" content="no-cache">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/js/theme-init.js', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="erp-app-wrapper">
    <!-- Dynamic Module Name Resolver -->
    @php
        $activeModuleName = $moduleName ?? (trim($__env->yieldContent('module_name')) ?: null);
        if (!$activeModuleName) {
            if (request()->is('inventory/movements*')) {
                $activeModuleName = 'سجل حركات المخزون';
            } elseif (request()->is('inventory*') || request()->is('warehouses*')) {
                $activeModuleName = 'المخزون والمستودعات';
            } elseif (request()->is('products*') || request()->is('categories*')) {
                $activeModuleName = 'المنتجات والأصناف';
            } elseif (request()->is('settings*')) {
                $activeModuleName = 'إعدادات النظام';
            } elseif (request()->is('sales-reps*')) {
                $activeModuleName = 'إدارة مناديب المبيعات';
            } elseif (request()->is('customers*')) {
                $activeModuleName = 'المبيعات والعملاء';
            } elseif (request()->is('suppliers*') || request()->is('shipping-offices*')) {
                $activeModuleName = 'المشتريات والتوريدات';
            } elseif (request()->is('expenses*') || request()->is('payments*')) {
                $activeModuleName = 'المالية والحسابات';
            } else {
                $activeModuleName = 'لوحة التحكم';
            }
        }
    @endphp

    <!-- Top Navigation Bar -->
    <x-topbar :module-name="$activeModuleName" />

    <!-- Application Body (Sidebar + Main View) -->
    <div class="erp-body-layout">
        <!-- Sidebar Navigation -->
        <x-sidebar />
        <div id="sidebarOverlay" class="erp-sidebar-overlay" aria-hidden="true"></div>

        <!-- Main Work Area -->
        <main class="erp-main">
            @yield('control_panel')

            <div class="erp-content">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="erp-footer">
                <div><a href="#">BayoSoft</a> © {{ date('Y') }}</div>
                <span class="erp-footer-sep">·</span>
                <div class="font-numeric">الإصدار 1.0</div>
            </footer>
        </main>
    </div>

    <!-- Odoo 3x3 App Launcher Drawer -->
    <x-app-launcher />

    <!-- Universal Print Options Modal -->
    <x-universal-print-modal />
</div>

@stack('scripts')

</body>
</html>
