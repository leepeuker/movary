function disableElement(el) {
    el.classList.add('disabled');
    el.disabled = true; 
}

function enableElement(el) {
    el.classList.remove('disabled');
    el.disabled = false;    
}

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

async function saveServerUrl() {
    await fetch('/settings/jellyfin/server-url-save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'JellyfinServerUrl': document.getElementById('jellyfinServerUrlInput').value
        })
    }).then(response => {
        if(!response.ok) {
            addAlert('alertJellyfinImportDiv', 'Could not save server URL', 'danger');
            return;
        }
        addAlert('alertJellyfinImportDiv', 'Succesfully saved the server URL!', 'success');
        
        if(document.getElementById('jellyfinServerUrlInput').value.length > 0) {
            enableElement(document.getElementById('verifyServerUrlBtn'));
            enableElement(document.getElementById('JellyfinUsernameInput'))
            enableElement(document.getElementById('JellyfinPasswordInput'))
        } else {
            disableElement(document.getElementById('verifyServerUrlBtn'));
            disableElement(document.getElementById('JellyfinUsernameInput'))
            disableElement(document.getElementById('JellyfinPasswordInput'))
        }
    }).catch(function (error) {
        console.log(error)
        addAlert('alertJellyfinImportDiv', 'Could not save server URL', 'danger');
    });
}

async function verifyServerUrl() {
    await fetch('/settings/jellyfin/server-url-verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'JellyfinServerUrl': document.getElementById('jellyfinServerUrlInput').value
        })
    }).then(response => {
        if(!response.ok) {
            addAlert('alertJellyfinImportDiv', 'Could not verify server URL', 'danger');
            return;
        }
        addAlert('alertJellyfinImportDiv', 'Succesfully verified the server URL!', 'success');
    }).catch(function (error) {
        console.log(error)
        addAlert('alertJellyfinImportDiv', 'Could not verify server URL', 'danger');
    });
}

async function authenticateJellyfinAccount() {
    await fetch('/settings/jellyfin/authenticate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'username': document.getElementById('JellyfinUsernameInput').value,
            'password': document.getElementById('JellyfinPasswordInput').value,
        })
    }).then(response => {
        if(!response.ok) {
            addAlert('alertJellyfinImportDiv', 'Could not authenticate with Jellyfin', 'danger');
            return;
        }
        addAlert('alertJellyfinImportDiv', 'Succesfully authenticated with Jellyfin!', 'success');
    }).catch(function (error) {
        console.log(error)
        addAlert('alertJellyfinImportDiv', 'Could not authenticate with Jellyfin', 'danger');
    });
}

async function removeJellyfinAuthentication() {
    await fetch('/settings/jellyfin/remove-authentication', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    }).then(response => {
        if(!response.ok) {
            addAlert('alertJellyfinImportDiv', 'The authentication could not be removed', 'danger');
            return;
        }
        addAlert('alertJellyfinImportDiv', 'Authentication succesfully removed!', 'success');

        // let jellyfinInputGroup = document.createElement('div');
        // let jellyfinUsernameInput = document.createElement('input');
        // let jellyfinPasswordInput = document.createElement('input');
        // let jellyfinButtonInput = document.createElement('button');
        // let jellyfinUsernameLabel = document.createElement('label');
        // let jellyfinPasswordLabel = document.createElement('label');

        // jellyfinUsernameInput.type = 'text';
        // jellyfinUsernameInput.classList.add('form-control', 'disabled');
        // jellyfinUsernameInput.id = 'JellyfinUsernameInput';
        
        // jellyfinPasswordInput.type = 'password';
        // jellyfinPasswordInput.classList.add('form-control', 'disabled');
        // jellyfinPasswordInput.id = 'JellyfinPasswordInput';

        // jellyfinUsernameLabel.classList.add('input-group-text');
        // jellyfinUsernameLabel.for = 'JellyfinUsernameInput';
        // jellyfinUsernameLabel.
    }).catch(function (error) {
        console.log(error)
        addAlert('alertJellyfinImportDiv', 'The authentication could not be removed', 'danger');
    });
}