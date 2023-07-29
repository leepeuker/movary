function disableElement(el) {
    el.classList.add('disabled');
    el.disabled = true;
}

function enableElement(el) {
    el.classList.remove('disabled');
    el.disabled = false;
}

function insertAuthenticationForm() {
    let jellyfinInputGroup = document.createElement('div');
    let jellyfinUsernameInput = document.createElement('input');
    let jellyfinPasswordInput = document.createElement('input');
    let jellyfinButtonInput = document.createElement('button');
    let jellyfinUsernameLabel = document.createElement('label');
    let jellyfinPasswordLabel = document.createElement('label');

    jellyfinUsernameInput.type = 'text';
    jellyfinUsernameInput.classList.add('form-control', 'disabled');
    jellyfinUsernameInput.id = 'JellyfinUsernameInput';

    jellyfinPasswordInput.type = 'password';
    jellyfinPasswordInput.classList.add('form-control', 'disabled');
    jellyfinPasswordInput.id = 'JellyfinPasswordInput';

    jellyfinUsernameLabel.classList.add('input-group-text');
    jellyfinUsernameLabel.for = 'JellyfinUsernameInput';
    jellyfinUsernameLabel.innerText = 'Jellyfin username';

    jellyfinPasswordLabel.classList.add('input-group-text');
    jellyfinPasswordLabel.for = 'JellyfinPasswordInput';
    jellyfinPasswordLabel.innerText = 'Jellyfin password';

    jellyfinButtonInput.type = 'button';
    jellyfinButtonInput.classList.add('btn', 'btn-success', 'mb-3');
    jellyfinButtonInput.addEventListener('click', authenticateJellyfinAccount);
    jellyfinButtonInput.id = 'jellyfinAuthenticationBtn';
    jellyfinButtonInput.innerText = 'Authenticate with Jellyfin';

    jellyfinInputGroup.classList.add('input-group', 'mb-3');

    jellyfinInputGroup.append(jellyfinUsernameLabel, jellyfinUsernameInput, jellyfinPasswordLabel, jellyfinPasswordInput);

    document.getElementById('jellyfinModalBody').append(jellyfinInputGroup, jellyfinButtonInput);
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
        if (jellyfinServerUrl !== '') {
            document.getElementById('authenticateWithJellyfinButton').disabled = false
        } else {
            document.getElementById('authenticateWithJellyfinButton').disabled = true
        }
    }).catch(function (error) {
        console.log(error);
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinServerUrlDiv', 'Could not save server url', 'danger');
    });
}

async function verifyJellyfinServerUrl() {
    document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.remove('d-none')
    removeAlert('alertJellyfinServerUrlDiv');

    await fetch('/settings/jellyfin/server-url-verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'JellyfinServerUrl': document.getElementById('jellyfinServerUrlInput').value
        })
    }).then(response => {
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        if (!response.ok) {
            addAlert('alertJellyfinServerUrlDiv', 'Could not verify server url', 'danger');

            return;
        }
        addAlert('alertJellyfinServerUrlDiv', 'Server url was verified', 'success');
    }).catch(function (error) {
        console.log(error)
        document.getElementById('alertJellyfinServerUrlLoadingSpinner').classList.add('d-none')
        addAlert('alertJellyfinServerUrlDiv', 'Could not verify server url', 'danger');
    });
}

async function authenticateJellyfinAccount() {
    console.log(document.getElementById('jellyfinUsernameInput').value)
    await fetch('/settings/jellyfin/authenticate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            'username': document.getElementById('jellyfinUsernameInput').value,
            'password': document.getElementById('jellyfinPasswordInput').value,
        })
    }).then(response => {
        if (!response.ok) {
            addAlert('alertJellyfinAuthenticationModalDiv', 'Could not authenticate with Jellyfin', 'danger', 0);

            return;
        }

        location.reload();
    }).catch(function (error) {
        console.error(error)
        addAlert('alertJellyfinAuthenticationModalDiv', 'Could not authenticate with Jellyfin', 'danger', 0);
    });
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
