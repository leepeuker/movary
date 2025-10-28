document.getElementById('deleteHistoryButton').addEventListener('click', async () => {
    if (!window.confirm('Are you sure you want to delete your watch history?')) {
        return;
    }

    const response = await deleteUserHistory();

    switch (response.status) {
        case 200:
            addAlert('alertDeletionsDiv', 'History deletion was successful', 'success');

            return;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

function deleteUserHistory() {
    return fetch(APPLICATION_URL + '/settings/account/delete-history', {
        method: 'DELETE',
    })
}

document.getElementById('deleteRatingsButton').addEventListener('click', async () => {
    if (!window.confirm('Are you sure you want to delete your movie ratings?')) {
        return;
    }

    const response = await deleteUserRatings();

    switch (response.status) {
        case 200:
            addAlert('alertDeletionsDiv', 'Ratings deletion successful', 'success');

            return;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

function deleteUserRatings() {
    return fetch(APPLICATION_URL + '/settings/account/delete-ratings', {
        method: 'DELETE',
    })
}

document.getElementById('deleteAccountButton').addEventListener('click', async () => {
    if (!window.confirm('Are you sure you want to delete your account with all your data?')) {
        return;
    }

    const response = await deleteUserAccount();

    switch (response.status) {
        case 200:
            window.location.href = APPLICATION_URL + '/'

            return;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            return;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

function deleteUserAccount() {
    return fetch(APPLICATION_URL + '/settings/account/delete-account', {
        method: 'DELETE',
    })
}