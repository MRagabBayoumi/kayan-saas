/**
 * Kayan ERP — Frontend Application Client (Single Page Application via Hotwire Turbo)
 * Odoo-Inspired Interactions, Instant Transitions, Theme & Density Engine
 */

import * as Turbo from '@hotwired/turbo';
import { initCommonUI } from './common.js';
import { initBarcodeScanner } from './components/barcode-scanner.js';
import { initProductForm } from './pages/products-form.js';
import { initProductsIndex } from './pages/products-index.js';
import { initProductShow } from './pages/products-show.js';
import { initInventoryPage } from './pages/inventory.js';
import { initWarehousesPage } from './pages/warehouses.js';
import { initSettingsPage } from './pages/settings.js';
import { initPrintLabelPage } from './pages/print-label.js';
import { initCustomersPage } from './pages/customers.js';
import { initSuppliersPage } from './pages/suppliers.js';
import { initShippingOfficesPage } from './pages/shipping-offices.js';
import { initPurchases } from './pages/purchases.js';
import { initSales } from './pages/sales.js';
import { initShifts } from './pages/shifts.js';
import { initPrintTemplatesPage } from './pages/print-templates.js';
import { initPortalPage } from './pages/portal.js';
import { initSalesRepsPage } from './pages/sales-reps.js';
import { initCustomerInstallments } from './pages/customer-installments.js';

// Expose Turbo globally
window.Turbo = Turbo;

// 1. Forward Laravel CSRF token in all Turbo background requests
document.addEventListener('turbo:before-fetch-request', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        event.detail.fetchOptions.headers['X-CSRF-TOKEN'] = token;
    }
});

// 2. Close mobile drawers and app launcher upon navigating to another route
document.addEventListener('turbo:before-visit', () => {
    if (typeof window.closeAppLauncher === 'function') {
        window.closeAppLauncher();
    }
    const sb = document.getElementById('erpSidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb && sb.classList.contains('open-mobile')) {
        sb.classList.remove('open-mobile');
        if (ov) ov.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// 3. Master Bootstrapper (Fires on initial page load and every Turbo SPA navigation)
document.addEventListener('turbo:load', () => {
    // A. One-time global listeners
    if (!window.__kayanGlobalBooted) {
        window.__kayanGlobalBooted = true;
        initKeyboardShortcuts();
        initDirectPythonPrint();
        initPwaInstallation();
        initCommonUI();
    }

    // B. Page & Navigation binders (Re-run on every SPA page change)
    initThemeAndDensity();
    initAppLauncher();
    initSidebar();
    initDropdowns();
    initGlobalSearch();
    initBarcodeScanner();
    initProductForm();
    initProductsIndex();
    initProductShow();
    initInventoryPage();
    initWarehousesPage();
    initSettingsPage();
    initPrintLabelPage();
    initCustomersPage();
    initSuppliersPage();
    initShippingOfficesPage();
    initPurchases();
    initSales();
    initShifts();
    initPrintTemplatesPage();
    initPortalPage();
    initSalesRepsPage();
    initCustomerInstallments();
});

/**
 * Theme & Density Control
 */
function initThemeAndDensity() {
    if (!window.__themeDensityDelegated) {
        window.__themeDensityDelegated = true;

        document.addEventListener('click', (e) => {
            const themeToggleBtn = e.target.closest('#kayanThemeToggle');
            if (themeToggleBtn) {
                e.preventDefault();
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('kayan_theme', newTheme);
                updateThemeIcons(newTheme);
                return;
            }

            const densityToggleBtn = e.target.closest('#kayanDensityToggle');
            if (densityToggleBtn) {
                e.preventDefault();
                const currentDensity = document.documentElement.getAttribute('data-density') || 'comfortable';
                const newDensity = currentDensity === 'enterprise' ? 'comfortable' : 'enterprise';
                document.documentElement.setAttribute('data-density', newDensity);
                localStorage.setItem('kayan_density', newDensity);
                updateDensityButton(newDensity);
                return;
            }
        });
    }

    // Set initial icon states on each page load
    const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcons(activeTheme);

    const activeDensity = document.documentElement.getAttribute('data-density') || 'comfortable';
    updateDensityButton(activeDensity);
}

function updateThemeIcons(theme) {
    const themeToggleBtn = document.getElementById('kayanThemeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.setAttribute('title', theme === 'dark' ? 'تبديل إلى الوضع النهاري' : 'تبديل إلى الوضع الليلي');
        themeToggleBtn.setAttribute('aria-label', theme === 'dark' ? 'تبديل إلى الوضع النهاري' : 'تبديل إلى الوضع الليلي');
    }
}

function updateDensityButton(density) {
    const densityLabel = document.getElementById('densityLabel');
    if (densityLabel) {
        densityLabel.textContent = density === 'enterprise' ? 'مؤسسي مكثف' : 'افتراضي مريح';
    }
}

/**
 * App Launcher (Odoo 3x3 App Drawer)
 */
function initAppLauncher() {
    if (!window.__appLauncherDelegated) {
        window.__appLauncherDelegated = true;

        function openLauncher() {
            const backdrop = document.getElementById('appLauncherBackdrop');
            if (backdrop) {
                backdrop.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeLauncher() {
            const backdrop = document.getElementById('appLauncherBackdrop');
            if (backdrop) {
                backdrop.classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        window.closeAppLauncher = closeLauncher;

        document.addEventListener('click', (e) => {
            const launcherBtn = e.target.closest('[data-action="open-app-launcher"]');
            if (launcherBtn) {
                e.preventDefault();
                openLauncher();
                return;
            }

            const closeBtn = e.target.closest('#appLauncherClose');
            if (closeBtn) {
                closeLauncher();
                return;
            }

            const backdrop = document.getElementById('appLauncherBackdrop');
            if (backdrop && e.target === backdrop) {
                closeLauncher();
            }
        });
    }
}

/**
 * Sidebar Navigation & Responsive Drawer
 */
function initSidebar() {
    const sidebar = document.getElementById('erpSidebar');
    if (!sidebar) return;

    function isMobile() {
        return window.innerWidth <= 900;
    }

    function openMobileSidebar() {
        const sb = document.getElementById('erpSidebar');
        const ov = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.add('open-mobile');
        if (ov) ov.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateSidebarAria();
    }

    function closeMobileSidebar() {
        const sb = document.getElementById('erpSidebar');
        const ov = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.remove('open-mobile');
        if (ov) ov.classList.remove('active');
        document.body.style.overflow = '';
        updateSidebarAria();
    }

    function toggleSidebar() {
        if (isMobile()) {
            const sb = document.getElementById('erpSidebar');
            if (sb && sb.classList.contains('open-mobile')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        } else {
            const sb = document.getElementById('erpSidebar');
            if (!sb) return;
            const isCollapsed = sb.classList.contains('collapsed');
            if (isCollapsed) {
                sb.classList.remove('collapsed');
                localStorage.setItem('kayan_sidebar_collapsed', 'false');
            } else {
                sb.classList.add('collapsed');
                localStorage.setItem('kayan_sidebar_collapsed', 'true');
            }
            updateSidebarAria();
        }
    }

    function updateSidebarAria() {
        const sb = document.getElementById('erpSidebar');
        if (!sb) return;
        const desktopToggle = document.getElementById('sidebarDesktopToggle');
        const topbarToggle = document.getElementById('sidebarToggleBtn') || document.getElementById('sidebarMobileToggle');

        const isCollapsed = sb.classList.contains('collapsed');
        const isOpenMobile = sb.classList.contains('open-mobile');
        const isExpanded = isMobile() ? isOpenMobile : !isCollapsed;

        if (desktopToggle) {
            desktopToggle.setAttribute('aria-expanded', isExpanded.toString());
            desktopToggle.title = isCollapsed ? 'توسيع القائمة' : 'طي القائمة';
        }
        if (topbarToggle) {
            topbarToggle.setAttribute('aria-expanded', isExpanded.toString());
            topbarToggle.title = isExpanded ? 'طي القائمة الجانبية' : 'فتح القائمة الجانبية';
        }
    }

    // Load saved preference on desktop
    const savedCollapsed = localStorage.getItem('kayan_sidebar_collapsed');
    if (!isMobile()) {
        if (savedCollapsed === 'true') {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    } else {
        sidebar.classList.remove('collapsed');
    }
    updateSidebarAria();

    if (!window.__sidebarDelegated) {
        window.__sidebarDelegated = true;

        document.addEventListener('click', (e) => {
            const toggle = e.target.closest('#sidebarToggleBtn, #sidebarMobileToggle, #sidebarDesktopToggle');
            if (toggle) {
                e.stopPropagation();
                toggleSidebar();
                return;
            }

            const overlay = e.target.closest('#sidebarOverlay');
            if (overlay) {
                closeMobileSidebar();
                return;
            }

            const sb = document.getElementById('erpSidebar');
            const tbToggle = document.getElementById('sidebarToggleBtn') || document.getElementById('sidebarMobileToggle');
            if (isMobile() && sb && sb.classList.contains('open-mobile')) {
                if (!sb.contains(e.target) && !tbToggle?.contains(e.target)) {
                    closeMobileSidebar();
                }
            }
        });

        window.addEventListener('resize', () => {
            const sb = document.getElementById('erpSidebar');
            if (!sb) return;
            if (!isMobile()) {
                closeMobileSidebar();
                const pref = localStorage.getItem('kayan_sidebar_collapsed') === 'true';
                sb.classList.toggle('collapsed', pref);
            } else {
                sb.classList.remove('collapsed');
            }
            updateSidebarAria();
        });
    }
}

/**
 * Generic Dropdown Handlers (Delegated)
 */
function initDropdowns() {
    if (window.__dropdownsDelegated) return;
    window.__dropdownsDelegated = true;

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-dropdown-toggle]');
        if (toggle) {
            e.stopPropagation();
            const targetId = toggle.getAttribute('data-dropdown-toggle');
            const menu = document.getElementById(targetId);
            if (!menu) return;

            const isOpen = menu.classList.contains('open');

            // Close other open menus
            document.querySelectorAll('.erp-dropdown-menu.open').forEach(el => {
                if (el !== menu) el.classList.remove('open');
            });

            menu.classList.toggle('open', !isOpen);
            if (!isOpen) {
                const rect = menu.getBoundingClientRect();
                if (rect.left < 8) {
                    menu.style.left = '0';
                    menu.style.right = 'auto';
                } else if (rect.right > window.innerWidth - 8) {
                    menu.style.right = '0';
                    menu.style.left = 'auto';
                }
            }
            return;
        }

        // Close dropdowns on outside click
        document.querySelectorAll('.erp-dropdown-menu.open').forEach(menu => {
            if (menu.contains(e.target) && !e.target.closest('.erp-dropdown-item:not(.keep-open)')) {
                return;
            }
            menu.classList.remove('open');
        });
    });
}

/**
 * Keyboard Shortcuts (Ctrl+K for Search, Esc to Close)
 */
function initKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
        // Ctrl+K or Cmd+K: Focus global search
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearchInput');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Escape: Close modals, drawers, and dropdowns
        if (e.key === 'Escape' || e.key === 'Esc' || e.keyCode === 27) {
            if (typeof window.closeAppLauncher === 'function') {
                window.closeAppLauncher();
            }

            document.querySelectorAll('.erp-dropdown-menu.open').forEach(menu => {
                menu.classList.remove('open');
            });

            const sidebar = document.getElementById('erpSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && sidebar.classList.contains('open-mobile')) {
                sidebar.classList.remove('open-mobile');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
}

/**
 * Global Search in Topbar
 */
function initGlobalSearch() {
    if (!window.__globalSearchDelegated) {
        window.__globalSearchDelegated = true;

        document.addEventListener('keydown', (e) => {
            if (e.target && e.target.id === 'globalSearchInput' && e.key === 'Enter') {
                e.preventDefault();
                const q = e.target.value.trim();
                if (q) {
                    if (window.Turbo) {
                        window.Turbo.visit('/products?search=' + encodeURIComponent(q));
                    } else {
                        window.location.href = '/products?search=' + encodeURIComponent(q);
                    }
                }
            }
        });
    }
}

/**
 * Safely fetch with dynamic CSRF token refresh on HTTP 419 (CSRF Token Mismatch/Expired Session)
 */
export async function fetchWithCsrfRetry(url, options = {}) {
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
if (typeof window !== 'undefined') {
    window.fetchWithCsrfRetry = fetchWithCsrfRetry;
}

/**
 * Global Direct Python Printing Handler
 */
function initDirectPythonPrint() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action="python-direct-print"]');
        if (!btn) return;

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
            const payload = {};
            if (btn.dataset.format) {
                payload.format = btn.dataset.format;
            }
            if (btn.dataset.printer) {
                payload.printer = btn.dataset.printer;
            }

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
                console.warn('[DirectPrint] Python engine error or network printer unreachable, triggering fallback:', data);
                const fallbackUrl = btn.dataset.fallbackUrl;
                if (fallbackUrl) {
                    btn.innerHTML = `<span>جاري فتح شاشة الطباعة...</span>`;
                    window.open(fallbackUrl + (fallbackUrl.includes('?') ? '&' : '?') + 'auto=1', '_blank');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.disabled = originalDisabled;
                    }, 1500);
                } else if (typeof window.print === 'function') {
                    window.print();
                    btn.innerHTML = originalHtml;
                    btn.disabled = originalDisabled;
                } else {
                    alert(`فشل أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                    btn.innerHTML = originalHtml;
                    btn.disabled = originalDisabled;
                }
            }
        } catch (err) {
            console.warn('[DirectPrint] Network error, triggering fallback:', err);
            const fallbackUrl = btn.dataset.fallbackUrl;
            if (fallbackUrl) {
                window.open(fallbackUrl + (fallbackUrl.includes('?') ? '&' : '?') + 'auto=1', '_blank');
            } else {
                window.print();
            }
            btn.innerHTML = originalHtml;
            btn.disabled = originalDisabled;
        }
    });
}

/**
 * PWA & Desktop App Installation Manager
 */
function initPwaInstallation() {
    // 1. Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch((err) => {
                console.debug('[PWA] Service Worker registration note:', err);
            });
        });
    }

    // 2. Handle beforeinstallprompt event for desktop installation
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        const installBtns = document.querySelectorAll('[data-action="install-pwa-app"]');
        installBtns.forEach(btn => {
            btn.style.display = 'inline-flex';
            btn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;
                if (choice.outcome === 'accepted') {
                    console.log('[PWA] User accepted the installation');
                }
                deferredPrompt = null;
            });
        });
    });

    window.addEventListener('appinstalled', () => {
        console.log('[PWA] Kayan ERP desktop app installed');
        const installBtns = document.querySelectorAll('[data-action="install-pwa-app"]');
        installBtns.forEach(btn => {
            btn.style.display = 'none';
        });
    });
}
