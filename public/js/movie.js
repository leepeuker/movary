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

function toggleWatchDateAdvancedMode() {
    if (document.getElementById('advancedWatchDateDetails').classList.contains('d-none')) {
        enableWatchDateAdvancedMode()

        return
    }

    disableWatchDateAdvancedMode()
}

function disableWatchDateAdvancedMode() {
    const advancedWatchDateDetails = document.getElementById('advancedWatchDateDetails');
    const advancedWatchDateModeButton = document.getElementById('advancedWatchDateModeButton');

    advancedWatchDateModeButton.innerHTML = 'Advanced mode'
    advancedWatchDateDetails.classList.add('d-none')
}

function enableWatchDateAdvancedMode() {
    const advancedWatchDateDetails = document.getElementById('advancedWatchDateDetails');
    const advancedWatchDateModeButton = document.getElementById('advancedWatchDateModeButton');

    advancedWatchDateDetails.classList.remove('d-none')
    advancedWatchDateModeButton.innerHTML = 'Simple mode'
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

async function loadWatchDateModal(watchDateListElement) {
    const modal = new bootstrap.Modal('#editWatchDateModal', {
        keyboard: false
    })

    document.getElementById('editWatchDateModalInput').value = watchDateListElement.dataset.watchDate;
    document.getElementById('editWatchDateModalPlaysInput').value = watchDateListElement.dataset.plays;
    document.getElementById('editWatchDateModalCommentInput').value = watchDateListElement.dataset.comment;
    document.getElementById('editWatchDateModalPositionInput').value = watchDateListElement.dataset.position;

    document.getElementById('originalWatchDate').value = watchDateListElement.dataset.watchDate;
    document.getElementById('originalWatchDatePlays').value = watchDateListElement.dataset.plays;

    disableWatchDateAdvancedMode()

    new Datepicker(document.getElementById('editWatchDateModalInput'), {
        format: document.getElementById('dateFormatJavascript').value,
        title: 'Watch date',
    })

    await loadLocationOptions()
    document.getElementById('editWatchDateModalLocationInput').value = watchDateListElement.dataset.location;

    modal.show()
}

async function loadLocationOptions() {
    let locations;
    try {
        locations = await fetchLocations();
    } catch (error) {
        addAlert('alertMovieModalDiv', 'Could not load locations', 'danger', false);

        return;
    }

    const selectElement = document.getElementById('editWatchDateModalLocationInput');

    selectElement.innerHTML = '';

    const fragment = document.createDocumentFragment();

    const optionElement = document.createElement('option');
    optionElement.value = '';
    optionElement.selected = true;
    fragment.appendChild(optionElement);

    locations.forEach(location => {
        const optionElement = document.createElement('option');
        optionElement.value = location.id;
        optionElement.textContent = location.name;

        fragment.appendChild(optionElement);
    });

    selectElement.appendChild(fragment)
}

function editWatchDate() {
    const originalWatchDate = document.getElementById('originalWatchDate').value;

    const newWatchDate = document.getElementById('editWatchDateModalInput').value;
    const newWatchDatePlays = document.getElementById('editWatchDateModalPlaysInput').value;
    const newPositionPlays = document.getElementById('editWatchDateModalPositionInput').value;
    const comment = document.getElementById('editWatchDateModalCommentInput').value;
    const locationId = document.getElementById('editWatchDateModalLocationInput').value;

    const apiUrl = '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history'

    $.ajax({
        url: apiUrl,
        type: 'POST',
        data: JSON.stringify({
            'newWatchDate': newWatchDate,
            'originalWatchDate': originalWatchDate,
            'plays': newWatchDatePlays,
            'comment': comment,
            'position': newPositionPlays,
            'dateFormat': document.getElementById('dateFormatPhp').value,
            'locationId': locationId
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

    disableMoreModalButtons();

    if (isOnWatchlist == null) {
        addToWatchlistRequest().then(() => {
            location.reload()
        }).catch(() => {
            addAlert('alertMovieOptionModalDiv', 'Could not add to Watchlist', 'danger')
            disableMoreModalButtons(false);
        })
    } else {
        removeFromWatchlistRequest().then(() => {
            location.reload()
        }).catch(() => {
            addAlert('alertMovieOptionModalDiv', 'Could not remove from Watchlist', 'danger')
            disableMoreModalButtons(false);
        })
    }
}

function refreshTmdbData() {
    removeAlert('alertMovieOptionModalDiv')

    disableMoreModalButtons();

    refreshTmdbDataRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh tmdb data', 'danger')
        disableMoreModalButtons(false);
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

function disableMoreModalButtons(disable = true) {
    document.getElementById('whereToWatchModalButton').disabled = disable;
    document.getElementById('refreshTmdbDataButton').disabled = disable;
    document.getElementById('refreshImdbRatingButton').disabled = disable;
    document.getElementById('watchlistButton').disabled = disable;
}

//region refreshImdbRating
function refreshImdbRating() {
    removeAlert('alertMovieOptionModalDiv')

    disableMoreModalButtons();

    refreshImdbRatingRequest().then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertMovieOptionModalDiv', 'Could not refresh imdb rating', 'danger')
        disableMoreModalButtons(false);
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

    document.getElementById('whereToWatchModal').addEventListener('hide.bs.modal', event => {
        document.getElementById('countrySelect').value = ''
        document.getElementById('streamTypeSelect').value = 'all'
    });

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
//endregion whereToWatchModal

function isTruncated(el) {
    return el.scrollWidth > el.clientWidth
}

const tooltipTriggerListCast = document.querySelectorAll('[data-bs-toggle="tooltip"]#castMemberName, [data-bs-toggle="tooltip"]#castCharacterName')
const tooltipCastList = [...tooltipTriggerListCast].map(tooltipTriggerEl => {
    if (isTruncated(tooltipTriggerEl) === false) {
        return
    }

    let placement = 'bottom';
    if (tooltipTriggerEl.classList.contains('card-header') === true) {
        placement = 'top'
    }

    new bootstrap.Tooltip(tooltipTriggerEl, {'placement': placement})
})

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]#editWatchDateModalPlays, [data-bs-toggle="tooltip"]#editWatchDateModalPlaysInput1')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
