const LEGACY_CACHE_NAME = 'movary'
const CSRF_RECOVERY_CACHE_NAME = 'movary-csrf-recovery-v1'

self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting())
})

self.addEventListener('activate', event => {
    event.waitUntil(
        (async () => {
            const cacheNames = await caches.keys()
            const legacyCacheExists = cacheNames.includes(LEGACY_CACHE_NAME)
            const recoveryRequired = cacheNames.includes(CSRF_RECOVERY_CACHE_NAME) === false

            if (legacyCacheExists === true) {
                await caches.delete(LEGACY_CACHE_NAME)
            }

            await self.clients.claim()

            if (recoveryRequired === false) {
                return
            }

            const windowClients = await self.clients.matchAll({type: 'window', includeUncontrolled: true})
            await Promise.all(windowClients.map(client => {
                if (typeof client.navigate !== 'function') {
                    return Promise.resolve()
                }

                return client.navigate(client.url)
            }))
            await caches.open(CSRF_RECOVERY_CACHE_NAME)
        })()
    )
})
