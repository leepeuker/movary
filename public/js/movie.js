let originalRating

function deleteWatchDate() {
    const confirmed = confirm('Are you sure?')

    if (confirmed === false) {
        return
    }

    $.ajax({
        url: '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history',
        type: 'DELETE',
        data: JSON.stringify({
            'date': document.getElementById('originalWatchDate').value,
            'dateFormat': document.getElementById('dateFormatPhp').value
        }),
        success: function (data, textStatus, xhr) {
            window.location.reload()
        },
        error: function (xhr, textStatus, errorThrown) {
            addAlert('alertMovieModalDiv', 'Could not delete watch date.', 'danger')
        }
    })
}

function getMovieId() {
    return document.getElementById('movieId').value
}

function getRouteUsername() {
    return document.getElementById('username').value
}

function saveRating() {
    let newRating = getRatingFromStars('editRatingModal')

    fetch('/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/rating', {
        method: 'post',
        headers: {
            'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: 'rating=' + newRating
    }).then(function (response) {
        if (response.ok === false) {
            addAlert('editRatingModalDiv', 'Could not update rating.', 'danger')

            return
        }

        window.location.reload()
    })
}

function toggleWatchDates() {
    const toggleWatchDatesButton = document.getElementById('toggleWatchDatesButton');
    const watchDatesListDiv = document.getElementById('watchDatesListDiv');
    const toggleWatchDatesButtonDiv = document.getElementById('toggleWatchDatesButtonDiv');

    if (toggleWatchDatesButton.classList.contains('active') === true) {
        toggleWatchDatesButton.classList.remove('active')
        watchDatesListDiv.style.display = 'none';
        toggleWatchDatesButtonDiv.style.marginBottom = '0.5rem';
        watchDatesListDiv.style.marginBottom = '0';
    } else {
        toggleWatchDatesButton.classList.add('active')
        watchDatesListDiv.style.display = 'block';
        toggleWatchDatesButtonDiv.style.marginBottom = '0';
        watchDatesListDiv.style.marginBottom = '0.5rem';
    }
}

function loadWatchDateModal(watchDateListElement) {
    const modal = new bootstrap.Modal('#editWatchDateModal', {
        keyboard: false
    })

    document.getElementById('editWatchDateModalInput').value = watchDateListElement.dataset.watchDate;
    document.getElementById('editWatchDateModalPlaysInput').value = watchDateListElement.dataset.plays;
    document.getElementById('editWatchDateModalCommentInput').value = watchDateListElement.dataset.comment;

    console.log(document.getElementById('editWatchDateModalCommentInput').value)

    document.getElementById('originalWatchDate').value = watchDateListElement.dataset.watchDate;
    document.getElementById('originalWatchDatePlays').value = watchDateListElement.dataset.plays;

    new Datepicker(document.getElementById('editWatchDateModalInput'), {
        format: document.getElementById('dateFormatJavascript').value,
        title: 'Watch date',
    })

    modal.show()
}

function editWatchDate() {
    const originalWatchDate = document.getElementById('originalWatchDate').value;

    const newWatchDate = document.getElementById('editWatchDateModalInput').value;
    const newWatchDatePlays = document.getElementById('editWatchDateModalPlaysInput').value;
    const comment = document.getElementById('editWatchDateModalCommentInput').value;

    const apiUrl = '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history'

    $.ajax({
        url: apiUrl,
        type: 'DELETE',
        data: JSON.stringify({
            'date': originalWatchDate,
            'dateFormat': document.getElementById('dateFormatPhp').value
        }),
        success: function (data, textStatus, xhr) {
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: JSON.stringify({
                    'watchDate': newWatchDate,
                    'plays': newWatchDatePlays,
                    'comment': comment,
                    'dateFormat': document.getElementById('dateFormatPhp').value
                }),
                success: function (data, textStatus, xhr) {
                    window.location.reload()
                },
                error: function (xhr, textStatus, errorThrown) {
                    addAlert('alertMovieModalDiv', 'Could not update watch date', 'danger')
                }
            })
        },
        error: function (xhr, textStatus, errorThrown) {
            addAlert('alertMovieModalDiv', 'Could not delete old watch date', 'danger')
        }
    })
}

function loadRatingModal() {
    const editRatingModal = new bootstrap.Modal(document.getElementById('editRatingModal'), {
        keyboard: false
    });

    setRatingStars('editRatingModal', 0) // When this is removed the rating stars are reset to 0 every second time the edit modal is opened...  ¯\_(ツ)_/¯
    setRatingStars('editRatingModal', getRatingFromStars('movie'))

    editRatingModal.show()
}

function refreshTmdbData() {
    removeAlert('alertMovieOptionModalDiv')

    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;

    refreshTmdbDataRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh tmdb data', 'danger')
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('refreshImdbRatingButton').disabled = false;
    })
}

async function refreshTmdbDataRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/refresh-tmdb')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

function refreshImdbRating() {
    removeAlert('alertMovieOptionModalDiv')

    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;

    refreshImdbRatingRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh imdb rating', 'danger')
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('refreshImdbRatingButton').disabled = false;
    })
}

async function refreshImdbRatingRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/refresh-imdb')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}
