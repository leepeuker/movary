function regenerateJellyfinWebhook() {
    if (confirm('Do you really want to regenerate the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    regenerateJellyfinWebhookRequest().then(webhookUrl => {
        setJellyfinWebhookUrl(webhookUrl)
        addAlert('alertWebhookUrlDiv', 'Generated new webhook url', 'success')
    }).catch((error) => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not generate webhook url', 'danger')
    })
}

function deleteJellyfinWebhook() {
    if (confirm('Do you really want to delete the webhook url?') === false) {
        return
    }

    removeAlert('alertWebhookUrlDiv')

    deleteJellyfinWebhookRequest().then(() => {
        setJellyfinWebhookUrl()
        addAlert('alertWebhookUrlDiv', 'Deleted webhook url', 'success')
    }).catch((error) => {
        console.log(error)
        addAlert('alertWebhookUrlDiv', 'Could not delete webhook url', 'danger')
    })
}

async function regenerateJellyfinWebhookRequest() {
    const response = await fetch('/settings/jellyfin/webhook', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.url
}

async function deleteJellyfinWebhookRequest() {
    const response = await fetch('/settings/jellyfin/webhook', {'method': 'delete'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}

function setJellyfinWebhookUrl(webhookUrl) {
    if (webhookUrl) {
        document.getElementById('jellyfinWebhookUrl').innerHTML = webhookUrl
        document.getElementById('deleteJellyfinWebhookButton').classList.remove('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = false
        document.getElementById('saveButton').disabled = false
    } else {
        document.getElementById('jellyfinWebhookUrl').innerHTML = '-'
        document.getElementById('deleteJellyfinWebhookButton').classList.add('disabled')
        document.getElementById('scrobbleWatchesCheckbox').disabled = true
        document.getElementById('saveButton').disabled = true
    }
}

async function updateScrobbleOptions() {
    removeAlert('alertWebhookOptionsDiv')

    await fetch('/settings/jellyfin', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json'
        },
        body: JSON.stringify({
            'scrobbleWatches': document.getElementById('scrobbleWatchesCheckbox').checked,
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

async function saveJellyfinServerUrl() {
    document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.remove('d-none')
    removeAlert('alertJellyfinServerUrlDiv');

    const jellyfinServerUrl = document.getElementById('jellyfinServerUrlInput').value;

    await fetch('/settings/jellyfin/server-url-save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'JellyfinServerUrl': jellyfinServerUrl
        })
    }).then(response => {
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')

        if (!response.ok) {
            addAlert('alertJellyfinServerUrlDiv', 'Could not save server url', 'danger');
            return;
        }

        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinServerUrlDiv', 'Server url was updated', 'success');

        console.log(jellyfinServerUrl)
        document.getElementById('jellyfinAuthenticationModalServerUrlInput').value = jellyfinServerUrl
        document.getElementById('authenticateWithJellyfinButton').disabled = jellyfinServerUrl == ''
        document.getElementById('verifyServerUrlButton').disabled = jellyfinServerUrl == ''
        document.getElementById('jellyfinAuthenticationModalAuthenticateButton').disabled = jellyfinServerUrl == ''
    }).catch(function (error) {
        console.log(error);
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinServerUrlDiv', 'Could not save server url', 'danger');
    });
}

async function verifyJellyfinServerUrl() {
    document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.remove('d-none')
    removeAlert('alertJellyfinServerUrlDiv');

    const response = await fetch(
        '/settings/jellyfin/server-url-verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            signal: AbortSignal.timeout(4000),
            body: JSON.stringify({
                'jellyfinServerUrl': document.getElementById('jellyfinServerUrlInput').value
            })
        }
    ).catch(function (error) {
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinServerUrlDiv', 'Connection test failed: Cannot connect to server', 'danger');
    });

    document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')

    if (!response.ok) {
        if (response.status === 400) {
            addAlert('alertJellyfinAuthenticationModalDiv', await response.text(), 'danger')

            return
        }

        addAlert('alertJellyfinServerUrlDiv', 'Connection test failed', 'danger');

        return;
    }

    const verificationResult = await response.json();

    if (verificationResult.serverUrlVerified === false) {
        addAlert('alertJellyfinServerUrlDiv', 'Connection test failed: Cannot connect to server', 'danger');

        return
    }

    if (verificationResult.authenticationVerified === false) {
        addAlert('alertJellyfinServerUrlDiv', 'Can connect to server, but authentication is missing or invalid', 'warning');

        return
    }

    addAlert('alertJellyfinServerUrlDiv', 'Connection test successful', 'success');
}

async function authenticateJellyfinAccount() {
    document.getElementById('jellyfinAuthenticationModalLoadingSpinner').classList.remove('d-none')
    removeAlert('alertJellyfinAuthenticationModalDiv')

    const response = await fetch(
        '/settings/jellyfin/authenticate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            signal: AbortSignal.timeout(4000),
            body: JSON.stringify({
                'username': document.getElementById('jellyfinAuthenticationModalUsernameInput').value,
                'password': document.getElementById('jellyfinAuthenticationModalPasswordInput').value,
            })
        }
    ).catch(function (error) {
        console.error(error)
        document.getElementById('jellyfinAuthenticationModalLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinAuthenticationModalDiv', 'Could not authenticate with Jellyfin', 'danger', 0);
    });

    document.getElementById('jellyfinAuthenticationModalLoadingSpinner').classList.add('d-none')

    if (!response.ok) {
        if (response.status === 400) {
            addAlert('alertJellyfinAuthenticationModalDiv', await response.text(), 'danger')

            return
        }

        addAlert('alertJellyfinAuthenticationModalDiv', 'Authentication did not work', 'danger')

        return
    }

    location.reload();
}

async function removeJellyfinAuthentication() {
    await fetch('/settings/jellyfin/remove-authentication', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    }).then(response => {
        if (!response.ok) {
            addAlert('alertJellyfinImportDiv', 'The authentication could not be removed', 'danger');

            return
        }

        location.reload();
    }).catch(function (error) {
        console.error(error)
        addAlert('alertJellyfinImportDiv', 'The authentication could not be removed', 'danger');
    });
}


async function updateSyncOptions() {
    removeAlert('alertJellyfinSyncDiv')

    await fetch('/settings/jellyfin/sync', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json'
        },
        body: JSON.stringify({
            'syncWatches': document.getElementById('automaticWatchStateSyncCheckbox').checked,
        })
    }).then(response => {
        if (!response.ok) {
            addAlert('alertJellyfinSyncDiv', 'Could not update sync options', 'danger')

            return
        }

        addAlert('alertJellyfinSyncDiv', 'Sync options were updated', 'success')
    }).catch(function (error) {
        console.log(error)
        addAlert('alertJellyfinSyncDiv', 'Could not update sync options', 'danger')
    });
}
