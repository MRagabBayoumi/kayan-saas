import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/theme-init.js',
                'resources/js/app.js',
                'resources/css/print/thermal-label.css',
                'resources/js/pages/print-label.js',
                'resources/css/print/invoice-document.css',
                'resources/js/pages/invoice-print.js',
                'resources/css/print/shift-receipt.css',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
