<!DOCTYPE html>
<html lang="ar" dir="rtl" class="{{ $colorClass ?? 'emerald' }}">
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
@php
    $designerSettings = $settings ?? $template?->settings ?? [];
@endphp

    <!-- Screen-Only Navigation & Print Actions Toolbar -->
    <header class="print-toolbar no-print">
        <div class="toolbar-info">
            <span class="toolbar-title">
                <x-icon name="printer" size="18" />
                <span>طباعة فاتورة شراء: {{ $invoice->invoice_number }}</span>
            </span>

            <div class="toolbar-formats flex items-center gap-2">
                <select class="toolbar-format-select" onchange="window.location.href = this.value" style="font-family: inherit; font-size: 12px; font-weight: bold; padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; color: #1e293b;">
                    <option value="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" {{ $format === 'thermal_80mm' ? 'selected' : '' }}>
                        🖨️ إيصال حراري (80 مم) - XP-80C
                    </option>
                    <option value="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" {{ $format === 'a4' ? 'selected' : '' }}>
                        📄 فاتورة قياسية (A4)
                    </option>
                    <option value="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" {{ $format === 'a5' ? 'selected' : '' }}>
                        📑 فاتورة مدمجة (A5)
                    </option>
                </select>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'thermal_80mm']) }}" class="toolbar-format-btn {{ $format === 'thermal_80mm' ? 'active' : '' }}">
                    80 مم حراري
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a4']) }}" class="toolbar-format-btn {{ $format === 'a4' ? 'active' : '' }}">
                    A4 قياسي
                </a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'a5']) }}" class="toolbar-format-btn {{ $format === 'a5' ? 'active' : '' }}">
                    A5 وسط
                </a>
            </div>

            <span class="toolbar-badge primary font-numeric">
                {{ $invoice->items->count() }} بنود · {{ number_format((float)$invoice->total_amount, 2) }} ج.م
            </span>
        </div>

        <div class="toolbar-actions">
            <button type="button" data-action="print-now" class="toolbar-btn toolbar-btn-primary" title="طباعة فورية عبر المتصفح">
                <x-icon name="printer" size="16" />
                <span>{{ $format === 'thermal_80mm' ? 'طباعة الإيصال (XP-80C) 🖨️' : 'طباعة الفاتورة 🖨️' }}</span>
            </button>
            <button type="button" data-action="python-direct-print" data-url="{{ route('purchases.direct-print', [$invoice, 'format' => $format]) }}" class="toolbar-btn toolbar-btn-secondary" title="إرسال مباشر عبر بايثون للسيرفر">
                <x-icon name="zap" size="14" />
                <span>طباعة بايثون (خادم)</span>
            </button>
            <button type="button" data-action="close-window" class="toolbar-btn toolbar-btn-secondary">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- Document Paper Sheet Container -->
    <main class="invoice-page-container">
        <article 
            class="invoice-sheet" 
            data-format="{{ $format }}" 
            data-density="{{ $template->getSetting('table_columns.density', 'comfortable') }}"
            data-font-size="{{ $template->getSetting('general.font_size', 'md') }}"
        >
        @php
            $companyLogo = $template->getSetting('general.custom_logo') ?: ($company?->logo_url ?? null);
        @endphp

        @if($format !== 'thermal_80mm')
            <!-- =================================================================
                 A4 / A5 Enterprise Dispatch Invoice Layout (مطابق لصورة الفاتورة الفعلية)
                 ================================================================= -->
            
            @if($template->getSetting('general.show_logo') || $template->getSetting('general.show_company_name'))
                <div class="dispatch-company-branding preview-header-brand">
                    <div class="flex items-center gap-3">
                        @if($template->getSetting('general.show_logo') && $companyLogo)
                            <div class="preview-logo doc-mockup-logo-{{ $template->getSetting('general.logo_size', 'md') }}">
                                <img src="{{ $companyLogo }}" alt="Logo" class="preview-logo-img" />
                            </div>
                        @endif
                        <div class="preview-company-details">
                            @if($template->getSetting('general.show_company_name'))
                                <div class="preview-company-name font-bold text-sm">{{ $company?->company_name ?? 'شركة كيان للتجارة والتوزيع' }}</div>
                            @endif
                            @if($template->getSetting('general.show_company_phone') && !empty($company?->company_phone))
                                <div class="preview-company-phone text-xs text-slate-600">هاتف: {{ $company->company_phone }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- 1. Header: 3 Columns (Document & Supplier Info, Stamp Box, Date & Warehouse) -->
            <header class="dispatch-header">
                <!-- Right Column: Document Number & Supplier Info -->
                <div class="dispatch-col-right">
                    <div class="dispatch-badge-original">Original</div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">رقم المستند :</span>
                        <strong class="dispatch-field-val font-numeric">{{ $invoice->invoice_number }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">كود المورد :</span>
                        <span class="dispatch-field-val font-numeric">{{ $invoice->supplier->code ?? $invoice->supplier->id ?? '—' }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">اسم المورد :</span>
                        <strong class="dispatch-field-val">{{ $invoice->supplier->name ?? 'مورد نقدي' }}</strong>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">رقم التليفون :</span>
                        <span class="dispatch-field-val font-numeric">{{ $invoice->supplier->phone ?? '—' }}</span>
                    </div>
                </div>

                <!-- Center Column: Official Stamp Box -->
                <div class="dispatch-col-center">
                    <div class="dispatch-stamp-frame">
                        <span class="stamp-stars">★★★★★</span>
                        <span class="stamp-title">{{ data_get($designerSettings, 'general.custom_title') ?: 'إذن استلام' }}</span>
                        @if($sub = data_get($designerSettings, 'general.custom_subtitle'))
                            <span class="stamp-sub-title">{{ $sub }}</span>
                        @else
                            <span class="stamp-sub-title">فاتورة شراء وتوريد</span>
                        @endif
                        <span class="stamp-date font-numeric">{{ \Carbon\Carbon::parse($invoice->invoice_date ?? now())->format('d/m/Y') }}</span>
                        <span class="stamp-stars">★★★★★</span>
                    </div>
                </div>

                <!-- Left Column: Date & Time, Address, Receiving Warehouse -->
                <div class="dispatch-col-left">
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">التاريخ :</span>
                        <span class="dispatch-field-val font-numeric">{{ \Carbon\Carbon::parse($invoice->invoice_date ?? $invoice->created_at ?? now())->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">العنوان :</span>
                        <span class="dispatch-field-val">{{ $invoice->supplier->address ?? ($invoice->warehouse->name ?? 'الجيزة/مصر') }}</span>
                    </div>
                    <div class="dispatch-field">
                        <span class="dispatch-field-label">المستودع المستلم :</span>
                        <span class="dispatch-field-val">{{ $invoice->warehouse->name ?? 'المستودع الرئيسي' }}</span>
                    </div>
                </div>
            </header>

            <!-- 2. High-Contrast Solid Grid Table (8 أعمدة مطابقة للصورة تماماً) -->
            <table class="dispatch-table">
                <thead>
                    <tr>
                        <th class="col-seq">م</th>
                        <th class="col-sku">كود الصنف</th>
                        <th class="col-name">اسم الصنف</th>
                        <th class="col-qty">الكمية</th>
                        <th class="col-unit">وحده القياس</th>
                        <th class="col-wh">المخزن</th>
                        <th class="col-price">السعر</th>
                        <th class="col-total">الاجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $idx => $item)
                        <tr>
                            <td class="col-seq font-numeric">{{ $idx + 1 }}</td>
                            <td class="col-sku font-numeric">{{ $item->product->sku ?? $item->product->barcode ?? '—' }}</td>
                            <td class="col-name">
                                <strong>{{ $item->product->name ?? 'صنف غير محدد' }}</strong>
                            </td>
                            <td class="col-qty font-numeric">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format((float)$item->quantity, 2) }}</td>
                            <td class="col-unit">{{ $item->unit->name ?? $item->product->unit ?? 'قطعة' }}</td>
                            <td class="col-wh font-numeric">{{ $invoice->warehouse->code ?? $invoice->warehouse->id ?? '20' }}</td>
                            <td class="col-price font-numeric">{{ number_format((float)($item->unit_cost ?? $item->unit_price ?? 0), 2) }}</td>
                            <td class="col-total font-numeric">{{ number_format((float)$item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 3. Notes & Financial Totals Section -->
            <div class="dispatch-bottom-section">
                <div class="dispatch-notes-area">
                    <div class="dispatch-notes-header">ملاحظات</div>
                    <div class="dispatch-notes-body font-numeric">
                        {{ $invoice->notes ?: ($invoice->creator ? 'ع م *' . $invoice->creator->name . ' *استلام معتمد' : 'فاتورة مشتريات وتوريد معتمدة') }}
                    </div>
                </div>

                <div class="dispatch-totals-area">
                    <table class="dispatch-totals-table font-numeric">
                        <tr>
                            <td class="tot-label">المجموع الفرعي:</td>
                            <td class="tot-val">{{ number_format((float)$invoice->subtotal, 2) }} ج.م</td>
                        </tr>
                        @if((float)$invoice->shipping_cost > 0)
                            <tr>
                                <td class="tot-label">تكلفة الشحن والتوصيل:</td>
                                <td class="tot-val">{{ number_format((float)$invoice->shipping_cost, 2) }} ج.م</td>
                            </tr>
                        @endif
                        <tr class="tot-grand">
                            <td class="tot-label">إجمالي الفاتورة:</td>
                            <td class="tot-val">{{ number_format((float)$invoice->total_amount, 2) }} ج.م</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 4. Bottom Info Bar (اسم المستخدم، الصفحات، تاريخ ووقت الطباعة) -->
            <div class="dispatch-footer-bar">
                <div class="footer-box box-user">
                    <span class="box-label">اسم المستخدم :</span>
                    <strong class="box-val">{{ $invoice->creator->name ?? auth()->user()?->name ?? 'مسؤول المشتريات' }}</strong>
                </div>
                <div class="footer-box box-pages">
                    <span>الصفحات من 1 إلي 1</span>
                </div>
                <div class="footer-box box-time">
                    <span class="box-label">تاريخ ووقت الطباعة :</span>
                    <span class="box-val font-numeric">{{ now()->format('d/m/Y h:i a') }}</span>
                </div>
            </div>

        @else
            <!-- Thermal 80mm Layout -->
            <header class="doc-header">
                <div class="doc-brand">
                    @if($template->getSetting('general.show_logo') && $companyLogo)
                        <div class="preview-logo doc-mockup-logo-{{ $template->getSetting('general.logo_size', 'sm') }} mb-1">
                            <img src="{{ $companyLogo }}" alt="Logo" class="preview-logo-img" />
                        </div>
                    @endif
                    <div>
                        <h1 class="doc-company-title">{{ $company?->company_name ?: 'شركة كيان التجارية' }}</h1>
                    </div>
                </div>
                <div class="doc-title-block">
                    <h2 class="doc-main-title">فاتورة شراء وتوريد</h2>
                    <div class="font-numeric font-bold text-sm">{{ $invoice->invoice_number }}</div>
                </div>
            </header>

            <section class="doc-meta-section">
                <div class="doc-panel">
                    <div class="doc-info-list font-numeric">
                        <div class="doc-info-row">
                            <span class="doc-info-label">التاريخ:</span>
                            <span class="doc-info-val">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="doc-info-row">
                            <span class="doc-info-label">المورد:</span>
                            <span class="doc-info-val">{{ $invoice->supplier->name ?? 'مورد' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <table class="doc-table">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th class="doc-table-num">الكمية</th>
                        <th class="doc-table-price">السعر</th>
                        <th class="doc-table-price">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name ?? 'صنف' }}</strong>
                            </td>
                            <td class="doc-table-num font-numeric">{{ (float)$item->quantity }}</td>
                            <td class="doc-table-price font-numeric">{{ number_format((float)($item->unit_cost ?? $item->unit_price ?? 0), 2) }}</td>
                            <td class="doc-table-price font-numeric">{{ number_format((float)$item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="doc-summary-section">
                <table class="doc-summary-table font-numeric">
                    <tr class="doc-grand-total">
                        <td class="doc-summary-label">الإجمالي النهائي:</td>
                        <td class="doc-summary-val">{{ number_format((float)$invoice->total_amount, 2) }} ج.م</td>
                    </tr>
                </table>
            </section>

            <div class="doc-footer-note font-numeric">
                سند استلام بضاعة · {{ now()->format('Y-m-d H:i') }}
            </div>
        @endif
        </article>
    </main>

</body>
</html>
