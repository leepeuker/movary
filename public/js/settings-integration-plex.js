function regeneratePlexWebhookId() {
    if (document.getElementById('plexWebhookUrl').dataset.applicationUrl == '') {
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url: Application url not set', 'danger')

        return
    }

    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    regeneratePlexWebhookIdRequest().then(webhookId => {
        location.reload()
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url', 'danger')
    })
}

function deletePlexWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    deletePlexWebhookIdRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertWebhookUrlDiv', 'Could not delete webhook url', 'danger')
    })
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
