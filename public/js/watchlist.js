const optionsModal = new bootstrap.Modal('#optionsModal');
optionsModal.hide()

async function removeFromWatchList() {
    let movieId = document.getElementById('optionsModal').dataset.movieid;
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
    document.getElementById('card-' + movieId).style.filter = 'brightness(30%)'
}

function goToMovie() {
    const currentRouteUsername = window.location.pathname.match(/(?<!^)\/([a-zA-Z0-9]+)\//)[1];
    let movieId = document.getElementById('optionsModal').dataset.movieid;

    window.location.href = '/users/' + currentRouteUsername + '/movies/' + movieId;
}

document.getElementById('optionsModal').addEventListener('hidden.bs.modal', () => {
    document.querySelectorAll('.card').forEach((element) => {
        element.style.filter = 'brightness(100%)'
    });
})
