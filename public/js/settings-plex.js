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

async function sendPlexImportRequest() {
    disable(document.getElementById('triggerPlexImportBtn'));
    await  createSpinner();
    await fetch('/settings/plex/importplexhistory', {
        method: 'POST'
    }).then(response => {
        if(!response.ok) {
            processPlexHistoryImportError(response.status);
            console.error(response.text);
        } else {
            let plexHistoryLogDiv = document.getElementById('plexImportHistoryLog');
            plexHistoryLogDiv.innerText = 'Plex History has succesfully been imported!';
            document.getElementsByClassName('spinner-border')[0].remove();
        }
    }).catch(function(error) {
        console.error(error);
        processPlexHistoryImportError(500);
    })
    enable(document.getElementById('triggerPlexImportBtn'));
}

function processPlexHistoryImportError(statusCode) {
    let text;
    let plexHistoryLogDiv = document.getElementById('plexImportHistoryLog');

    if (statusCode === 400) {
        text = 'Error: The user input is invalid. Check if the server url is correct, if the server is on and accessible with the Plex account and if the user has authorized the Movary application.';
    } else if (statusCode === 401) {
        text = 'Error: Movary has no authorization.Check if the server url is correct, if the server is on and accessible with the Plex account and if the user has authorized the Movary application.';
    } else {
        text = 'Error: Please check your browser console log (F12 -> Console) and the Movary application logs and report the error via <a href="https://github.com/leepeuker/movary" target="_blank">Github</a>.';
    }

    plexHistoryLogDiv.innerHTML = text;
}

async function createSpinner() {
    let div = document.createElement('div');
    let span = document.createElement('span');
    div.className = 'spinner-border';
    span.className = 'visually-hidden';
    span.innerText = 'Loading...';
    div.append(span);
    document.getElementById('plexImportHistoryLog').append(div);
}

function enable(el) {
    el.classList.remove('disabled');
    el.removeAttribute('disabled');
}

function disable(el) {
    el.classList.add('disabled');
    el.setAttribute('disabled', '');
}