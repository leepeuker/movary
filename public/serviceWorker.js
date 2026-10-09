const LEGACY_CACHE_NAME = 'movary'

self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting())
})

self.addEventListener('activate', event => {
    event.waitUntil(
        (async () => {
            const cacheNames = await caches.keys()
            const legacyCacheExists = cacheNames.includes(LEGACY_CACHE_NAME)

            if (legacyCacheExists === true) {
                await caches.delete(LEGACY_CACHE_NAME)
            }

            await self.clients.claim()

            if (legacyCacheExists === false) {
                return
            }

            const windowClients = await self.clients.matchAll({type: 'window'})
            await Promise.all(windowClients.map(client => {
                const path = new URL(client.url).pathname
                if ((path !== '/' && path !== '/index.php') || typeof client.navigate !== 'function') {
                    return Promise.resolve()
                }

                return client.navigate(client.url)
            }))
        })()
    )
})
