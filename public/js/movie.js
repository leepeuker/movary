let originalRating

const POSTER_BATCH_SIZE = 12
let availablePosters = null
let renderedPosterCount = 0

document.addEventListener('DOMContentLoaded', () => {
    const changePosterModal = document.getElementById('changePosterModal')
    if (changePosterModal === null) {
        return
    }

    changePosterModal.addEventListener('show.bs.modal', loadPosterChoices)
    document.getElementById('showMorePostersButton').addEventListener('click', renderNextPosterBatch)
    document.getElementById('useSelectedPosterButton').addEventListener('click', useSelectedPoster)
    document.getElementById('restoreDefaultPosterButton').addEventListener('click', restoreDefaultPoster)
})

async function loadPosterChoices() {
    if (availablePosters !== null) {
        return
    }

    const loading = document.getElementById('posterSelectionLoading')
    const error = document.getElementById('posterSelectionError')
    loading.classList.remove('d-none')
    error.classList.add('d-none')

    try {
        const response = await fetch(APPLICATION_URL + '/movies/' + getMovieId() + '/posters')
        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        availablePosters = data.posters
        document.getElementById('restoreDefaultPosterButton').classList.toggle('d-none', data.hasOverride !== true)
        loading.classList.add('d-none')

        if (availablePosters.length === 0) {
            document.getElementById('posterSelectionEmpty').classList.remove('d-none')
            return
        }

        renderNextPosterBatch()
    } catch (requestError) {
        loading.classList.add('d-none')
        error.classList.remove('d-none')
    }
}

function renderNextPosterBatch() {
    const grid = document.getElementById('posterSelectionGrid')
    const postersToRender = availablePosters.slice(renderedPosterCount, renderedPosterCount + POSTER_BATCH_SIZE)

    postersToRender.forEach((poster, offset) => {
        const index = renderedPosterCount + offset
        const column = document.createElement('div')
        column.className = 'col'

        const input = document.createElement('input')
        input.type = 'radio'
        input.className = 'btn-check'
        input.name = 'moviePoster'
        input.id = 'moviePoster' + index
        input.value = poster.path
        input.addEventListener('change', () => {
            document.getElementById('useSelectedPosterButton').disabled = false
        })

        const label = document.createElement('label')
        label.className = 'movie-poster-option'
        label.htmlFor = input.id

        const image = document.createElement('img')
        image.className = 'img-fluid rounded'
        image.src = poster.previewUrl
        image.alt = 'TMDB poster option ' + (index + 1)
        image.loading = 'lazy'

        label.appendChild(image)
        column.appendChild(input)
        column.appendChild(label)
        grid.appendChild(column)
    })

    renderedPosterCount += postersToRender.length
    document.getElementById('showMorePostersButton').classList.toggle(
        'd-none',
        renderedPosterCount >= availablePosters.length,
    )
}

async function useSelectedPoster() {
    const selectedPoster = document.querySelector('input[name="moviePoster"]:checked')
    if (selectedPoster === null) {
        return
    }

    await updateMoviePoster('PUT', JSON.stringify({posterPath: selectedPoster.value}))
}

async function restoreDefaultPoster() {
    const confirmed = await showConfirmationModal({
        title: 'Restore default poster',
        message: 'Are you sure you want to restore the default poster?',
        confirmLabel: 'Restore default',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    await updateMoviePoster('DELETE')
}

async function updateMoviePoster(method, body = null) {
    const saveButton = document.getElementById('useSelectedPosterButton')
    const restoreButton = document.getElementById('restoreDefaultPosterButton')
    saveButton.disabled = true
    restoreButton.disabled = true

    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/movies/' + getMovieId() + '/poster', {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: body,
        })
        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        window.location.reload()
    } catch (requestError) {
        const posterModal = document.getElementById('changePosterModal')
        if (posterModal.classList.contains('show') === false) {
            bootstrap.Modal.getOrCreateInstance(posterModal).show()
        }

        document.getElementById('posterSelectionError').textContent = 'Could not update the movie poster.'
        document.getElementById('posterSelectionError').classList.remove('d-none')
        saveButton.disabled = document.querySelector('input[name="moviePoster"]:checked') === null
        restoreButton.disabled = false
    }
}

async function deleteWatchDate() {
    const confirmed = await showConfirmationModal({
        title: 'Delete watch date',
        message: 'Are you sure you want to delete this watch date?',
        confirmLabel: 'Delete watch date',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history',
            {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    'date': document.getElementById('originalWatchDate').value,
                    'dateFormat': document.getElementById('dateFormatPhp').value
                }),
            },
        )

        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        window.location.reload()
    } catch (requestError) {
        addAlert('alertMovieModalDiv', 'Could not delete watch date.', 'danger')
        bootstrap.Modal.getOrCreateInstance('#editWatchDateModal').show()
    }
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

    advancedWatchDateDetails.classList.add('d-none')
    updateWatchDateAdvancedModeButton('Advanced mode', false)
}

function enableWatchDateAdvancedMode() {
    const advancedWatchDateDetails = document.getElementById('advancedWatchDateDetails');

    advancedWatchDateDetails.classList.remove('d-none')
    updateWatchDateAdvancedModeButton('Simple mode', true)
}

function updateWatchDateAdvancedModeButton(label, active) {
    const button = document.getElementById('advancedWatchDateModeButton')
    button.setAttribute('data-bs-title', label)
    button.setAttribute('aria-label', label)
    button.classList.toggle('active', active)
    bootstrap.Tooltip.getOrCreateInstance(button).setContent({'.tooltip-inner': label})
}

function getMovieId() {
    return document.getElementById('movieId').value
}

function getRouteUsername() {
    return document.getElementById('username').value
}

function saveRating() {
    let newRating = getRatingFromStars('editRatingModal')

    fetchWithCsrf(APPLICATION_URL + '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/rating', {
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
    updateLocationSelectPlaceholderStyle(document.getElementById('editWatchDateModalLocationInput'))

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
    optionElement.textContent = 'Location';
    optionElement.classList.add('text-body-secondary');
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

async function editWatchDate() {
    const originalWatchDate = document.getElementById('originalWatchDate').value;

    const newWatchDate = document.getElementById('editWatchDateModalInput').value;
    const newWatchDatePlays = document.getElementById('editWatchDateModalPlaysInput').value;
    const newPositionPlays = document.getElementById('editWatchDateModalPositionInput').value;
    const comment = document.getElementById('editWatchDateModalCommentInput').value;
    const locationId = document.getElementById('editWatchDateModalLocationInput').value;
    const postToMastodon = document.getElementById('editWatchDateModalPostToMastodonInput').checked;

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history',
            {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    'newWatchDate': newWatchDate,
                    'originalWatchDate': originalWatchDate,
                    'plays': newWatchDatePlays,
                    'comment': comment,
                    'position': newPositionPlays,
                    'dateFormat': document.getElementById('dateFormatPhp').value,
                    'locationId': locationId,
                    'postToMastodon': postToMastodon
                }),
            },
        )

        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        window.location.reload()
    } catch (requestError) {
        addAlert('alertMovieModalDiv', 'Could not update watch date', 'danger')
    }
}

function loadRatingModal() {
    const editRatingModal = new bootstrap.Modal(document.getElementById('editRatingModal'), {
        keyboard: false
    });

    setRatingStars('editRatingModal', 0) // When this is removed the rating stars are reset to 0 every second time the edit modal is opened...  ¯\_(ツ)_/¯
    setRatingStars('editRatingModal', getRatingFromStars('movie'))

    editRatingModal.show()
}

async function toggleWatchlist(isOnWatchlist) {
    if (isOnWatchlist == null) {
        try {
            await addToWatchlistRequest()
            location.reload()
        } catch (error) {
            await showMessageModal({
                title: 'Watchlist update failed',
                message: 'Could not add to watchlist.',
            })
        }

        return
    }

    const confirmed = await showConfirmationModal({
        title: 'Remove from watchlist',
        message: 'Are you sure you want to remove this movie from your watchlist?',
        confirmLabel: 'Remove from watchlist',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    try {
        await removeFromWatchlistRequest()
        location.reload()
    } catch (error) {
        await showMessageModal({
            title: 'Watchlist update failed',
            message: 'Could not remove from watchlist.',
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
    const response = await fetchWithCsrf(
        APPLICATION_URL + '/movies/' + getMovieId() + '/add-watchlist',
        {method: 'POST'}
    )

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

async function removeFromWatchlistRequest() {
    const response = await fetchWithCsrf(
        APPLICATION_URL + '/movies/' + getMovieId() + '/remove-watchlist',
        {method: 'DELETE'}
    )

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

async function refreshTmdbDataRequest() {
    const response = await fetchWithCsrf(
        APPLICATION_URL + '/movies/' + getMovieId() + '/refresh-tmdb',
        {method: 'POST'}
    )

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
    const response = await fetchWithCsrf(
        APPLICATION_URL + '/movies/' + getMovieId() + '/refresh-imdb',
        {method: 'POST'}
    )

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
        APPLICATION_URL + '/movies/' + getMovieId() + '/watch-providers?country=' + country + '&streamType=' + streamType,
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

async function saveMovieReview() {
    const review = document.getElementById('movieReviewInput').value
    const body = new URLSearchParams({review: review})
    if (document.getElementById('movieReviewContainsSpoilers').checked === true) {
        body.set('containsSpoilers', '1')
    }

    const response = await fetchWithCsrf(
        APPLICATION_URL + '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/review',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString(),
        },
    )

    if (response.status === 204) {
        window.location.reload()
        return
    }

    const message = response.status === 400 ? await response.text() : 'Could not save review.'
    addAlert('movieReviewAlert', message, 'danger')
}

async function deleteMovieReview() {
    const confirmed = await showConfirmationModal({
        title: 'Delete review',
        message: 'Are you sure you want to permanently delete this review?',
        confirmLabel: 'Delete review',
        confirmClass: 'btn-danger',
    })
    if (confirmed === false) {
        return
    }

    const response = await fetchWithCsrf(
        APPLICATION_URL + '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/review',
        {method: 'DELETE'},
    )

    if (response.status === 204) {
        window.location.reload()
        return
    }

    addAlert('movieReviewAlert', 'Could not delete review.', 'danger')
}

const tooltipTriggerListCast = document.querySelectorAll('[data-bs-toggle="tooltip"].castMemberName, [data-bs-toggle="tooltip"].castCharacterName')
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

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]#editWatchDateModalPlays, [data-bs-toggle="tooltip"]#editWatchDateModalPlaysInput1, [data-bs-toggle="tooltip"].editWatchDateInputIcon, [data-bs-toggle="tooltip"].directorName, [data-bs-toggle="tooltip"]#bookmarkIcon')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
