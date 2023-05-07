document.addEventListener('DOMContentLoaded', function () {
    fetchEmbyWebhookId().then(webhookId => {
        setEmbyWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not fetch Emby webhook url', 'danger')
        setEmbyWebhookUrl()
    })
})

function regenerateEmbyWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')
    setEmbyWebhookUrlLoadingSpinner()

    regenerateEmbyWebhookIdRequest().then(webhookId => {
        addAlert('alertWebhookUrlDiv', 'Generated Emby webhook url', 'success')
        setEmbyWebhookUrl(webhookId)
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not generate Emby webhook url', 'danger')
    })
}

function deleteEmbyWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }
    removeAlert('alertWebhookUrlDiv')
    setEmbyWebhookUrlLoadingSpinner()

    deleteEmbyWebhookIdRequest().then(() => {
        addAlert('alertWebhookUrlDiv', 'Deleted Emby webhook url', 'success')
        setEmbyWebhookUrl()
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not delete Emby webhook url', 'danger')
    })
}

function setEmbyWebhookUrl(webhookId) {
    document.getElementById('loadingSpinner').classList.add('d-none')

    if (webhookId) {
        document.getElementById('embyWebhookUrl').innerHTML = location.protocol + '//' + location.host + '/emby/' + webhookId
        document.getElementById('deleteEmbyWebhookIdButton').classList.remove('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = false
        document.getElementById('saveButton').disabled = false
    } else {
        document.getElementById('embyWebhookUrl').innerHTML = '-'
        document.getElementById('deleteEmbyWebhookIdButton').classList.add('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = true
        document.getElementById('saveButton').disabled = true
    }
}

async function fetchEmbyWebhookId() {
    const response = await fetch('/settings/emby/webhook-id')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function regenerateEmbyWebhookIdRequest() {
    const response = await fetch('/settings/emby/webhook-id', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.id
}

async function deleteEmbyWebhookIdRequest() {
    const response = await fetch('/settings/emby/webhook-id', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

function setEmbyWebhookUrlLoadingSpinner() {
    document.getElementById('embyWebhookUrl').innerHTML =
        '<div class="spinner-border spinner-border-sm" role="status" id="loadingSpinner" style="margin-top: .1rem">\n' +
        '<span class="visually-hidden">Loading...</span>\n' +
        '</div>'
}
