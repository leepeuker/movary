function regeneratePlexWebhookId() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    regeneratePlexWebhookIdRequest().then(webhookId => {
        setPlexWebhookUrl(webhookId)
        addAlert('alertWebhookUrlDiv', 'Generated new webhook url', 'success')
    }).catch((error) => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url', 'danger')
    })
}

function deletePlexWebhookId() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    deletePlexWebhookIdRequest().then(() => {
        setPlexWebhookUrl()
        addAlert('alertWebhookUrlDiv', 'Deleted webhook url', 'success')
    }).catch(() => {
        console.log(error)
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

async function updateScrobbleOptions() {
    removeAlert('alertWebhookOptionsDiv')

    await fetch('/settings/plex', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json'
        },
        body: JSON.stringify({
            'scrobbleWatches': document.getElementById('scrobbleWatchesCheckbox').checked,
            'scrobbleRatings': document.getElementById('scrobbleRatingsCheckbox').checked
        })
    }).then(response => {
        if (!response.ok) {
            addAlert('alertWebhookOptionsDiv', 'Could not update scrobble options', 'danger')

            return
        }

        addAlert('alertWebhookOptionsDiv', 'Scrobble options were updated', 'success')
    }).catch(function (error) {
        console.log(error)
        addAlert('alertWebhookOptionsDiv', 'Could not update scrobble options', 'danger')
    });
}
