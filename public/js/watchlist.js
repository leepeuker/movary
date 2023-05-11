const optionsModal = new bootstrap.Modal('#optionsModal');

async function removeFromWatchList() {
    let watchlistEntryId = document.getElementById('optionsModal').dataset.watchlistentryid;
    let url = '/movies/' + watchlistEntryId + '/remove-watchlist';

    await fetch(url, {
        method: 'GET'
    }).then(response => {
        if (!response.ok) {
            console.log(response);
            addAlert('watchlistAlert', 'Something has gone wrong, check the logs and please try again', 'danger');
            return;
        }

        location.reload()
    }).catch(function (error) {
        console.log(error)
        addAlert('watchlistAlert', 'Something has gone wrong, check the logs and please try again', 'danger');
    });
}

function openOptionsModal(trigger) {
    let watchlistEntryId = trigger.dataset.watchlistentryid;
    document.getElementById('optionsModal').setAttribute('data-watchlistEntryId', watchlistEntryId);
    optionsModal.show();
}

function goToMovie() {
    const currentRouteUsername = window.location.pathname.match(/(?<!^)\/([a-zA-Z0-9]+)\//)[1];
    let watchlistEntryId = document.getElementById('optionsModal').dataset.watchlistentryid;

    window.location.href = '/users/' + currentRouteUsername + '/movies/' + watchlistEntryId;
}
