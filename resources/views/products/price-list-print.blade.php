<!DOCTYPE html>
<html lang="ar" dir="rtl" class="emerald">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>

    <!-- Google Fonts: Cairo -->
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/invoice-document.css', 'resources/js/pages/invoice-print.js'])
</head>
<body data-format="{{ $format }}" data-auto-print="{{ $autoPrint ? 'true' : 'false' }}">

    <!-- Screen-Only Navigation & Print Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>طباعة قائمة أسعار وبيان تعبئة المنتجات (Packing List)</span>
            </span>

            <div class="toolbar-formats">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                {{ $products->count() }} أصناف مشمولة
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-primary">
                <x-icon name="printer" size="16" />
                <span>طباعة المستند</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- Document Paper Sheet Container -->
    <main class="invoice-page-container">
        <article class="invoice-sheet" data-format="{{ $format }}">
            <!-- 1. Enterprise Dispatch Header (A4 / A5 Layout) -->
            <header class="dispatch-header">
                <!-- Right: Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">كتالوج المنتجات والأسعار</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">نوع المستند :</span>
                        <strong class="dispatch-field-val">قائمة أسعار وبيان تعبئة (Packing List)</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">عدد الأصناف :</span>
                        <span class="dispatch-field-val font-numeric">{{ $products->count() }} صنف</span>
                    </div>
                    @if($selectedCategory)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">القسم المختار :</span>
                        <span class="dispatch-field-val font-bold">{{ $selectedCategory->name }}</span>
                    </div>
                    @endif
                </div>

                <!-- Center: Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">قائمة أسعار وتعبئة المنتجات</span>
                        <span class="stamp-date font-numeric">{{ now()->format('d/m/Y') }}</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left: Date & Time, Company Info -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المؤسسة :</span>
                        <strong class="dispatch-field-val">{{ $company->company_name }}</strong>
                    </div>
                    @if($company->phone)
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">هاتف المبيعات :</span>
                        <span class="dispatch-field-val font-numeric">{{ $company->phone }}</span>
                    </div>
                    @endif
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">تاريخ الإصدار :</span>
                        <span class="dispatch-field-val font-numeric">{{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. Products Grid -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-w-5 text-center">#</th>
                        <th class="col-w-15 text-center">كود الصنف</th>
                        <th class="col-w-30 text-right">اسم المنتج / الصنف</th>
                        <th class="col-w-15 text-center">القسم</th>
                        <th class="col-w-10 text-center">الوحدة</th>
                        <th class="col-w-15 text-center">التعبئة (عبوة/كرتونة)</th>
                        @if(in_array($priceType, ['all', 'wholesale', 'wholesale_retail'], true))
                            <th class="col-w-15 text-center bg-amber-500/10 font-bold">سعر الجملة</th>
                        @endif
                        @if(in_array($priceType, ['all', 'retail', 'wholesale_retail'], true))
                            <th class="col-w-15 text-center bg-blue-500/10 font-bold">سعر القطاعي</th>
                        @endif
                        @if(in_array($priceType, ['all', 'distributor'], true))
                            <th class="col-w-15 text-center bg-purple-500/10 font-bold">سعر الموزع</th>
                        @endif
                        @if($showStock)
                            <th class="col-w-10 text-center">المتاح</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $prod)
                        @php
                            $pieces = (int) ($prod->pieces_per_package ?: 12);
                            $stock = (float) $prod->total_stock;
                        @endphp
                        <tr>
                            <td class="text-center font-numeric">{{ $idx + 1 }}</td>
                            <td class="text-center font-numeric font-bold">{{ $prod->sku }}</td>
                            <td>
                                <strong>{{ $prod->name }}</strong>
                            </td>
                            <td class="text-center text-xs text-muted">{{ $prod->category?->name ?? 'عام' }}</td>
                            <td class="text-center text-xs font-semibold">{{ $prod->unit ?? 'قطعة' }}</td>
                            <td class="text-center font-numeric text-xs font-bold">
                                {{ $pieces }} قطعة / عبوة
                            </td>
                            @if(in_array($priceType, ['all', 'wholesale', 'wholesale_retail'], true))
                                <td class="text-center font-numeric font-black text-amber-700">
                                    {{ number_format($prod->wholesale_price, 2) }} ج.م
                                </td>
                            @endif
                            @if(in_array($priceType, ['all', 'retail', 'wholesale_retail'], true))
                                <td class="text-center font-numeric font-bold text-blue-700">
                                    {{ number_format($prod->retail_price, 2) }} ج.م
                                </td>
                            @endif
                            @if(in_array($priceType, ['all', 'distributor'], true))
                                <td class="text-center font-numeric font-bold text-purple-700">
                                    {{ number_format($prod->distributor_price, 2) }} ج.م
                                </td>
                            @endif
                            @if($showStock)
                                <td class="text-center font-numeric text-xs font-bold {{ $stock > 0 ? 'text-emerald-600' : 'text-danger' }}">
                                    {{ number_format($stock, 0) }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                لا توجد منتجات مسجلة مطابقة للخيارات المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Packing Notes & Terms -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-box w-full">
                    <div class="dispatch-notes-title">شروط وملاحظات التوريد والطلب</div>
                    <div class="dispatch-notes-content">
                        • الأسعار الموضحة بالجنيه المصري ومطابقة لأحدث تحديث تسعير للعملاء.<br>
                        • يرجى تأكيد الكميات والتعبئة مع مسؤولي المبيعات قبل إتمام أوامر الشحن والتوصيل.<br>
                        • يتوفر شحن لكافة المحافظات عبر مكاتب الشحن المعتمدة.
                    </div>
                </div>
            </div>

            <!-- 4. Signatures Bar -->
            <footer class="dispatch-footer-bar">
                <div class="dispatch-footer-box">
                    إدارة المبيعات والتوزيع
                </div>
                <div class="dispatch-footer-box">
                    إجمالي الأصناف: {{ $products->count() }} صنف
                </div>
                <div class="dispatch-footer-box font-numeric">
                    تاريخ ووقت الطباعة: {{ now()->format('Y-m-d H:i') }}
                </div>
            </footer>
        </article>
    </main>

</body>
</html>
