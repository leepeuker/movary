document.addEventListener('DOMContentLoaded', function () {
    fetchJellyfinWebhookId().then(webhookId => {
        setJellyfinWebhookUrl(webhookId)
    }).catch(() => {
        alert('Could not fetch Jellyfin webhook url')
        setJellyfinWebhookUrl()
    })
})

function regenerateJellyfinWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    regenerateJellyfinWebhookIdRequest().then(webhookId => {
        setJellyfinWebhookUrl(webhookId)
    }).catch(() => {
        alert('Could not regenerate Jellyfin webhook url')
        setJellyfinWebhookUrl()
    })
}

function deleteJellyfinWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    deleteJellyfinWebhookIdRequest().then(() => {
        setJellyfinWebhookUrl()
    }).catch(() => {
        alert('Could not delete Jellyfin webhook url')
    })
}

function setJellyfinWebhookUrl(webhookId) {
    if (webhookId) {
        document.getElementById('jellyfinWebhookUrl').innerHTML = location.protocol + '//' + location.host + '/jellyfin/' + webhookId
        document.getElementById('deleteJellyfinWebhookIdButton').classList.remove('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = false
        document.getElementById('saveButton').disabled = false
    } else {
        document.getElementById('jellyfinWebhookUrl').innerHTML = '-'
        document.getElementById('deleteJellyfinWebhookIdButton').classList.add('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = true
        document.getElementById('saveButton').disabled = true
    }
}

async function fetchJellyfinWebhookId() {
    const response = await fetch('/user/jellyfin-webhook-id')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function regenerateJellyfinWebhookIdRequest() {
    const response = await fetch('/user/jellyfin-webhook-id', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function deleteJellyfinWebhookIdRequest() {
    const response = await fetch('/user/jellyfin-webhook-id', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}
