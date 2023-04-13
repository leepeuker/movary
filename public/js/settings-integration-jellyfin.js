document.addEventListener('DOMContentLoaded', function () {
    fetchJellyfinWebhookId().then(webhookId => {
        setJellyfinWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not fetch Jellyfin webhook url', 'danger')
        setJellyfinWebhookUrl()
    })
})

function regenerateJellyfinWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')
    setJellyfinWebhookUrlLoadingSpinner()

    regenerateJellyfinWebhookIdRequest().then(webhookId => {
        addAlert('alertWebhookUrlDiv', 'Generate Jellyfin webhook url', 'success')
        setJellyfinWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not generate Jellyfin webhook url', 'danger')
    })
}

function deleteJellyfinWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }
    removeAlert('alertWebhookUrlDiv')
    setJellyfinWebhookUrlLoadingSpinner()

    deleteJellyfinWebhookIdRequest().then(() => {
        setJellyfinWebhookUrl()
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not delete Jellyfin webhook url', 'danger')
    })
}

function setJellyfinWebhookUrl(webhookId) {
    document.getElementById('loadingSpinner').classList.add('d-none')

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
    const response = await fetch('/settings/jellyfin/webhook-id')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function regenerateJellyfinWebhookIdRequest() {
    const response = await fetch('/settings/jellyfin/webhook-id', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function deleteJellyfinWebhookIdRequest() {
    const response = await fetch('/settings/jellyfin/webhook-id', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

function setJellyfinWebhookUrlLoadingSpinner() {
    document.getElementById('jellyfinWebhookUrl').innerHTML =
        '<div class="spinner-border spinner-border-sm" role="status" id="loadingSpinner" style="margin-top: .1rem">\n' +
        '<span class="visually-hidden">Loading...</span>\n' +
        '</div>'
}
