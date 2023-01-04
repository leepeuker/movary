document.addEventListener('DOMContentLoaded', function () {
    fetchPlexWebhookId().then(webhookId => {
        setPlexWebhookUrl(webhookId)
    }).catch(() => {
        alert('Could not fetch plex webhook url')
        setPlexWebhookUrl()
    })
})

function regeneratePlexWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    regeneratePlexWebhookIdRequest().then(webhookId => {
        setPlexWebhookUrl(webhookId)
    }).catch(() => {
        alert('Could not regenerate plex webhook url')
        setPlexWebhookUrl()
    })
}

function deletePlexWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    deletePlexWebhookIdRequest().then(() => {
        setPlexWebhookUrl()
    }).catch(() => {
        alert('Could not delete plex webhook url')
    })
}

function setPlexWebhookUrl(webhookId) {
    if (webhookId) {
        document.getElementById('plexWebhookUrl').innerHTML = location.protocol + '//' + location.host + '/plex/' + webhookId
        document.getElementById('deletePlexWebhookIdButton').classList.remove('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = false
        document.getElementById('scrobbleRatingsCheckbox').disabled = false
        document.getElementById('saveButton').disabled = false
    } else {
        document.getElementById('plexWebhookUrl').innerHTML = '-'
        document.getElementById('deletePlexWebhookIdButton').classList.add('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = true
        document.getElementById('scrobbleRatingsCheckbox').disabled = true
        document.getElementById('saveButton').disabled = true
    }
}

async function fetchPlexWebhookId() {
    const response = await fetch('/user/plex-webhook-id')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function regeneratePlexWebhookIdRequest() {
    const response = await fetch('/user/plex-webhook-id', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function deletePlexWebhookIdRequest() {
    const response = await fetch('/user/plex-webhook-id', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}
