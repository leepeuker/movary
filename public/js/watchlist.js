const removeFromWatchlistModal = new bootstrap.Modal('#removeFromWatchlistModal');

async function removeFromWatchList() {
    let watchlistEntryId = document.getElementById('removeFromWatchlistModal').dataset.watchlistentryid;
    let url = '/movies/' + watchlistEntryId + '/remove-watchlist';
    await fetch(url, {
        method: 'GET'
    }).then(response => {
        if (!response.ok) {
            console.log(response);
            addAlert('watchlistAlert', 'Something has gone wrong, check the logs and please try again', 'danger');
            return;
        }
        addAlert('watchlistAlert', 'Item has succesfully been removed from watchlist', 'success');
        document.querySelector('i[data-watchlistEntryId="'+watchlistEntryId+'"]').parentElement.parentElement.remove();
    }).catch(function (error) {
        console.log(error)
        addAlert('watchlistAlert', 'Something has gone wrong, check the logs and please try again', 'danger');
    });
    removeFromWatchlistModal.hide();
}

function openRemoveWatchlistModal(trigger) {
    let watchlistEntryId = trigger.dataset.watchlistentryid;
    document.getElementById('removeFromWatchlistModal').setAttribute('data-watchlistEntryId', watchlistEntryId);
    removeFromWatchlistModal.show();
}