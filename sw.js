// sw.js - Service Worker pour PWA
const CACHE_NAME = 'gmao-cache-v1';
const urlsToCache = [
  '/gmao_GEMINI/',
  '/gmao_GEMINI/index.php?page=mobile_dashboard',
  '/gmao_GEMINI/index.php?page=mobile_interventions',
  '/gmao_GEMINI/index.php?page=mobile_equipment',
  '/gmao_GEMINI/index.php?page=mobile_scan',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

// Installation du service worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache ouvert');
        return cache.addAll(urlsToCache);
      })
  );
});

// Interception des requêtes
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request)
          .then(response => {
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            return response;
          });
      })
  );
});

// Activation et nettoyage
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Gestion des notifications push
self.addEventListener('push', event => {
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: 'assets/icons/icon-192.png',
    badge: 'assets/icons/icon-72.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Clic sur notification
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});