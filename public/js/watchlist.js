const optionsModal = new bootstrap.Modal('#optionsModal');
optionsModal.hide()

async function removeFromWatchList() {
    let movieId = document.getElementById('optionsModal').dataset.movieid;
    let url = APPLICATION_URL + '/movies/' + movieId + '/remove-watchlist';

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
    const pathSegments = window.location.pathname.split('/').filter(Boolean);
    const userIndex = pathSegments.indexOf('users');

    if (userIndex === -1 || pathSegments.length <= userIndex + 1) {
        console.error("Username could not be extracted from the URL.");
        return;
    }

    const currentRouteUsername = pathSegments[userIndex + 1];
    const movieId = document.getElementById('optionsModal')?.dataset.movieid;

    if (!movieId) {
        console.error("movieId is missing in #optionsModal dataset.");
        return;
    }

    if (typeof APPLICATION_URL === 'undefined') {
        console.error("APPLICATION_URL is not defined.");
        return;
    }

    window.location.href = `${APPLICATION_URL}/users/${currentRouteUsername}/movies/${movieId}`;
}

document.getElementById('optionsModal').addEventListener('hidden.bs.modal', () => {
    document.querySelectorAll('.card').forEach((element) => {
        element.style.filter = 'brightness(100%)'
    });
})
