const LEGACY_CACHE_NAME = 'movary'

self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting())
})

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.delete(LEGACY_CACHE_NAME).then(() => self.clients.claim())
    )
})
