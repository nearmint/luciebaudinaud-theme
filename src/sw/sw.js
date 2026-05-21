// Kill switch SW — désinscrit ce worker et purge tous les caches.
// Voir inc/service-worker.php pour contexte.
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil((async function () {
        try {
            const keys = await caches.keys();
            await Promise.all(keys.map(function (k) { return caches.delete(k); }));
        } catch (e) {}
        try {
            await self.registration.unregister();
        } catch (e) {}
        try {
            const clients = await self.clients.matchAll({ type: 'window' });
            clients.forEach(function (client) {
                if (client.url) {
                    client.navigate(client.url);
                }
            });
        } catch (e) {}
    })());
});

// Plus d'interception : laisse le navigateur faire.
self.addEventListener('fetch', function () {});
