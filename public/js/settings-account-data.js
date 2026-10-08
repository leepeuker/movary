document.getElementById('deleteHistoryButton').addEventListener('click', async () => {
    const confirmed = await showConfirmationModal({
        title: 'Delete history',
        message: 'Are you sure you want to permanently delete your watch history? This action is not reversible!',
        confirmLabel: 'Delete history',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    const response = await deleteUserHistory();

    switch (response.status) {
        case 204:
            addAlert('alertDeletionsDiv', 'History deletion was successful', 'success');
            break;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            break;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

document.getElementById('deleteRatingsButton').addEventListener('click', async () => {
    const confirmed = await showConfirmationModal({
        title: 'Delete ratings',
        message: 'Are you sure you want to permanently delete your movie ratings? This action is not reversible!',
        confirmLabel: 'Delete ratings',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    const response = await deleteUserRatings();

    switch (response.status) {
        case 204:
            addAlert('alertDeletionsDiv', 'Ratings deletion successful', 'success');

            break;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            break;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

document.getElementById('deleteAccountButton').addEventListener('click', async () => {
    const confirmed = await showConfirmationModal({
        title: 'Delete account',
        message: 'Are you sure you want to permanently delete your account with all your data? This action is not reversible!',
        confirmLabel: 'Delete account',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    const response = await deleteUserAccount();

    switch (response.status) {
        case 204:
            window.location.href = APPLICATION_URL + '/'

            break;
        case 400:
            const errorMessage = await response.text();

            addAlert('alertDeletionsDiv', errorMessage, 'danger');

            break;
        default:
            addAlert('alertDeletionsDiv', 'Unexpected server error', 'danger');
    }
});

function deleteUserAccount() {
    return fetch(APPLICATION_URL + '/settings/account/delete-account', {
        method: 'DELETE',
    })
}

function deleteUserHistory() {
    return fetch(APPLICATION_URL + '/settings/account/delete-history', {
        method: 'DELETE',
    })
}

function deleteUserRatings() {
    return fetch(APPLICATION_URL + '/settings/account/delete-ratings', {
        method: 'DELETE',
    })
}
