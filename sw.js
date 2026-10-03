// SuperStore POS High-Performance Offline-First Service Worker
const CACHE_NAME = 'superstore-pos-v3';

const STATIC_ASSETS = [
  './',
  './index.php',
  './sales.php',
  './shift.php',
  './products.php',
  './dashboard.php',
  './manifest.json',
  './assets/css/style.css',
  './assets/js/app3.js',
  './assets/icon-512.png',
  './assets/icon-192.png',
  './assets/images/placeholder.jpg',
  './assets/images/logo.jpg'
];

// Install Event: Pre-cache static shell & assets
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('Some assets failed to pre-cache during install:', err);
      });
    })
  );
});

// Activate Event: Clean up old cache versions & claim clients
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

// Fetch Event: Ultra-fast Cache First for Assets, Network First with Offline Fallback for Pages
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Skip non-GET requests (e.g. POST to checkout.php or api)
  if (req.method !== 'GET') {
    return;
  }

  // Strategy 1: Static Assets (CSS, JS, Fonts, Images) -> Cache First with Network Revalidate
  if (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.jpeg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.ttf') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com') ||
    url.hostname.includes('cdn.jsdelivr.net')
  ) {
    event.respondWith(
      caches.match(req).then((cachedResponse) => {
        const fetchPromise = fetch(req).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, responseClone));
          }
          return networkResponse;
        }).catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // Strategy 2: HTML Pages (.php or root) -> Network First with Offline Cache Fallback
  event.respondWith(
    fetch(req)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, responseClone));
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(req).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Fallback to offline index page if specific page not cached
          return caches.match('./index.php') || caches.match('./');
        });
      })
  );
});