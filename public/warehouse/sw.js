// Secure PWA: never cache authenticated order pages, addresses or PDFs.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
    if (event.request.method === 'GET' && event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request));
    }
});
