@props([
    'targetInputId' => 'productBarcode',
])

<!-- Barcode Camera Scanner Modal -->
<div id="kayanBarcodeScannerModal" class="erp-modal-overlay erp-scanner-modal-overlay" data-target-input="{{ $targetInputId }}" aria-hidden="true">
    <div class="erp-modal-container erp-scanner-modal-container">
        <div class="erp-modal-header erp-scanner-modal-header">
            <div class="erp-modal-title">
                <x-icon name="camera" size="18" />
                <span>مسح الباركود بالكاميرا</span>
            </div>
            <button type="button" class="erp-modal-close" data-action="close-barcode-scanner" title="إغلاق">✕</button>
        </div>

        <div class="erp-modal-body erp-scanner-body">
            <!-- Camera Viewport Box -->
            <div id="scannerViewport" class="erp-scanner-viewport">
                <!-- Live Video Feed -->
                <video id="kayanBarcodeVideo" playsinline autoplay muted class="erp-scanner-video"></video>

                <!-- Scanning Aim Reticle & Laser -->
                <div id="scannerReticle" class="erp-scanner-reticle">
                    <div class="erp-scanner-laser"></div>
                </div>

                <!-- Scanner Status / Hint Overlay -->
                <div id="scannerStatusBadge" class="erp-scanner-badge">
                    وجّه الكاميرا نحو باركود الصنف
                </div>
            </div>

            <!-- Permission or Error Notice -->
            <div id="scannerErrorMessage" class="erp-scanner-error"></div>

            <!-- Controls and Fallback Options -->
            <div class="erp-scanner-controls">
                <button type="button" id="btnSwitchCamera" data-action="switch-scanner-camera" class="erp-btn erp-btn-secondary text-xs">
                    <x-icon name="arrow-left-right" size="14" />
                    <span>تبديل الكاميرا</span>
                </button>

                <!-- Native Camera Snap Fallback for Devices without live BarcodeDetector -->
                <label id="barcodeSnapBtn" class="erp-btn erp-btn-secondary cursor-pointer text-xs">
                    <x-icon name="camera" size="14" />
                    <span>التقاط صورة للباركود (كاميرا الهاتف)</span>
                    <input type="file" id="barcodeFileCapture" accept="image/*" capture="environment" class="hidden">
                </label>
            </div>
        </div>

        <div class="erp-modal-footer erp-scanner-footer">
            <span class="erp-scanner-footer-hint">
                يدعم EAN-13, Code-128, QR, UPC
            </span>
            <button type="button" data-action="close-barcode-scanner" class="erp-btn erp-btn-secondary">إلغاء</button>
        </div>
    </div>
</div>
