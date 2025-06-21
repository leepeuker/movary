function regeneratePlexWebhook() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    regeneratePlexWebhookRequest().then(webhookUrl => {
        setPlexWebhookUrl(webhookUrl)
        addAlert('alertWebhookUrlDiv', 'Generated new webhook url', 'success')
        document.getElementById('deletePlexWebhookButton').classList.remove('disabled')
    }).catch((error) => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url', 'danger')
    })
}

function deletePlexWebhook() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    deletePlexWebhookRequest().then(() => {
        setPlexWebhookUrl()
        addAlert('alertWebhookUrlDiv', 'Deleted webhook url', 'success')
    }).catch(() => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not delete webhook url', 'danger')
    })
}

async function regeneratePlexWebhookRequest() {
    const response = await fetch(APPLICATION_URL + '/settings/plex/webhook', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.url
}

async function deletePlexWebhookRequest() {
    const response = await fetch(APPLICATION_URL + '/settings/plex/webhook', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

function setPlexWebhookUrl(webhookUrl) {
    if (webhookUrl) {
        document.getElementById('plexWebhookUrl').innerHTML = webhookUrl
        document.getElementById('deletePlexWebhookButton').classList.remove('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = false
        document.getElementById('scrobbleRatingsCheckbox').disabled = false
        document.getElementById('saveButton').disabled = false
    } else {
        document.getElementById('plexWebhookUrl').innerHTML = '-'
        document.getElementById('deletePlexWebhookButton').classList.add('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = true
        document.getElementById('scrobbleRatingsCheckbox').disabled = true
        document.getElementById('saveButton').disabled = true
    }
}

async function updateScrobbleOptions() {
    removeAlert('alertWebhookOptionsDiv')

    await fetch(APPLICATION_URL + '/settings/plex', {
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

async function authenticateWithPlex() {
    const response = await fetch(
        APPLICATION_URL + '/settings/plex/authentication-url',
        {signal: AbortSignal.timeout(4000)}
    ).catch(function (error) {
        document.getElementById('alertPlexServerUrlLoadingSpinner').classList.add('d-none')

        console.log(error)
        addAlert('alertPlexServerUrlDiv', 'Authentication did not work', 'danger')
    });

    if (!response.ok) {
        if (response.status === 400) {
            addAlert('alertPlexAuthenticationDiv', await response.text(), 'danger')

            return
        }

        addAlert('alertPlexAuthenticationDiv', 'Authentication did not work', 'danger')

        return
    }

    const data = await response.json()

    location.href = data.authenticationUrl;
}

async function removePlexAuthentication() {
    const response = await fetch(
        APPLICATION_URL + '/settings/plex/logout',
        {signal: AbortSignal.timeout(4000)}
    ).catch(function (error) {
        console.log(error)

        addAlert('alertPlexAuthenticationDiv', 'Could not remove authentication', 'danger')
    });

    if (!response.ok) {
        addAlert('alertPlexAuthenticationDiv', 'Could not remove authentication', 'danger')

        return
    }

    document.getElementById('plexServerUrlInput').disabled = true
    document.getElementById('plexServerUrlInput').value = ''
    document.getElementById('saveServerUrlButton').disabled = true
    document.getElementById('verifyServerUrlButton').disabled = true

    document.getElementById('authenticateWithPlexDiv').classList.remove('d-none')
    document.getElementById('removeAuthenticationWithPlexDiv').classList.add('d-none')

    addAlert('alertPlexAuthenticationDiv', 'Plex authentication was removed', 'success')
}

async function savePlexServerUrl() {
    const response = await fetch(APPLICATION_URL + '/settings/plex/server-url-save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'plexServerUrl': document.getElementById('plexServerUrlInput').value,
        })
    }).then(async function (response) {
        return {'status': response.status, 'message': await response.text()};
    }).then(function (data) {
        if (data.status === 200) {
            addAlert('alertPlexServerUrlDiv', 'Server url was updated', 'success')

            return
        }

        if (data.status === 400) {
            addAlert('alertPlexServerUrlDiv', data.message, 'danger')

            return
        }

        addAlert('alertPlexServerUrlDiv', 'Server URL could not be updated', 'danger')
    }).catch(function (error) {
        document.getElementById('alertPlexServerUrlLoadingSpinner').classList.add('d-none')

        console.log(error)
        addAlert('alertPlexServerUrlDiv', 'Server URL could not be updated', 'danger')
    });
}

async function verifyPlexServerUrl() {
    document.getElementById('alertPlexServerUrlLoadingSpinner').classList.remove('d-none')
    removeAlert('alertPlexServerUrlDiv')

    const response = await fetch(APPLICATION_URL + '/settings/plex/server-url-verify', {
        signal: AbortSignal.timeout(4000),
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'plexServerUrl': document.getElementById('plexServerUrlInput').value,
        })
    }).then(async function (response) {
        document.getElementById('alertPlexServerUrlLoadingSpinner').classList.add('d-none')

        if (response.status === 400) {
            addAlert('alertPlexServerUrlDiv', await response.text(), 'danger')

            return
        }

        const data = await response.json()

        if (response.status === 200 && data === true) {
            addAlert('alertPlexServerUrlDiv', 'Connection test successful', 'success')

            return
        }

        addAlert('alertPlexServerUrlDiv', 'Connection test failed', 'danger')
    }).catch(function (error) {
        document.getElementById('alertPlexServerUrlLoadingSpinner').classList.add('d-none')

        console.log(error)
        addAlert('alertPlexServerUrlDiv', 'Connection test failed', 'danger')
    });
}

document.getElementById('verifyServerUrlButton').disabled = document.getElementById('plexServerUrlInput').value === ''
document.getElementById('plexServerUrlInput').addEventListener('input', function (e) {
    document.getElementById('verifyServerUrlButton').disabled = e.target.value === ''
});

async function importPlexWatchlist() {
    const response = await fetch(
        APPLICATION_URL + '/jobs/schedule/plex-watchlist-sync',
        {'method': 'get'}
    ).catch(function (error) {
        addAlert('alertPlexWatchlistImportDiv', 'Watchlist import could not be scheduled', 'danger')

        throw new Error(`HTTP error! status: ${response.status}`)
    });

    if (!response.ok) {
        if (response.status === 400) {
            addAlert('alertPlexWatchlistImportDiv', await response.text(), 'danger')

            return
        }

        addAlert('alertPlexWatchlistImportDiv', 'Watchlist import could not be scheduled', 'danger')

        throw new Error(`HTTP error! status: ${response.status}`)
    }

    addAlert('alertPlexWatchlistImportDiv', 'Watchlist import scheduled', 'success')
}
