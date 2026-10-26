<!DOCTYPE html>
<html lang="ar" dir="rtl" data-size="{{ $size ?? '38x25' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'طباعة ملصقات الباركود - كيان ERP' }}</title>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('fonts/cairo/cairo.css') }}">

    @vite(['resources/css/print/thermal-label.css', 'resources/js/pages/print-label.js'])
</head>
<body data-size="{{ $size ?? '38x25' }}" data-width-mm="{{ $widthMm }}" data-height-mm="{{ $heightMm }}" data-auto-print="{{ !empty($autoPrint) ? 'true' : 'false' }}">

    <!-- SCREEN-ONLY CONTROL TOOLBAR -->
    <header class="preview-toolbar no-print">
        <div class="preview-info">
            <span class="preview-title preview-title-wrap">
                <x-icon name="printer" size="16" />
                <span>طباعة ملصقات الباركود</span>
            </span>
            <span class="preview-badge preview-badge-primary">
                <span>المقاس: {{ $widthMm }}×{{ $heightMm }} مم ({{ $barcodeSettings->printer_name ?? 'طابعة الباركود' }})</span>
            </span>
            <div class="preview-btn-group">
                @php
                    $dbSize = ($barcodeSettings->label_width_mm ?? 38) . 'x' . ($barcodeSettings->label_height_mm ?? 15);
                    $currentSize = $size ?? $dbSize;
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['size' => $dbSize]) }}" class="btn {{ $currentSize === $dbSize ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    المقاس من الإعدادات ({{ $barcodeSettings->label_width_mm ?? 38 }}×{{ $barcodeSettings->label_height_mm ?? 15 }} مم)
                </a>
                @if($dbSize !== '38x25')
                <a href="{{ request()->fullUrlWithQuery(['size' => '38x25']) }}" class="btn {{ $currentSize === '38x25' ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    38×25 مم
                </a>
                @endif
                @if($dbSize !== '38x15')
                <a href="{{ request()->fullUrlWithQuery(['size' => '38x15']) }}" class="btn {{ $currentSize === '38x15' ? 'btn-primary' : 'btn-secondary' }} btn-preset-size">
                    38×15 مم
                </a>
                @endif
                <a href="{{ route('settings.index', ['tab' => 'barcode']) }}" class="btn btn-secondary btn-preset-settings" title="تعديل مقاسات وإعدادات الباركود">
                    <x-icon name="settings" size="13" />
                    <span>إعدادات المقاسات</span>
                </a>
            </div>
            <span class="preview-badge">
                <span>الأصناف: {{ count($items) }}</span>
            </span>
            @if(count($items) === 1)
            <div class="inline-flex items-center bg-black/20 border border-white/20 rounded p-0.5 mx-1" title="تعديل عدد النسخ">
                <button type="button" id="previewQtyMinus" class="btn btn-secondary font-bold text-sm min-w-[26px] h-[26px] py-0.5 px-2">−</button>
                <input type="number" id="previewLabelQty" value="{{ $totalQuantity }}" min="1" max="500" class="w-12 text-center p-0.5 font-bold border-0 bg-transparent text-white text-sm" title="عدد النسخ">
                <button type="button" id="previewQtyPlus" class="btn btn-secondary font-bold text-sm min-w-[26px] h-[26px] py-0.5 px-2">+</button>
            </div>
            @endif
            <span class="preview-badge preview-badge-qty">
                <span id="previewTotalLabelsText">الملصقات: {{ $totalQuantity }} ملصق</span>
            </span>
        </div>

        <div class="preview-actions">
            @php
                $isSingle = count($items) === 1 && isset($items[0]['product']);
                $directUrl = $isSingle 
                    ? route('products.direct-print', $items[0]['product']) 
                    : route('products.direct-print-batch');
                $itemsJson = json_encode(array_values(array_map(function($item) {
                    return [
                        'id' => (int) $item['product']->id,
                        'qty' => (int) $item['quantity'],
                    ];
                }, $items)));
            @endphp
            <button type="button" 
                    data-action="direct-thermal-print" 
                    data-url="{{ $directUrl }}" 
                    data-is-batch="{{ $isSingle ? '0' : '1' }}"
                    data-items="{{ $itemsJson }}"
                    data-quantity="{{ $totalQuantity }}"
                    data-size="{{ $currentSize }}"
                    class="btn btn-primary btn-inline-icon" 
                    id="directPrintButton"
                    title="إرسال مباشر إلى طابعة الباركود الحرارية بدون نوافذ المتصفح">
                <x-icon name="printer" size="16" />
                <span>طباعة حرارية فورية ({{ $totalQuantity }} ملصق)</span>
            </button>
            <button type="button" data-action="print-now" class="btn btn-secondary btn-inline-icon" id="printButton" title="طباعة عبر المتصفح كخيار بديل">
                <x-icon name="printer" size="14" />
                <span>طباعة عبر المتصفح</span>
            </button>
            <button type="button" data-action="close-window" class="btn btn-secondary btn-inline-icon">
                <x-icon name="x" size="14" />
                <span>إغلاق</span>
            </button>
        </div>
    </header>

    <!-- SCREEN-ONLY INSTRUCTIONS FOR THERMAL PRINTER SETUP -->
    <div class="preview-tips no-print">
        <div class="preview-tips-content">
            <span class="preview-tips-title">
                <x-icon name="info" size="14" />
                <span>نصيحة للطباعة:</span>
            </span>
            <span>في نافذة الطابعة، اختر طابعة <strong>{{ $barcodeSettings->printer_name ?? 'Xprinter XP-233B' }}</strong> بمقاس <strong>{{ $widthMm }}x{{ $heightMm }}mm</strong> والهوامش على <strong>None (بدون هوامش)</strong>.</span>
        </div>
        <button type="button" data-action="dismiss-tip" class="preview-tips-close" title="إغلاق التنبيه">
            <x-icon name="x" size="14" />
        </button>
    </div>

    <!-- MAIN LABELS CONTAINER -->
    <main class="roll-stage">
        <div class="roll-grid">
            @php $labelIndex = 1; @endphp
            @foreach($items as $item)
                @for($copy = 1; $copy <= $item['quantity']; $copy++)
                    <div class="label-card-preview">
                        <span class="card-index">{{ $labelIndex }}</span>
                        <div class="thermal-label">
                            {!! $item['label_svg'] !!}
                        </div>
                    </div>
                    @php $labelIndex++; @endphp
                @endfor
            @endforeach
        </div>
    </main>

    <!-- Toast Notification for Direct Print Feedback -->
    <div id="printNotificationToast" class="erp-toast hidden">
        <span class="erp-toast-icon"><x-icon name="printer" size="18" /></span>
        <span id="printToastMessage" class="erp-toast-message">جاري إرسال الملصقات للطباعة...</span>
    </div>

</body>
</html>
