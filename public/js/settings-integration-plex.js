document.addEventListener('DOMContentLoaded', function () {
    fetchPlexWebhookId().then(webhookId => {
        setPlexWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not fetch plex webhook url', 'danger')
    })
})

function regeneratePlexWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')
    setPlexWebhookUrlLoadingSpinner()

    regeneratePlexWebhookIdRequest().then(webhookId => {
        addAlert('alertWebhookUrlDiv', 'Generate plex webhook url', 'success')
        setPlexWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not generate plex webhook url', 'danger')
    })
}

function deletePlexWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')
    setPlexWebhookUrlLoadingSpinner()

    deletePlexWebhookIdRequest().then(() => {
        setPlexWebhookUrl()
        addAlert('alertWebhookUrlDiv', 'Deleted plex webhook url', 'success')
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not delete plex webhook url', 'danger')
    })
}

function setPlexWebhookUrl(webhookId) {
    document.getElementById('loadingSpinner').classList.add('d-none')

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
    const response = await fetch('/settings/plex/webhook-id')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function regeneratePlexWebhookIdRequest() {
    const response = await fetch('/settings/plex/webhook-id', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function deletePlexWebhookIdRequest() {
    const response = await fetch('/settings/plex/webhook-id', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

function setPlexWebhookUrlLoadingSpinner() {
    document.getElementById('plexWebhookUrl').innerHTML =
        '<div class="spinner-border spinner-border-sm" role="status" id="loadingSpinner" style="margin-top: .1rem">\n' +
        '<span class="visually-hidden">Loading...</span>\n' +
        '</div>'
}
