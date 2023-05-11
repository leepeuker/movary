const optionsModal = new bootstrap.Modal('#optionsModal');

async function removeFromWatchList() {
    let movieId = document.getElementById('optionsModal').dataset.movieId;
    let url = '/movies/' + movieId + '/remove-watchlist';

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
    let movieId = trigger.dataset.movieid;
    document.getElementById('optionsModal').setAttribute('data-movieId', movieId);
    optionsModal.show();
}

function goToMovie() {
    const currentRouteUsername = window.location.pathname.match(/(?<!^)\/([a-zA-Z0-9]+)\//)[1];
    let movieId = document.getElementById('optionsModal').dataset.movieid;

    window.location.href = '/users/' + currentRouteUsername + '/movies/' + movieId;
}
