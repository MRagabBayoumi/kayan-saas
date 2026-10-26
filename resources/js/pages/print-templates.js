/**
 * BayoSoft ERP — Print Template Designer Engine
 * Real-time DOM-reactive preview, paper format switching (A4, A5, Thermal 80mm),
 * instant logo upload/change, color palette sync, and AJAX configuration persistence.
 */

export function initPrintTemplatesPage() {
    const page = document.querySelector('[data-page="settings-print-templates"]');
    if (!page) return;

    const form = page.querySelector('#printTemplateForm');
    const paperSheet = page.querySelector('#paperMockupSheet');
    const resetBtn = page.querySelector('#btnResetTemplate');
    const templateSelect = page.querySelector('#templateSelector');

    // Logo & Color controls
    const logoInput = form?.querySelector('#template_logo_upload');
    const customLogoHidden = form?.querySelector('#input_custom_logo');
    const removeLogoBtn = form?.querySelector('#btn_remove_logo');
    const colorPicker = form?.querySelector('#template_color_picker');
    const primaryColorInput = form?.querySelector('#template_primary_color_input');

    // Parse initial data stored in script tag or data attributes
    let templateData = null;
    let sampleData = null;

    const dataScript = document.getElementById('printTemplateInitialData');
    if (dataScript) {
        try {
            const parsed = JSON.parse(dataScript.textContent);
            templateData = parsed.template;
            sampleData = parsed.sample;
        } catch (e) {
            console.error('Error parsing print template data script:', e);
        }
    }

    if (!templateData) {
        try {
            const rawTemplate = page.getAttribute('data-initial-template');
            const rawSample = page.getAttribute('data-initial-sample');
            if (rawTemplate) templateData = JSON.parse(rawTemplate);
            if (rawSample) sampleData = JSON.parse(rawSample);
        } catch (e) {
            console.error('Error parsing print template data attributes:', e);
        }
    }

    if (!templateData || !templateData.settings) {
        return;
    }

    let settings = JSON.parse(JSON.stringify(templateData.settings));

    /**
     * Accordion toggle interaction
     */
    const accordionHeaders = page.querySelectorAll('.designer-accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const body = header.nextElementSibling;
            if (body && body.classList.contains('designer-accordion-body')) {
                body.classList.toggle('collapsed');
                const icon = header.querySelector('.accordion-chevron');
                if (icon) {
                    icon.classList.toggle('rotate-180');
                }
            }
        });
    });

    /**
     * Bind form controls to settings object
     */
    function syncFormToSettings() {
        if (!form) return;

        // General
        settings.general = settings.general || {};
        settings.general.show_logo = form.querySelector('[name="settings[general][show_logo]"]')?.checked ?? true;
        settings.general.custom_logo = customLogoHidden?.value || settings.general.custom_logo || null;
        settings.general.logo_size = form.querySelector('[name="settings[general][logo_size]"]')?.value ?? 'md';
        settings.general.font_size = form.querySelector('[name="settings[general][font_size]"]')?.value ?? 'md';
        settings.general.show_company_name = form.querySelector('[name="settings[general][show_company_name]"]')?.checked ?? true;
        settings.general.show_company_phone = form.querySelector('[name="settings[general][show_company_phone]"]')?.checked ?? true;
        settings.general.show_company_address = form.querySelector('[name="settings[general][show_company_address]"]')?.checked ?? true;
        settings.general.custom_title = form.querySelector('[name="settings[general][custom_title]"]')?.value ?? '';
        settings.general.custom_subtitle = form.querySelector('[name="settings[general][custom_subtitle]"]')?.value ?? '';
        settings.general.primary_color = primaryColorInput?.value || colorPicker?.value || '#1e293b';

        // Document Meta
        settings.document_meta = settings.document_meta || {};
        settings.document_meta.show_doc_number = form.querySelector('[name="settings[document_meta][show_doc_number]"]')?.checked ?? true;
        settings.document_meta.show_doc_date = form.querySelector('[name="settings[document_meta][show_doc_date]"]')?.checked ?? true;
        settings.document_meta.show_due_date = form.querySelector('[name="settings[document_meta][show_due_date]"]')?.checked ?? false;
        settings.document_meta.show_warehouse = form.querySelector('[name="settings[document_meta][show_warehouse]"]')?.checked ?? true;
        settings.document_meta.show_status_badge = form.querySelector('[name="settings[document_meta][show_status_badge]"]')?.checked ?? true;
        settings.document_meta.show_barcode = form.querySelector('[name="settings[document_meta][show_barcode]"]')?.checked ?? true;
        settings.document_meta.show_qr_code = form.querySelector('[name="settings[document_meta][show_qr_code]"]')?.checked ?? true;

        // Party Meta
        settings.party_meta = settings.party_meta || {};
        settings.party_meta.party_type_label = form.querySelector('[name="settings[party_meta][party_type_label]"]')?.value ?? '';
        settings.party_meta.show_party_name = form.querySelector('[name="settings[party_meta][show_party_name]"]')?.checked ?? true;
        settings.party_meta.show_party_phone = form.querySelector('[name="settings[party_meta][show_party_phone]"]')?.checked ?? true;
        settings.party_meta.show_party_address = form.querySelector('[name="settings[party_meta][show_party_address]"]')?.checked ?? true;
        settings.party_meta.show_party_tax_no = form.querySelector('[name="settings[party_meta][show_party_tax_no]"]')?.checked ?? false;

        // Table Columns
        settings.table_columns = settings.table_columns || {};
        settings.table_columns.density = form.querySelector('[name="settings[table_columns][density]"]')?.value ?? 'comfortable';
        settings.table_columns.show_index = form.querySelector('[name="settings[table_columns][show_index]"]')?.checked ?? true;
        settings.table_columns.show_sku = form.querySelector('[name="settings[table_columns][show_sku]"]')?.checked ?? true;
        settings.table_columns.show_barcode = form.querySelector('[name="settings[table_columns][show_barcode]"]')?.checked ?? false;
        settings.table_columns.show_unit = form.querySelector('[name="settings[table_columns][show_unit]"]')?.checked ?? true;
        settings.table_columns.show_warehouse = form.querySelector('[name="settings[table_columns][show_warehouse]"]')?.checked ?? true;
        settings.table_columns.show_price = form.querySelector('[name="settings[table_columns][show_price]"]')?.checked ?? true;
        settings.table_columns.show_discount = form.querySelector('[name="settings[table_columns][show_discount]"]')?.checked ?? true;
        settings.table_columns.show_tax = form.querySelector('[name="settings[table_columns][show_tax]"]')?.checked ?? false;
        settings.table_columns.show_total = form.querySelector('[name="settings[table_columns][show_total]"]')?.checked ?? true;
        settings.table_columns.show_notes = form.querySelector('[name="settings[table_columns][show_notes]"]')?.checked ?? true;

        // Financial Summary
        settings.financial_summary = settings.financial_summary || {};
        settings.financial_summary.show_subtotal = form.querySelector('[name="settings[financial_summary][show_subtotal]"]')?.checked ?? true;
        settings.financial_summary.show_discount = form.querySelector('[name="settings[financial_summary][show_discount]"]')?.checked ?? true;
        settings.financial_summary.show_shipping = form.querySelector('[name="settings[financial_summary][show_shipping]"]')?.checked ?? true;
        settings.financial_summary.show_extra_expenses = form.querySelector('[name="settings[financial_summary][show_extra_expenses]"]')?.checked ?? false;
        settings.financial_summary.show_tax = form.querySelector('[name="settings[financial_summary][show_tax]"]')?.checked ?? false;
        settings.financial_summary.show_net_total = form.querySelector('[name="settings[financial_summary][show_net_total]"]')?.checked ?? true;
        settings.financial_summary.show_paid = form.querySelector('[name="settings[financial_summary][show_paid]"]')?.checked ?? true;
        settings.financial_summary.show_remaining = form.querySelector('[name="settings[financial_summary][show_remaining]"]')?.checked ?? true;

        // Notes and Terms
        settings.notes_and_terms = settings.notes_and_terms || {};
        settings.notes_and_terms.show_notes = form.querySelector('[name="settings[notes_and_terms][show_notes]"]')?.checked ?? true;
        settings.notes_and_terms.notes_text = form.querySelector('[name="settings[notes_and_terms][notes_text]"]')?.value ?? '';
        settings.notes_and_terms.show_terms = form.querySelector('[name="settings[notes_and_terms][show_terms]"]')?.checked ?? true;
        settings.notes_and_terms.terms_text = form.querySelector('[name="settings[notes_and_terms][terms_text]"]')?.value ?? '';

        // Signatures
        settings.signatures = settings.signatures || {};
        settings.signatures.show_signatures = form.querySelector('[name="settings[signatures][show_signatures]"]')?.checked ?? true;
        settings.signatures.first_party_title = form.querySelector('[name="settings[signatures][first_party_title]"]')?.value ?? '';
        settings.signatures.second_party_title = form.querySelector('[name="settings[signatures][second_party_title]"]')?.value ?? '';
        settings.signatures.show_stamp_box = form.querySelector('[name="settings[signatures][show_stamp_box]"]')?.checked ?? true;
    }

    /**
     * Render the live paper mockup
     */
    function renderMockup() {
        if (!paperSheet || !sampleData) return;

        const paperFormat = templateData.paper_format || 'a4';
        paperSheet.setAttribute('data-format', paperFormat);

        // Font size scaling
        const fontSize = settings.general?.font_size || 'md';
        paperSheet.setAttribute('data-font-size', fontSize);

        // Primary color
        const primaryColor = settings.general?.primary_color || '#1e293b';
        paperSheet.style.setProperty('--mockup-primary', primaryColor);

        // Active Logo (custom uploaded logo or company setting logo)
        const activeLogo = settings.general?.custom_logo || sampleData.company?.logo_url || null;
        const logoSize = settings.general?.logo_size || 'md';

        paperSheet.querySelectorAll('.preview-logo').forEach(logoEl => {
            logoEl.style.display = settings.general?.show_logo ? 'inline-flex' : 'none';
            logoEl.className = `preview-logo doc-mockup-logo-${logoSize}`;
            if (settings.general?.show_logo) {
                if (activeLogo) {
                    logoEl.innerHTML = `<img src="${activeLogo}" alt="Logo" class="preview-logo-img" />`;
                } else {
                    logoEl.innerHTML = `<div class="preview-logo-placeholder">LOGO</div>`;
                }
            }
        });

        // Company Details
        paperSheet.querySelectorAll('.preview-company-name').forEach(el => {
            el.style.display = settings.general?.show_company_name ? 'block' : 'none';
        });

        paperSheet.querySelectorAll('.preview-company-phone').forEach(el => {
            el.style.display = settings.general?.show_company_phone ? 'block' : 'none';
        });

        paperSheet.querySelectorAll('.preview-company-address').forEach(el => {
            el.style.display = settings.general?.show_company_address ? 'block' : 'none';
        });

        // Document Titles
        paperSheet.querySelectorAll('.preview-doc-title').forEach(titleEl => {
            titleEl.textContent = settings.general?.custom_title || sampleData.document?.type_label || 'فاتورة';
        });

        paperSheet.querySelectorAll('.preview-doc-subtitle').forEach(subtitleEl => {
            subtitleEl.textContent = settings.general?.custom_subtitle || '';
            subtitleEl.style.display = settings.general?.custom_subtitle ? 'block' : 'none';
        });

        // Document Meta
        paperSheet.querySelectorAll('.preview-doc-number').forEach(el => {
            el.style.display = settings.document_meta?.show_doc_number ? 'inline-block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-doc-number-row').forEach(el => {
            el.style.display = settings.document_meta?.show_doc_number ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-date').forEach(el => {
            el.style.display = settings.document_meta?.show_doc_date ? 'inline-block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-doc-date-row').forEach(el => {
            el.style.display = settings.document_meta?.show_doc_date ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-due-date').forEach(el => {
            el.style.display = settings.document_meta?.show_due_date ? 'inline-block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-doc-due-date-row').forEach(el => {
            el.style.display = settings.document_meta?.show_due_date ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-warehouse').forEach(el => {
            el.style.display = settings.document_meta?.show_warehouse ? 'inline-block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-doc-warehouse-row').forEach(el => {
            el.style.display = settings.document_meta?.show_warehouse ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-badge').forEach(el => {
            el.style.display = settings.document_meta?.show_status_badge ? 'inline-block' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-barcode').forEach(el => {
            el.style.display = settings.document_meta?.show_barcode ? 'block' : 'none';
        });

        paperSheet.querySelectorAll('.preview-doc-qr').forEach(el => {
            el.style.display = settings.document_meta?.show_qr_code ? 'block' : 'none';
        });

        // Party Meta
        paperSheet.querySelectorAll('.preview-party-label').forEach(partyLabelEl => {
            const rawLabel = settings.party_meta?.party_type_label || 'الطرف المقابل';
            partyLabelEl.textContent = partyLabelEl.textContent.includes(':') ? `${rawLabel} :` : rawLabel;
        });

        paperSheet.querySelectorAll('.preview-party-name').forEach(el => {
            el.style.display = settings.party_meta?.show_party_name ? 'block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-party-name-row').forEach(el => {
            el.style.display = settings.party_meta?.show_party_name ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-party-phone').forEach(el => {
            el.style.display = settings.party_meta?.show_party_phone ? 'block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-party-phone-row').forEach(el => {
            el.style.display = settings.party_meta?.show_party_phone ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-party-address').forEach(el => {
            el.style.display = settings.party_meta?.show_party_address ? 'block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-party-address-row').forEach(el => {
            el.style.display = settings.party_meta?.show_party_address ? 'flex' : 'none';
        });

        paperSheet.querySelectorAll('.preview-party-tax-no').forEach(el => {
            el.style.display = settings.party_meta?.show_party_tax_no ? 'block' : 'none';
        });
        paperSheet.querySelectorAll('.preview-party-tax-row').forEach(el => {
            el.style.display = settings.party_meta?.show_party_tax_no ? 'flex' : 'none';
        });

        // Table Columns & Density
        paperSheet.querySelectorAll('.doc-mockup-table').forEach(tbl => {
            tbl.classList.toggle('density-compact', settings.table_columns?.density === 'compact');
        });

        paperSheet.querySelectorAll('.col-index').forEach(el => el.style.display = settings.table_columns?.show_index ? '' : 'none');
        paperSheet.querySelectorAll('.col-sku').forEach(el => el.style.display = settings.table_columns?.show_sku ? '' : 'none');
        paperSheet.querySelectorAll('.col-barcode').forEach(el => el.style.display = settings.table_columns?.show_barcode ? '' : 'none');
        paperSheet.querySelectorAll('.col-unit').forEach(el => el.style.display = settings.table_columns?.show_unit ? '' : 'none');
        paperSheet.querySelectorAll('.col-warehouse, .col-wh').forEach(el => el.style.display = settings.table_columns?.show_warehouse ? '' : 'none');
        paperSheet.querySelectorAll('.col-price').forEach(el => el.style.display = settings.table_columns?.show_price ? '' : 'none');
        paperSheet.querySelectorAll('.col-discount').forEach(el => el.style.display = settings.table_columns?.show_discount ? '' : 'none');
        paperSheet.querySelectorAll('.col-tax').forEach(el => el.style.display = settings.table_columns?.show_tax ? '' : 'none');
        paperSheet.querySelectorAll('.col-total').forEach(el => el.style.display = settings.table_columns?.show_total ? '' : 'none');
        paperSheet.querySelectorAll('.col-notes').forEach(el => el.style.display = settings.table_columns?.show_notes ? '' : 'none');

        // Financial Summary
        paperSheet.querySelectorAll('.row-subtotal').forEach(el => el.style.display = settings.financial_summary?.show_subtotal ? '' : 'none');
        paperSheet.querySelectorAll('.row-discount').forEach(el => el.style.display = settings.financial_summary?.show_discount ? '' : 'none');
        paperSheet.querySelectorAll('.row-shipping').forEach(el => el.style.display = settings.financial_summary?.show_shipping ? '' : 'none');
        paperSheet.querySelectorAll('.row-extra-expenses').forEach(el => el.style.display = settings.financial_summary?.show_extra_expenses ? '' : 'none');
        paperSheet.querySelectorAll('.row-tax').forEach(el => el.style.display = settings.financial_summary?.show_tax ? '' : 'none');
        paperSheet.querySelectorAll('.row-net-total').forEach(el => el.style.display = settings.financial_summary?.show_net_total ? '' : 'none');
        paperSheet.querySelectorAll('.row-paid').forEach(el => el.style.display = settings.financial_summary?.show_paid ? '' : 'none');
        paperSheet.querySelectorAll('.row-remaining').forEach(el => el.style.display = settings.financial_summary?.show_remaining ? '' : 'none');

        // Notes and Terms
        paperSheet.querySelectorAll('.preview-notes-box').forEach(notesBox => {
            notesBox.style.display = settings.notes_and_terms?.show_notes ? 'block' : 'none';
            const notesTextEl = notesBox.querySelector('.notes-content');
            if (notesTextEl) notesTextEl.textContent = settings.notes_and_terms?.notes_text || '';
        });

        paperSheet.querySelectorAll('.preview-terms-box').forEach(termsBox => {
            termsBox.style.display = settings.notes_and_terms?.show_terms ? 'block' : 'none';
            const termsTextEl = termsBox.querySelector('.terms-content');
            if (termsTextEl) termsTextEl.textContent = settings.notes_and_terms?.terms_text || '';
        });

        // Signatures & Stamp
        paperSheet.querySelectorAll('.doc-mockup-signatures').forEach(sigContainer => {
            sigContainer.style.display = settings.signatures?.show_signatures ? 'flex' : 'none';
            const firstPartyLabel = sigContainer.querySelector('.first-party-title');
            if (firstPartyLabel) firstPartyLabel.textContent = settings.signatures?.first_party_title || 'الطرف الأول';

            const secondPartyLabel = sigContainer.querySelector('.second-party-title');
            if (secondPartyLabel) secondPartyLabel.textContent = settings.signatures?.second_party_title || 'المسؤول';
        });

        paperSheet.querySelectorAll('.preview-stamp-box, .doc-mockup-stamp-box').forEach(stampBox => {
            stampBox.style.display = settings.signatures?.show_stamp_box ? '' : 'none';
        });
    }

    // Logo upload interaction
    if (logoInput) {
        logoInput.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    showToast('يرجى اختيار ملف صورة صالح (PNG, JPG, SVG, WebP)', 'error');
                    return;
                }
                const reader = new FileReader();
                reader.onload = (loadEvent) => {
                    const dataUrl = loadEvent.target?.result;
                    if (dataUrl) {
                        if (customLogoHidden) customLogoHidden.value = dataUrl;
                        settings.general.custom_logo = dataUrl;
                        if (removeLogoBtn) removeLogoBtn.classList.remove('hidden');
                        renderMockup();
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', () => {
            if (logoInput) logoInput.value = '';
            if (customLogoHidden) customLogoHidden.value = '';
            settings.general.custom_logo = null;
            removeLogoBtn.classList.add('hidden');
            renderMockup();
        });
    }

    // Color picker sync
    if (colorPicker && primaryColorInput) {
        colorPicker.addEventListener('input', () => {
            primaryColorInput.value = colorPicker.value;
            settings.general.primary_color = colorPicker.value;
            renderMockup();
        });

        primaryColorInput.addEventListener('input', () => {
            const val = primaryColorInput.value.trim();
            if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(val)) {
                colorPicker.value = val;
                settings.general.primary_color = val;
            }
            renderMockup();
        });
    }

    // Attach listener to all inputs in the form
    if (form) {
        form.addEventListener('input', (e) => {
            if (e.target !== logoInput) {
                syncFormToSettings();
                renderMockup();
            }
        });

        form.addEventListener('change', (e) => {
            if (e.target !== logoInput) {
                syncFormToSettings();
                renderMockup();
            }
        });

        // Form submit via AJAX
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            syncFormToSettings();

            const submitBtns = [
                form.querySelector('button[type="submit"]'),
                document.querySelector('button[form="printTemplateForm"]')
            ].filter(Boolean);

            const originalStates = submitBtns.map(btn => ({ btn, html: btn.innerHTML }));
            submitBtns.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<span class="inline-flex items-center gap-1.5"><span class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span><span>جاري الحفظ...</span></span>';
            });

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        name: form.querySelector('[name="template_name"]')?.value || templateData.name,
                        is_default: form.querySelector('[name="template_is_default"]')?.checked ?? templateData.is_default,
                        settings: settings,
                    }),
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message || 'تم حفظ القالب بنجاح', 'success');
                } else {
                    showToast(result.message || 'حدث خطأ أثناء حفظ القالب', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('فشل الاتصال بالخادم أثناء الحفظ', 'error');
            } finally {
                submitBtns.forEach((btn, idx) => {
                    btn.disabled = false;
                    btn.innerHTML = originalStates[idx].html;
                });
            }
        });

        // Ensure external header button reliably triggers form submission
        const externalSubmitBtn = document.querySelector('button[form="printTemplateForm"]');
        if (externalSubmitBtn && !externalSubmitBtn.__bound) {
            externalSubmitBtn.__bound = true;
            externalSubmitBtn.addEventListener('click', (e) => {
                if (e.target.closest('#printTemplateForm')) return;
                e.preventDefault();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }
    }

    // Reset button handler
    if (resetBtn) {
        resetBtn.addEventListener('click', async () => {
            if (!confirm('هل أنت متأكد من استعادة الإعدادات الافتراضية لهذا القالب؟ ستفقد التعديلات المخصصة.')) {
                return;
            }

            const resetUrl = resetBtn.getAttribute('data-reset-url');
            if (!resetUrl) return;

            try {
                const response = await fetch(resetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();
                if (result.success && result.template) {
                    showToast(result.message, 'success');
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
                showToast('تعذر استعادة الإعدادات الافتراضية', 'error');
            }
        });
    }

    // Template selector change
    if (templateSelect) {
        templateSelect.addEventListener('change', () => {
            const selectedId = templateSelect.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('template_id', selectedId);
            window.location.href = currentUrl.toString();
        });
    }

    // Initial render
    syncFormToSettings();
    renderMockup();
}

function showToast(message, type = 'info') {
    let container = document.getElementById('erpGlobalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'erpGlobalToastContainer';
        container.style.cssText = 'position:fixed;bottom:24px;left:24px;z-index:999999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    const isError = type === 'error';
    const bg = isSuccess ? '#059669' : (isError ? '#dc2626' : '#1e293b');
    const icon = isSuccess ? '✓' : (isError ? '✕' : 'ℹ');

    toast.style.cssText = `
        display: flex !important;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        border-radius: 8px;
        background-color: ${bg};
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 600;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        animation: erpToastSlideUp 0.25s ease-out;
        pointer-events: auto;
        direction: rtl;
    `;
    toast.innerHTML = `<span style="font-size:16px;line-height:1;">${icon}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
