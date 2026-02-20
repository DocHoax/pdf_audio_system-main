/**
 * EchoDoc Service Worker
 * Provides offline functionality and caching for PWA
 */

const CACHE_NAME = 'echodoc-v4';
const OFFLINE_URL = '/offline.html';

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/assets/css/style.css',
    '/assets/css/auth.css',
    '/assets/js/main.js',
    '/assets/js/speech.js',
    '/assets/images/favicon.png',
    '/offline.html'
];

// Install event - cache core assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Precaching assets');
                return Promise.all(
                    PRECACHE_ASSETS.map((asset) =>
                        cache.add(asset).catch(() => {
                            console.warn('[SW] Failed to precache:', asset);
                            return null;
                        })
                    )
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Prevent Chrome/Safari errors for special requests
    if (event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin') {
        return;
    }

    // Skip API calls and external resources
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api/') ||
        url.hostname !== self.location.hostname) {
        return;
    }

    // Always use network for page navigation to avoid stale auth/session pages.
    // Never cache dynamic PHP navigation responses.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Cache only static assets; dynamic endpoints should stay network-only.
    const isStaticAsset = /\.(?:css|js|png|jpg|jpeg|gif|svg|webp|ico|woff|woff2|ttf)$/i.test(url.pathname);
    if (!isStaticAsset) {
        event.respondWith(fetch(event.request));
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    // Fetch fresh version in background
                    event.waitUntil(
                        fetch(event.request)
                            .then((response) => {
                                if (response.ok && response.type === 'basic' && !response.redirected) {
                                    caches.open(CACHE_NAME)
                                        .then((cache) => cache.put(event.request, response));
                                }
                            })
                            .catch(() => { })
                    );
                    return cachedResponse;
                }

                // Not in cache, try network
                return fetch(event.request)
                    .then((response) => {
                        // Cache successful responses
                        if (response.ok && response.type === 'basic' && !response.redirected) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => cache.put(event.request, responseClone));
                        }
                        return response;
                    })
                    .catch(() => {
                        // Network failed, show offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                        return new Response('Offline', { status: 503 });
                    });
            })
    );
});

// Handle background sync for uploads (future feature)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-uploads') {
        console.log('[SW] Background sync triggered');
    }
});
