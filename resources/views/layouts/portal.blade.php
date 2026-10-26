<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full light" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="كيان ERP">
    <meta name="theme-color" content="#714B67">
    <meta name="turbo-cache-control" content="no-cache">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <title>{{ $title ?? 'بوابة المندوب' }} | {{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</title>

    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/js/theme-init.js', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f6f8fc] text-slate-800 min-h-screen antialiased selection:bg-[#714B67] selection:text-white" style="font-family: 'Cairo', system-ui, -apple-system, sans-serif !important;">

    <div class="min-h-screen flex flex-col w-full overflow-x-hidden relative bg-[#f6f8fc]">

        <!-- Kayan ERP Topbar for Field Reps -->
        <header class="sticky top-0 z-40 bg-[#714B67] text-white shadow-sm border-b border-[#5e3c55] w-full">
            <div class="max-w-7xl mx-auto w-full px-3 sm:px-6 flex items-center justify-between gap-2 h-14">
                <!-- Brand & Module Badge -->
                <div class="flex items-center gap-2 min-w-0">
                    <a href="{{ route('portal.index') }}" class="group flex items-center gap-2 min-w-0">
                        @if(!empty($globalCompanySetting?->logo_url))
                            <img src="{{ $globalCompanySetting->logo_url }}" alt="Logo" class="h-6 w-6 object-contain rounded bg-white p-0.5 shrink-0">
                        @else
                            <span class="w-6 h-6 rounded bg-white text-[#714B67] font-black text-xs flex items-center justify-center shrink-0">K</span>
                        @endif
                        <span class="text-xs sm:text-base font-bold text-white truncate max-w-[140px] sm:max-w-xs">{{ $globalCompanySetting?->company_name ?? config('app.name', 'Kayan ERP') }}</span>
                    </a>

                    <div class="hidden sm:block w-px h-4 bg-white/20"></div>

                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-semibold bg-white/15 text-white/95">
                        <x-icon name="smartphone" size="13" />
                        <span>بوابة المندوب الميداني</span>
                    </span>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 text-xs font-medium">
                    <a href="{{ route('portal.index') }}" class="px-3 py-1.5 rounded transition-all {{ request()->routeIs('portal.index') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        الرئيسية
                    </a>
                    <a href="{{ route('portal.attendance.index') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.attendance.*') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        الحضور (GPS)
                    </a>
                    <a href="{{ route('portal.visits.index') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.visits.*') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        الزيارات
                    </a>
                    <a href="{{ route('portal.catalog') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.catalog') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        الكتالوج
                    </a>
                    <a href="{{ route('portal.customers.index') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.customers.*') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        عملائي
                    </a>
                    <a href="{{ route('portal.orders.index') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.orders.index') || request()->routeIs('portal.orders.show') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        سجل الطلبيات
                    </a>
                    <a href="{{ route('portal.commissions') }}" class="px-2.5 py-1.5 rounded transition-all {{ request()->routeIs('portal.commissions') ? 'bg-white/25 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        العمولات
                    </a>
                </nav>

                <!-- User Info & Actions -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <!-- New Order Button (Desktop only) -->
                    <a href="{{ route('portal.orders.create') }}" class="hidden sm:inline-flex erp-btn-pill-white px-3 py-1.5 text-xs">
                        <x-icon name="plus" size="13" />
                        <span>طلب جديد</span>
                    </a>

                    <!-- User & Commission Badge -->
                    <div class="hidden lg:flex items-center gap-2 px-2.5 py-1 rounded bg-white/10 border border-white/15 text-xs text-white">
                        <span class="font-bold">{{ auth()->user()->name }}</span>
                        <span class="text-white/50">|</span>
                        <span class="text-emerald-300 font-numeric font-semibold">
                            عمولة: {{ (float) (auth()->user()->commission_rate ?? 0) }}{{ auth()->user()->commission_type === 'percentage' ? '%' : ' ج.م' }}
                        </span>
                    </div>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('dashboard') }}" title="لوحة الإدارة العامة" class="p-1.5 rounded text-white/80 hover:text-white hover:bg-white/10 transition-colors flex items-center gap-1 text-xs">
                            <x-icon name="grid" size="15" />
                            <span class="hidden xl:inline">لوحة الإدارة</span>
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" title="تسجيل الخروج" class="p-2 rounded-lg text-white/80 hover:text-rose-200 hover:bg-white/10 transition-colors flex items-center justify-center">
                            <x-icon name="log-out" size="17" />
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Flash Notifications -->
        <div class="max-w-7xl mx-auto w-full px-3 sm:px-6 lg:px-8 pt-2.5">
        @if (session('status') === 'warning')
            <div class="erp-alert erp-alert-warning mb-3 shadow-xs bg-amber-50 border border-amber-300 text-amber-900 p-3 rounded-lg flex items-center gap-2.5" role="alert">
                <x-icon name="alert-triangle" size="18" class="text-amber-600 shrink-0" />
                <div class="text-xs sm:text-sm font-semibold">{{ session('message') }}</div>
            </div>
        @elseif (session('status') === 'success' || (session('message') && session('status') !== 'error'))
            <div class="erp-alert erp-alert-success mb-3 shadow-xs" role="alert">
                <x-icon name="check-circle" size="16" />
                <div>{{ session('message') ?? 'تم تنفيذ العملية بنجاح.' }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="erp-alert erp-alert-danger mb-3 shadow-xs" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="erp-alert erp-alert-danger mb-3 shadow-xs" role="alert">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <x-icon name="alert-circle" size="16" />
                    <span>يرجى مراجعة الأخطاء التالية:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Work Area -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-3.5 sm:px-6 lg:px-8 pb-32 md:pb-12 pt-3">
        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar (Faithful to Native PWA Reference Image 2) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/98 backdrop-blur-xl border-t border-slate-100 shadow-[0_-4px_25px_rgba(0,0,0,0.04)]"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="max-w-md mx-auto grid grid-cols-5 h-16 items-center px-1">
            <!-- 1. Dashboard -->
            <a href="{{ route('portal.index') }}" class="flex flex-col items-center justify-center py-1 transition-all relative {{ request()->routeIs('portal.index') ? 'text-[#714B67] font-black' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                @if(request()->routeIs('portal.index'))
                    <span class="w-8 h-1 rounded-full bg-[#714B67] -mt-1 mb-1 shadow-xs"></span>
                @else
                    <span class="w-8 h-1 bg-transparent -mt-1 mb-1"></span>
                @endif
                <x-icon name="layout-dashboard" size="21" strokeWidth="{{ request()->routeIs('portal.index') ? 2.4 : 2 }}" />
                <span class="text-[10px] mt-0.5">الرئيسية</span>
            </a>

            <!-- 2. Catalog -->
            <a href="{{ route('portal.catalog') }}" class="flex flex-col items-center justify-center py-1 transition-all relative {{ request()->routeIs('portal.catalog') ? 'text-[#714B67] font-black' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                @if(request()->routeIs('portal.catalog'))
                    <span class="w-8 h-1 rounded-full bg-[#714B67] -mt-1 mb-1 shadow-xs"></span>
                @else
                    <span class="w-8 h-1 bg-transparent -mt-1 mb-1"></span>
                @endif
                <x-icon name="package" size="21" strokeWidth="{{ request()->routeIs('portal.catalog') ? 2.4 : 2 }}" />
                <span class="text-[10px] mt-0.5">الكتالوج</span>
            </a>

            <!-- 3. New Order (Squircle FAB Floating Button like Image 2) -->
            <div class="flex items-center justify-center -mt-6">
                <a href="{{ route('portal.orders.create') }}" class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-[#714B67] to-[#8d5e82] text-white flex items-center justify-center shadow-lg shadow-[#714B67]/35 ring-4 ring-white active:scale-95 transition-all" title="طلب بيع جديد">
                    <x-icon name="plus" size="24" class="text-white" strokeWidth="2.8" />
                </a>
            </div>

            <!-- 4. Customers -->
            <a href="{{ route('portal.customers.index') }}" class="flex flex-col items-center justify-center py-1 transition-all relative {{ request()->routeIs('portal.customers.*') ? 'text-[#714B67] font-black' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                @if(request()->routeIs('portal.customers.*'))
                    <span class="w-8 h-1 rounded-full bg-[#714B67] -mt-1 mb-1 shadow-xs"></span>
                @else
                    <span class="w-8 h-1 bg-transparent -mt-1 mb-1"></span>
                @endif
                <x-icon name="users" size="21" strokeWidth="{{ request()->routeIs('portal.customers.*') ? 2.4 : 2 }}" />
                <span class="text-[10px] mt-0.5">عملائي</span>
            </a>

            <!-- 5. Commissions -->
            <a href="{{ route('portal.commissions') }}" class="flex flex-col items-center justify-center py-1 transition-all relative {{ request()->routeIs('portal.commissions') ? 'text-[#714B67] font-black' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                @if(request()->routeIs('portal.commissions'))
                    <span class="w-8 h-1 rounded-full bg-[#714B67] -mt-1 mb-1 shadow-xs"></span>
                @else
                    <span class="w-8 h-1 bg-transparent -mt-1 mb-1"></span>
                @endif
                <x-icon name="receipt" size="21" strokeWidth="{{ request()->routeIs('portal.commissions') ? 2.4 : 2 }}" />
                <span class="text-[10px] mt-0.5">عمولاتي</span>
            </a>
        </div>
    </nav>

    </div>

    <!-- Camera Barcode Scanner Modal for Portal -->
    <x-barcode-scanner-modal />

    @stack('scripts')
</body>
</html>
