<!DOCTYPE html>
<html lang="ar" dir="rtl" class="shop-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $settings->store_name ?? 'المتجر الإلكتروني' }}</title>
    
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}">
</head>
<body class="shop-body">

    <div class="shop-wrapper">

        <!-- Top Announcement Bar -->
        @if(!empty($settings->announcement_text))
            <div class="shop-top-banner">
                <x-icon name="sparkles" size="14" />
                <span>{{ $settings->announcement_text }}</span>
            </div>
        @endif

        <!-- Main Navigation Header -->
        <header class="shop-header">
            <div class="shop-container">
                <div class="shop-header-inner">
                    
                    <!-- Logo & Store Branding -->
                    <a href="{{ route('shop.catalog') }}" class="shop-brand">
                        <div class="shop-brand-logo">
                            <x-icon name="shopping-bag" size="22" />
                        </div>
                        <div>
                            <span class="shop-brand-title">
                                {{ $settings->store_name ?? 'متجرنا الإلكتروني' }}
                            </span>
                            @if($settings->store_tagline)
                                <span class="shop-brand-tagline">
                                    {{ $settings->store_tagline }}
                                </span>
                            @endif
                        </div>
                    </a>

                    <!-- Desktop Search Bar -->
                    <form action="{{ route('shop.catalog') }}" method="GET" class="shop-search-form">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="ابحث عن منتج، كود، أو صنف..." 
                               class="shop-search-input">
                        <button type="submit" class="shop-search-btn">
                            <x-icon name="search" size="16" />
                        </button>
                    </form>

                    <!-- Cart and Navigation Actions -->
                    <div class="shop-header-actions">
                        @auth
                            <a href="{{ url('/') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #475569; text-decoration: none; padding: 6px 12px; background: #f1f5f9; border-radius: 10px;">
                                <x-icon name="layout-dashboard" size="14" />
                                <span style="display: none; @media(min-width: 640px){display:inline;}">لوحة الـ ERP</span>
                            </a>
                        @endauth

                        <a href="{{ route('shop.cart') }}" class="btn-shop-cart">
                            <x-icon name="shopping-cart" size="16" />
                            <span>السلة</span>
                            @php
                                $cartTotalItems = array_sum(array_column(session('shop_cart', []), 'quantity'));
                            @endphp
                            <span class="cart-count-pill">{{ $cartTotalItems }}</span>
                        </a>
                    </div>
                </div>

                <!-- Mobile Search Bar -->
                <div class="shop-mobile-search">
                    <form action="{{ route('shop.catalog') }}" method="GET" style="position: relative;">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="ابحث عن المنتجات..." 
                               class="shop-search-input">
                        <button type="submit" class="shop-search-btn">
                            <x-icon name="search" size="16" />
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="shop-container">
            @if(session('success'))
                <div class="shop-alert success">
                    <x-icon name="check-circle" size="18" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="shop-alert error">
                    <x-icon name="alert-triangle" size="18" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if(session('warning'))
                <div class="shop-alert warning">
                    <x-icon name="alert-circle" size="18" />
                    <span>{{ session('warning') }}</span>
                </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <main class="shop-container" style="flex: 1; padding-top: 16px; padding-bottom: 40px;">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="shop-footer">
            <div class="shop-container">
                <div class="footer-grid">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <div class="shop-brand-logo" style="width: 32px; height: 32px; border-radius: 10px;">
                                <x-icon name="shopping-bag" size="16" />
                            </div>
                            <strong style="color: #0f172a; font-size: 15px;">{{ $settings->store_name ?? 'متجرنا الإلكتروني' }}</strong>
                        </div>
                        <p style="font-size: 12px; color: #64748b; line-height: 1.6; margin: 0; max-width: 480px;">
                            {{ $settings->store_tagline ?? 'تسوق بكل سهولة وأمان مع أفضل تجربة تسوق إلكتروني متكاملة وربط فوري بالمخزون ومتابعة حالة الطلبات حتى باب المنزل.' }}
                        </p>
                    </div>

                    <div>
                        <h5 class="footer-title">مميزات المتجر</h5>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #64748b; line-height: 2;">
                            <li style="display: flex; align-items: center; gap: 6px;">
                                <x-icon name="truck" size="14" style="color: #4f46e5;" />
                                <span>شحن وتوصيل سريع وموثوق</span>
                            </li>
                            <li style="display: flex; align-items: center; gap: 6px;">
                                <x-icon name="shield-check" size="14" style="color: #059669;" />
                                <span>الدفع عند الاستلام نقداً</span>
                            </li>
                            <li style="display: flex; align-items: center; gap: 6px;">
                                <x-icon name="check-circle" size="14" style="color: #2563eb;" />
                                <span>مخزون محدث لحظياً من الـ ERP</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h5 class="footer-title">خدمة العملاء</h5>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #64748b; line-height: 2;">
                            @if($settings->contact_phone)
                                <li style="display: flex; align-items: center; gap: 6px;">
                                    <x-icon name="phone" size="14" style="color: #94a3b8;" />
                                    <span dir="ltr">{{ $settings->contact_phone }}</span>
                                </li>
                            @endif
                            @if($settings->contact_whatsapp)
                                <li style="display: flex; align-items: center; gap: 6px;">
                                    <x-icon name="message-circle" size="14" style="color: #059669;" />
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->contact_whatsapp) }}" target="_blank" style="color: #059669; font-weight: 700; text-decoration: none;">
                                        تواصل عبر واتساب
                                    </a>
                                </li>
                            @endif
                            <li style="display: flex; align-items: center; gap: 6px;">
                                <x-icon name="lock" size="14" style="color: #94a3b8;" />
                                <span>مدعوم بنظام Kayan SaaS السحابي</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; font-size: 11px; color: #94a3b8;">
                    <div>&copy; {{ date('Y') }} {{ $settings->store_name ?? 'المتجر الإلكتروني' }}. جميع الحقوق محفوظة.</div>
                    <div>نظام التجارة الإلكترونية المتكامل &bull; Kayan ERP</div>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
