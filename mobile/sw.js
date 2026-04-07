const CACHE_NAME = 'petadigi-v1';
const ASSETS = [
    './',
  './index.html',
  './src/css/style.css',
  './src/js/app.js',
  './src/js/router.js',
  './src/js/pages/welcome.js',
  './manifest.json'
];

// Install - cache semua asset
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
});

// Activate - hapus cache lama
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
});

// Fetch - serve dari cache, fallback ke network
self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request))
  );
});