/**
 * Kayan ERP — Pre-render Theme & Density Initializer
 * Prevents theme and density flickering on initial page paint
 */
(function () {
    try {
        const savedTheme = localStorage.getItem('kayan_theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }

        const savedDensity = localStorage.getItem('kayan_density');
        if (savedDensity) {
            document.documentElement.setAttribute('data-density', savedDensity);
        }
    } catch (e) {
        // LocalStorage might be disabled in private browsing
    }
})();
