/**
 * Msantha Poultry Management System - Service Worker
 * Network-First for dynamic HTML pages, Cache-First for static assets, Never cache POST or auth routes.
 */

const CACHE_NAME = 'msantha-shell-v2';
const STATIC_ASSETS = [
    './',
    './assets/css/app.css',
    './assets/js/app.js',
    './assets/img/logo.png',
    './pwa-192x192.png',
    './pwa-512x512.png',
    './manifest.webmanifest',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Sensitive or dynamic routes that must NEVER be cached
const NO_CACHE_PATTERNS = [
    '/login',
    '/logout',
    '/force-password-change',
    '/change-password',
    '/forgot-password',
    '/verify-reset-code',
    '/reset-password',
    '/api/sync',
    '/webhooks/',
    '/payments/initiate',
    '/payments/return'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[SW] Some initial assets failed to cache:', err);
            });
        })
    );
    self.skipWaiting();
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
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Never cache non-GET requests (POST, PUT, DELETE, etc.)
    if (request.method !== 'GET') {
        return;
    }

    // 2. Never cache auth routes, sync endpoint, or payment callbacks
    const isNoCache = NO_CACHE_PATTERNS.some((pattern) => url.pathname.includes(pattern));
    if (isNoCache) {
        event.respondWith(fetch(request));
        return;
    }

    // 3. HTML Pages (Navigation): Network-First with Cache Fallback
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, copy);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Fallback to cached root/shell
                    const fallback = await caches.match('./');
                    if (fallback) {
                        return fallback;
                    }
                    return new Response(
                        `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Offline — Msantha</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>You are offline</h2><p>Msantha Farm Manager is offline. Previously viewed screens and offline entry remain accessible.</p></body></html>`,
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                })
        );
        return;
    }

    // 4. Static Assets (CSS, JS, Fonts, Images): Cache-First
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Fetch in background to update cache (stale-while-revalidate)
                fetch(request).then((freshResponse) => {
                    if (freshResponse && freshResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, freshResponse));
                    }
                }).catch(() => {});
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const copy = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, copy);
                    });
                }
                return networkResponse;
            });
        })
    );
});
