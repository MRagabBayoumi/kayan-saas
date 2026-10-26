/**
 * Kayan ERP — Settings Page Interactions
 * Logo upload live preview, barcode sticker live preview engine, preset buttons
 */

export function initSettingsPage() {
    const page = document.querySelector('[data-page="settings"]');
    if (!page) return;

    // 1. Live Logo Image Preview (Company Tab)
    const fileInput = page.querySelector('#companyLogoInput');
    const previewImg = page.querySelector('#logoPreviewImg');
    const placeholder = page.querySelector('#logoPlaceholder');

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (file) {
                const objectUrl = URL.createObjectURL(file);
                if (previewImg) {
                    previewImg.src = objectUrl;
                    previewImg.classList.remove('hidden');
                    previewImg.style.display = 'block';
                }
                if (placeholder) {
                    placeholder.classList.add('hidden');
                    placeholder.style.display = 'none';
                }
            }
        });
    }

    // 2. Program Status Radio Card Styling
    const statusInputs = page.querySelectorAll('input[name="program_status"]');
    const activeCard = page.querySelector('#cardStatusActive');
    const maintCard = page.querySelector('#cardStatusMaintenance');

    function updateStatusCards(val) {
        if (val === 'maintenance') {
            activeCard?.classList.remove('selected');
            maintCard?.classList.add('selected');
        } else {
            activeCard?.classList.add('selected');
            maintCard?.classList.remove('selected');
        }
    }

    statusInputs.forEach(input => {
        input.addEventListener('change', () => {
            updateStatusCards(input.value);
        });
    });

    // 3. Live Barcode Sticker Preview Engine (Barcode Tab)
    if (page.querySelector('#barcodeSettingsForm')) {
        const inputWidth = page.querySelector('#label_width_mm') || page.querySelector('#labelWidth');
        const inputHeight = page.querySelector('#label_height_mm') || page.querySelector('#labelHeight');
        const inputBarcodeW = page.querySelector('#barcode_width_px');
        const inputBarcodeH = page.querySelector('#barcode_height_px') || page.querySelector('#barcodeHeight');
        const inputProdFont = page.querySelector('#product_font_size') || page.querySelector('#labelFontSize');
        const inputDetFont = page.querySelector('#details_font_size');

        const cbCompany = page.querySelector('#show_company_name') || page.querySelector('#showCompanyName');
        const companyNameOverride = page.querySelector('#company_name_override');
        const cbProdName = page.querySelector('#show_product_name') || page.querySelector('#showProductName');
        const cbBarcodeBars = page.querySelector('#show_barcode_bars');
        const cbSku = page.querySelector('#show_item_sku') || page.querySelector('#showItemCode');

        const frame = page.querySelector('#liveStickerFrame');
        const badge = page.querySelector('#previewDimensionsBadge');
        const compEl = page.querySelector('#previewCompanyText') || page.querySelector('#stickerPreviewCompany');
        const prodEl = page.querySelector('#previewProductText') || page.querySelector('#stickerPreviewProductName');
        const barsEl = page.querySelector('#previewBarcodeBars') || page.querySelector('#stickerPreviewBarcodeSvg');
        const skuSpan = page.querySelector('#previewSkuSpan') || page.querySelector('#stickerPreviewItemCode');
        const priceSpan = page.querySelector('#previewPriceSpan') || page.querySelector('#stickerPreviewPrice');
        const bottomEl = page.querySelector('#previewBottomDetails');

        function updateLiveSticker() {
            if (!frame) return;

            const widthMm = parseInt(inputWidth?.value || 38, 10);
            const heightMm = parseInt(inputHeight?.value || 25, 10);
            const prodFont = parseInt(inputProdFont?.value || 14, 10);
            const detFont = parseInt(inputDetFont?.value || 8, 10);

            const showCompany = cbCompany?.checked ?? true;
            const companyName = companyNameOverride?.value || 'شركة كيان للتجارة والتوزيع';
            const showProd = cbProdName?.checked ?? true;
            const showBars = cbBarcodeBars?.checked ?? true;
            const showSku = cbSku?.checked ?? true;

            let priceType = 'none';
            const priceChecked = page.querySelector('input[name="print_price_type"]:checked');
            if (priceChecked) priceType = priceChecked.value;

            if (badge) badge.textContent = `${widthMm}×${heightMm} مم`;

            const frameWidthPx = 240;
            const frameHeightPx = Math.round((heightMm / widthMm) * frameWidthPx);
            frame.style.width = frameWidthPx + 'px';
            frame.style.height = Math.max(65, frameHeightPx) + 'px';

            const svgEl = page.querySelector('#previewBarcodeSvg');
            if (svgEl) {
                const barHeight = parseInt(inputBarcodeH?.value || 40, 10);
                const barWidth = parseInt(inputBarcodeW?.value || 260, 10);
                const scaledSvgHeight = Math.max(14, Math.min(80, Math.round(barHeight * (frameHeightPx / 115))));
                const scaledSvgWidthPercent = Math.min(95, Math.max(40, Math.round((barWidth / 304) * 90)));
                svgEl.setAttribute('height', scaledSvgHeight);
                svgEl.style.width = scaledSvgWidthPercent + '%';
            }

            if (compEl) {
                compEl.style.display = showCompany ? 'block' : 'none';
                compEl.textContent = companyName;
                compEl.style.fontSize = Math.max(7, Math.min(22, Math.round(detFont * 0.95))) + 'px';
            }

            if (prodEl) {
                prodEl.style.display = showProd ? 'block' : 'none';
                prodEl.style.fontSize = Math.max(8, Math.min(26, prodFont)) + 'px';
            }

            if (barsEl) {
                barsEl.style.display = showBars ? 'flex' : 'none';
            }

            if (skuSpan) {
                skuSpan.style.display = showSku ? 'inline' : 'none';
            }

            if (priceSpan) {
                if (priceType === 'retail') {
                    priceSpan.textContent = (showSku ? ' | ' : '') + 'السعر: 35.00 ج.م';
                    priceSpan.style.display = 'inline';
                } else if (priceType === 'wholesale') {
                    priceSpan.textContent = (showSku ? ' | ' : '') + 'جملة: 22.00 ج.م';
                    priceSpan.style.display = 'inline';
                } else if (priceType === 'both') {
                    priceSpan.textContent = (showSku ? ' | ' : '') + 'تجزئة 35 | جملة 22';
                    priceSpan.style.display = 'inline';
                } else {
                    priceSpan.textContent = '';
                    priceSpan.style.display = 'none';
                }
            }

            if (bottomEl) {
                bottomEl.style.display = (showSku || priceType !== 'none') ? 'block' : 'none';
                bottomEl.style.fontSize = Math.max(6, Math.min(20, detFont)) + 'px';
            }
        }

        const previewFields = [
            inputWidth, inputHeight, inputBarcodeW, inputBarcodeH,
            inputProdFont, inputDetFont, companyNameOverride,
            cbCompany, cbProdName, cbBarcodeBars, cbSku
        ];

        previewFields.forEach(el => {
            el?.addEventListener('input', updateLiveSticker);
            el?.addEventListener('change', updateLiveSticker);
        });

        page.querySelectorAll('input[name="print_price_type"]').forEach(r => {
            r.addEventListener('change', updateLiveSticker);
        });

        // Barcode Presets
        page.querySelectorAll('[data-action="apply-barcode-preset"]').forEach(btn => {
            btn.addEventListener('click', () => {
                page.querySelectorAll('[data-action="apply-barcode-preset"]').forEach(b => {
                    b.classList.remove('erp-btn-primary');
                    b.classList.add('erp-btn-secondary');
                });
                btn.classList.remove('erp-btn-secondary');
                btn.classList.add('erp-btn-primary');

                if (inputWidth) inputWidth.value = btn.dataset.width || 38;
                if (inputHeight) inputHeight.value = btn.dataset.height || 25;
                if (inputBarcodeW) inputBarcodeW.value = btn.dataset.barW || 260;
                if (inputBarcodeH) inputBarcodeH.value = btn.dataset.barH || 80;
                if (inputProdFont) inputProdFont.value = btn.dataset.prodFont || 14;
                if (inputDetFont) inputDetFont.value = btn.dataset.detFont || 8;
                updateLiveSticker();
            });
        });

        updateLiveSticker();
    }

    // 4. Printers & Hardware Management Tab
    const printersContainer = page.querySelector('#printersSettingsContainer');
    if (printersContainer) {
        // A. Receipt Print Mode Radio Cards
        const modeInputs = printersContainer.querySelectorAll('input[name="receipt_print_mode"]');
        const cardBrowser = printersContainer.querySelector('#cardModeBrowser');
        const cardServer = printersContainer.querySelector('#cardModeServer');

        function updateModeCards(val) {
            if (val === 'server_python') {
                cardBrowser?.classList.remove('selected');
                cardServer?.classList.add('selected');
            } else {
                cardBrowser?.classList.add('selected');
                cardServer?.classList.remove('selected');
            }
        }

        modeInputs.forEach(input => {
            input.addEventListener('change', () => {
                updateModeCards(input.value);
            });
        });

        // B. Hardware Auto-Discovery
        const btnDiscover = printersContainer.querySelector('#btnDiscoverPrinters');
        const discoverIcon = printersContainer.querySelector('#discoverIcon');
        const discoverBtnText = printersContainer.querySelector('#discoverBtnText');
        const statusAlert = printersContainer.querySelector('#discoveryStatusAlert');
        const alertBox = printersContainer.querySelector('#discoveryAlertBox');
        const alertMsg = printersContainer.querySelector('#discoveryAlertMsg');
        const alertTime = printersContainer.querySelector('#discoveryTimestamp');
        const datalist = document.getElementById('systemPrintersList');

        if (btnDiscover) {
            btnDiscover.addEventListener('click', async () => {
                const url = printersContainer.dataset.discoverUrl;
                if (!url) return;

                btnDiscover.disabled = true;
                if (discoverBtnText) discoverBtnText.textContent = 'جاري فحص الطابعات...';
                if (discoverIcon) discoverIcon.classList.add('animate-spin');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (data.success && Array.isArray(data.printers)) {
                        if (datalist) {
                            datalist.innerHTML = '';
                            data.printers.forEach(pName => {
                                const opt = document.createElement('option');
                                opt.value = pName;
                                opt.textContent = pName;
                                datalist.appendChild(opt);
                            });
                        }

                        if (statusAlert && alertMsg && alertBox) {
                            alertBox.className = 'p-3 rounded-lg text-xs font-semibold flex items-center justify-between gap-2 bg-emerald-50 text-emerald-800 border border-emerald-200';
                            alertMsg.textContent = `تم العثور على ${data.count} طابعة متصلة بنظام ويندوز بنجاح.`;
                            if (alertTime) alertTime.textContent = new Date().toLocaleTimeString('ar-EG');
                            statusAlert.classList.remove('hidden');
                        }
                    } else {
                        throw new Error(data.message || 'لم يتم العثور على طابعات');
                    }
                } catch (err) {
                    if (statusAlert && alertMsg && alertBox) {
                        alertBox.className = 'p-3 rounded-lg text-xs font-semibold flex items-center justify-between gap-2 bg-rose-50 text-rose-800 border border-rose-200';
                        alertMsg.textContent = `تعذر فحص الطابعات: ${err.message || 'خطأ في الاتصال'}`;
                        if (alertTime) alertTime.textContent = new Date().toLocaleTimeString('ar-EG');
                        statusAlert.classList.remove('hidden');
                    }
                } finally {
                    btnDiscover.disabled = false;
                    if (discoverBtnText) discoverBtnText.textContent = 'فحص وتحديث الطابعات المتاحة';
                    if (discoverIcon) discoverIcon.classList.remove('animate-spin');
                }
            });
        }

        // C. Instant Test Print
        const testButtons = printersContainer.querySelectorAll('[data-action="test-print"]');
        testButtons.forEach(btn => {
            btn.addEventListener('click', async () => {
                const testUrl = printersContainer.dataset.testUrl;
                const type = btn.dataset.type;
                const targetSelector = btn.dataset.targetInput;
                const inputEl = targetSelector ? printersContainer.querySelector(targetSelector) : null;
                const printerName = inputEl ? inputEl.value.trim() : '';

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<span>جاري الإرسال...</span>`;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(testUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            type: type,
                            printer_name: printerName,
                        }),
                    });

                    const data = await res.json();
                    if (data.success) {
                        alert(`✅ ${data.message}`);
                    } else {
                        alert(`❌ فشل أمر الطباعة التجريبية: ${data.message || 'خطأ غير محدد'}`);
                    }
                } catch (err) {
                    alert(`❌ حدث خطأ أثناء إرسال أمر الطباعة: ${err.message || 'خطأ في الاتصال بالسيرفر'}`);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });
    }
}
