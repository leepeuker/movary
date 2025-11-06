async function verifyTraktCredentials() {
    const username = document.getElementById('traktUserName').value;
    const clientId = document.getElementById('traktClientId').value;

    if (username == false || clientId == false) {
        addAlertMessage('Username or client id missing', 'warning')

        return
    }

    document.getElementById('verifyButton').disabled = true;
    alertPlaceholder.innerHTML = ''

    const response = await fetch(APPLICATION_URL + '/settings/trakt/verify-credentials', {
        method: 'post',
        headers: {
            'Content-type': 'application/json',
        },
        body: JSON.stringify({
            'username': username,
            'clientId': clientId
        })
    })

    if (response.ok) {
        addAlertMessage('Credentials are valid', 'success')
    } else if (response.status === 400) {
        addAlertMessage('Credentials are not valid', 'danger')
    } else {
        addAlertMessage('Something went wrong...', 'warning')

        console.log(`Api error on trakt credentials verification with status: ${response.status}`);
    }

    document.getElementById('verifyButton').disabled = false;
}

const alertPlaceholder = document.getElementById('alerts')
const addAlertMessage = (message, type) => {
    alertPlaceholder.innerHTML = [`<div class="alert alert-${type} alert-dismissible" role="alert">`, `   <div>${message}</div>`, '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>', '</div>'].join('')
}

function toggleTableVisibility() {
    document.getElementById('importTable').classList.remove('d-none')
    document.getElementById('showImportTableButton').disabled = true
}

async function traktImportRatings() {
    const importRatingsModal = bootstrap.Modal.getInstance('#traktImportRatings');

    const response = await importTraktRatings();

    importRatingsModal.hide()

    switch (response.status) {
        case 204:
            addAlert('alertRatingsImportDiv', 'Ratings import queued successfully', 'success');
            break;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertRatingsImportDiv', errorMessage, 'danger');

            break;
        default:
            addAlert('alertRatingsImportDiv', 'Unexpected server error', 'danger');
    }
}

function importTraktRatings() {
    return fetch(APPLICATION_URL + '/jobs/schedule/trakt-ratings-sync', {
        method: 'POST',
    })
}

async function traktImportHistory() {
    const importHistoryModal = bootstrap.Modal.getInstance('#traktImportHistory');

    const response = await importTraktHistory();

    importHistoryModal.hide()

    switch (response.status) {
        case 204:
            addAlert('alertHistoryImportDiv', 'History import queued successfully', 'success');
            break;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertHistoryImportDiv', errorMessage, 'danger');

            break;
        default:
            addAlert('alertHistoryImportDiv', 'Unexpected server error', 'danger');
    }
}

function importTraktHistory() {
    return fetch(APPLICATION_URL + '/jobs/schedule/trakt-history-sync', {
        method: 'POST',
    })
}