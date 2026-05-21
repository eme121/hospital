const CACHE_NAME = 'hope-haven-v2';
const urlsToCache = [
  './',
  './index.php',
  './logo.png',
  './manifest.json',
  'https://cdn.tailwindcss.com',
  'https://unpkg.com/lucide@latest'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('PWA: Caching essential assets');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Activate & Cleanup old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('PWA: Clearing old cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch with Network Fallback
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request).catch(() => {
            // Optional: return a custom offline page if fetch fails
            if (event.request.mode === 'navigate') {
                return caches.match('./index.php');
            }
        });
      }
    )
  );
});