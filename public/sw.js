// Kayan ERP Service Worker for High Speed Offline & Local Network Operation
const CACHE_NAME = 'kayan-erp-v3';

// Core static assets to pre-cache upon installation
const STATIC_ASSETS = [
    '/login',
    '/favicon.svg',
    '/favicon.ico',
    '/icon-192.png',
    '/icon-512.png',
    '/manifest.json',
    '/fonts/cairo/cairo.css',
    '/fonts/cairo/cairo-arabic.woff2',
    '/fonts/cairo/cairo-latin-ext.woff2',
    '/fonts/cairo/cairo-latin.woff2'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            for (const asset of STATIC_ASSETS) {
                try {
                    await cache.add(asset);
                } catch (e) {
                    console.debug('[SW] Pre-caching asset note:', asset, e);
                }
            }
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

/**
 * Intelligent Caching Strategy:
 * 1. Static Assets (CSS, JS, Fonts, Images, Icons, Vite Build):
 *    -> Cache-First (Instant 0ms load, then fallback to network & cache response)
 * 2. Dynamic ERP Pages (HTML, API, Navigation):
 *    -> Network-First (Fresh data, fallback to cache if offline)
 */
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
        return;
    }

    const url = new URL(event.request.url);
    const isStaticAsset = (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.startsWith('/storage/company/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname === '/manifest.json'
    );

    if (isStaticAsset) {
        // Strategy 1: Cache-First for static assets
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Strategy 2: Network-First for dynamic ERP routes
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});
