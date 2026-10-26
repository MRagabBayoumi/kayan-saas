/**
 * BayoSoft ERP — Invoice Print Controller
 * Handles Print Now, Close Window, and Automatic Trigger
 */

/**
 * Safely fetch with dynamic CSRF token refresh on HTTP 419 (CSRF Token Mismatch/Expired Session)
 */
async function fetchWithCsrfRetry(url, options = {}) {
    if (window.fetchWithCsrfRetry) {
        return window.fetchWithCsrfRetry(url, options);
    }
    options.headers = options.headers || {};
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    options.headers['X-CSRF-TOKEN'] = csrfToken;
    options.headers['Accept'] = 'application/json';

    let res = await fetch(url, options);
    if (res.status === 419) {
        try {
            const tokenRes = await fetch('/csrf-token', {
                headers: { 'Accept': 'application/json' },
            });
            if (tokenRes.ok) {
                const tokenData = await tokenRes.json();
                if (tokenData.token) {
                    csrfToken = tokenData.token;
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) {
                        meta.setAttribute('content', csrfToken);
                    }
                    options.headers['X-CSRF-TOKEN'] = csrfToken;
                    res = await fetch(url, options);
                }
            }
        } catch (e) {
            console.error('[CSRF] Failed to refresh token:', e);
        }
    }
    return res;
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Print Now Button
    const printNowBtn = document.querySelector('[data-action="print-now"]');
    if (printNowBtn) {
        printNowBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // 2. Close Window Button
    const closeBtn = document.querySelector('[data-action="close-window"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (window.opener || window.history.length <= 1) {
                window.close();
            } else {
                window.history.back();
            }
        });
    }

    // 3. Direct Python Print Button
    const directPrintBtns = document.querySelectorAll('[data-action="python-direct-print"]');
    directPrintBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const url = btn.dataset.url;
            if (!url) return;

            const originalHtml = btn.innerHTML;
            const originalDisabled = btn.disabled;
            btn.disabled = true;
            btn.innerHTML = `
                <svg style="animation: spin 1s linear infinite;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <span>جاري الإرسال للطابعة...</span>
            `;

            try {
                const bodyFormat = document.body?.dataset?.format || 'a4';
                const payload = {
                    format: btn.dataset.format || bodyFormat,
                    printer: btn.dataset.printer || undefined,
                };

                const res = await fetchWithCsrfRetry(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    btn.innerHTML = `
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>تمت الطباعة بنجاح ✓</span>
                    `;
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.disabled = originalDisabled;
                    }, 3000);
                } else {
                    console.warn('[DirectPrint] Python engine error or network printer unreachable, initiating browser print fallback:', data);
                    btn.innerHTML = `
                        <svg style="animation: spin 1s linear infinite;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        <span>جاري التحويل لطباعة المتصفح...</span>
                    `;
                    // Automatically trigger native browser print so printing never fails
                    setTimeout(() => {
                        window.print();
                        btn.innerHTML = originalHtml;
                        btn.disabled = originalDisabled;
                    }, 300);
                }
            } catch (err) {
                console.warn('[DirectPrint] Network connection error, initiating browser print fallback:', err);
                btn.innerHTML = `
                    <span>جاري التحويل لطباعة المتصفح...</span>
                `;
                setTimeout(() => {
                    window.print();
                    btn.innerHTML = originalHtml;
                    btn.disabled = originalDisabled;
                }, 300);
            }
        });
    });

    // 4. Auto-print if data-auto-print is enabled
    const body = document.body;
    if (body && body.dataset.autoPrint === 'true') {
        setTimeout(() => {
            window.print();
        }, 350);
    }
});
