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
        type: 'POST',
        data: JSON.stringify({
            'newWatchDate': newWatchDate,
            'originalWatchDate': originalWatchDate,
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
}

function loadRatingModal() {
    const editRatingModal = new bootstrap.Modal(document.getElementById('editRatingModal'), {
        keyboard: false
    });

    setRatingStars('editRatingModal', 0) // When this is removed the rating stars are reset to 0 every second time the edit modal is opened...  ¯\_(ツ)_/¯
    setRatingStars('editRatingModal', getRatingFromStars('movie'))

    editRatingModal.show()
}

function toggleWatchlist(isOnWatchlist) {
    removeAlert('alertMovieOptionModalDiv')

    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;
    document.getElementById('watchlistButton').disabled = true;

    if (isOnWatchlist == null) {
        addToWatchlistRequest().then(() => {
            location.reload()
        }).catch(() => {
            addAlert('alertMovieOptionModalDiv', 'Could not add to Watchlist', 'danger')
            document.getElementById('refreshTmdbDataButton').disabled = false;
            document.getElementById('refreshImdbRatingButton').disabled = false;
            document.getElementById('watchlistButton').disabled = false;
        })
    } else {
        removeFromWatchlistRequest().then(() => {
            location.reload()
        }).catch(() => {
            addAlert('alertMovieOptionModalDiv', 'Could not remove from Watchlist', 'danger')
            document.getElementById('refreshTmdbDataButton').disabled = false;
            document.getElementById('refreshImdbRatingButton').disabled = false;
            document.getElementById('watchlistButton').disabled = false;
        })
    }
}

function refreshTmdbData() {
    removeAlert('alertMovieOptionModalDiv')

    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;
    document.getElementById('watchlistButton').disabled = true;

    refreshTmdbDataRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh tmdb data', 'danger')
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('refreshImdbRatingButton').disabled = false;
        document.getElementById('watchlistButton').disabled = false;
    })
}

async function addToWatchlistRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/add-watchlist')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

async function removeFromWatchlistRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/remove-watchlist')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

async function refreshTmdbDataRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/refresh-tmdb')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

//region refreshImdbRating
function refreshImdbRating() {
    removeAlert('alertMovieOptionModalDiv')

    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;
    document.getElementById('watchlistButton').disabled = true;

    refreshImdbRatingRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh imdb rating', 'danger')
        document.getElementById('refreshTmdbDataButton').disabled = false;
        document.getElementById('refreshImdbRatingButton').disabled = false;
        document.getElementById('watchlistButton').disabled = false;
    })
}

async function refreshImdbRatingRequest() {
    const response = await fetch('/movies/' + getMovieId() + '/refresh-imdb')

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

//endregion refreshImdbRating

//region whereToWatchModal
async function showWhereToWatchModal() {
    const countrySelect = document.getElementById('countrySelect');
    const countrySelectValue = countrySelect.value;
    const streamType = document.getElementById('streamTypeSelect').value;

    if (countrySelectValue.length !== 2) {
        const currentUserDefaultCountry = document.getElementById('currentUserCountry').value

        if (currentUserDefaultCountry.length === 2) {
            countrySelect.value = currentUserDefaultCountry
        } else {
            const localStorageCountry = localStorage.getItem('country');

            if (localStorageCountry === undefined) {
                return
            }

            countrySelect.value = localStorageCountry
        }
    }

    loadWatchProviders(countrySelect.value, streamType)
}

async function loadWatchProviders(country, streamType) {
    document.getElementById('whereToWatchModalSearchSpinner').classList.remove('d-none')
    document.getElementById('whereToWatchModalProvidersInfo').classList.add('d-none')
    document.getElementById('whereToWatchModalProvidersList').classList.add('d-none')
    document.getElementById('whereToWatchModalProvidersList').innerHTML = ''
    removeAlert('alertWhereToWatchModalDiv')

    const watchProvidersHtml = await fetchWatchProviders(country, streamType)
        .catch(function (error) {
            addAlert('alertWhereToWatchModalDiv', 'Could not fetch watch providers', 'danger', false, 0)
            document.getElementById('whereToWatchModalSearchSpinner').classList.add('d-none')
        });

    if (watchProvidersHtml === undefined) {
        return
    }

    document.getElementById('whereToWatchModalProvidersList').innerHTML = watchProvidersHtml;

    document.getElementById('whereToWatchModalProvidersList').classList.remove('d-none')
    document.getElementById('whereToWatchModalSearchSpinner').classList.add('d-none')
}

document.getElementById('countrySelect').addEventListener('change', (e) => {
    const country = document.getElementById('countrySelect').value;
    const streamType = document.getElementById('streamTypeSelect').value;

    if (country === '') {
        document.getElementById('whereToWatchModalProvidersList').classList.add('d-none')
        document.getElementById('whereToWatchModalProvidersInfo').classList.add('d-none')
        document.getElementById('whereToWatchModalProvidersList').classList.add('d-none')
        document.getElementById('whereToWatchModalProvidersList').innerHTML = ''

        return;
    }

    localStorage.setItem('country', country)

    loadWatchProviders(country, streamType)
})

document.getElementById('streamTypeSelect').addEventListener('change', (e) => {
    const country = document.getElementById('countrySelect').value;
    const streamType = document.getElementById('streamTypeSelect').value;

    if (country === '') {
        return;
    }

    loadWatchProviders(country, streamType)
})

async function fetchWatchProviders(country, streamType) {
    const response = await fetch(
        '/movies/' + getMovieId() + '/watch-providers?country=' + country + '&streamType=' + streamType,
        {signal: AbortSignal.timeout(4000)}
    )

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.text();
}

function refreshWhereToWatchModal() {
    const country = document.getElementById('countrySelect').value;
    const streamType = document.getElementById('streamTypeSelect').value;

    if (country === '') {
        return;
    }

    loadWatchProviders(country, streamType)
}

document.getElementById('whereToWatchModal').addEventListener('hide.bs.modal', event => {
    document.getElementById('countrySelect').value = ''
    document.getElementById('streamTypeSelect').value = 'all'
});
//endregion whereToWatchModal
