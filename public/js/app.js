if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/serviceWorker.js')
            .then(function (registration) {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(function (error) {
                console.log('Service Worker registration failed:', error);
            });
    })
}

const PASSWORD_MIN_LENGTH = 8
let currentModalVersion = 1;

document.addEventListener('DOMContentLoaded', function () {
    const theme = document.cookie.split('; ').find((row) => row.startsWith('theme='))?.split('=')[1] ?? 'light';
    const darkModeInput = document.getElementById('darkModeInput');
    if (darkModeInput != null) {
        darkModeInput.checked = theme !== 'light'
    }

    const logPlayModalSearchInput = document.getElementById('logPlayModalSearchInput');
    if (logPlayModalSearchInput != null) {
        logPlayModalSearchInput.addEventListener('change', updateLogPlayModalButtonState);
        logPlayModalSearchInput.addEventListener('input', updateLogPlayModalButtonState);
        logPlayModalSearchInput.addEventListener('keypress', loadLogModalSearchResultsOnEnterPress);

        new Datepicker(document.getElementById('logPlayModalWatchDateInput'), {
            format: document.getElementById('dateFormatJavascript').value,
            title: 'Watch date',
        })

        document.getElementById('logPlayModal').addEventListener('show.bs.modal', function () {
            document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()

            currentModalVersion++
        })

        loadLogPlayModalLocationOptions()

        document.getElementById('logPlayModal').addEventListener('hidden.bs.modal', function () {
            setLogPlayModalSearchSpinner(false)
            logPlayModalSearchInput.value = ''
            resetLogModalLogInputs()
            resetLogModalSearchResults()
            backToLogModalSearchResults()
        })
    }
});

function setLogPlayModalSearchSpinner(visible) {
    if (visible === true) {
        document.getElementById('logPlayModalSearchSpinner').classList.remove('d-none')

        return
    }

    document.getElementById('logPlayModalSearchSpinner').classList.add('d-none')
}

function setTheme(theme, force = false) {
    const htmlTag = document.getElementById('html');

    if (force === false && htmlTag.dataset.bsTheme === theme) {
        return
    }

    let cookieDate = new Date;
    cookieDate.setFullYear(cookieDate.getFullYear() + 1);
    document.cookie = 'theme=' + theme + ';path=/;expires=' + cookieDate.toUTCString() + ';'

    htmlTag.dataset.bsTheme = theme

    if (theme === 'light') {
        updateHtmlThemeColors('light', 'dark')

        return;
    }

    updateHtmlThemeColors('dark', 'light')
}

function updateHtmlThemeColors(mainColor, secondaryColor) {
    const logSpecificMovieButton = document.getElementById('logSpecificMovieButton');
    const moreSpecificMovieButton = document.getElementById('moreSpecificMovieButton');
    const toggleWatchDatesButton = document.getElementById('toggleWatchDatesButton');
    if (logSpecificMovieButton != null && moreSpecificMovieButton != null && toggleWatchDatesButton != null) {
        toggleWatchDatesButton.classList.add('btn-' + secondaryColor)
        toggleWatchDatesButton.classList.remove('btn-' + mainColor)
        logSpecificMovieButton.classList.add('btn-outline-' + secondaryColor)
        logSpecificMovieButton.classList.remove('btn-outline-' + mainColor)
        moreSpecificMovieButton.classList.add('btn-outline-' + secondaryColor)
        moreSpecificMovieButton.classList.remove('btn-outline-' + mainColor)
    }

    document.querySelectorAll('.activeItemButton').forEach((element) => {
        if (mainColor === 'dark') {
            element.classList.add('text-white');
            element.classList.remove('activeItemButtonActiveLight');
        } else {
            element.classList.remove('text-white');
            element.classList.add('activeItemButtonActiveLight');
        }
    });

    const darkModeNavHr = document.getElementById('darkModeNavHr');
    if (darkModeNavHr != null) {
        if (mainColor === 'dark') {
            darkModeNavHr.classList.remove('d-none')
        } else {
            darkModeNavHr.classList.add('d-none')
        }
    }

    // Add "theme-text-color" as a css class for text which should change main color when theme is updated
    document.querySelectorAll(".theme-text-color").forEach(elementWithThemeTextClass => {
        elementWithThemeTextClass.classList.add('text-' + secondaryColor)
        elementWithThemeTextClass.classList.remove('text-' + mainColor)
    });
}

async function searchTmdbWithLogModalSearchInput() {
    resetLogModalSearchResults()
    setLogPlayModalSearchSpinner(true)

    let targetModalVersion = currentModalVersion

    const data = await fetch('/settings/netflix/search', {
        signal: AbortSignal.timeout(4000),
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'query': document.getElementById('logPlayModalSearchInput').value
        })
    }).then(response => {
        if (!response.ok) {
            console.error(response);

            if (targetModalVersion !== currentModalVersion) {
                return null
            }

            setLogPlayModalSearchSpinner(false)
            displayLogModalTmdbSearchError('Something went wrong. Please try again.')

            return null;
        }

        return response.json()
    }).catch(function (error) {
        setLogPlayModalSearchSpinner(false)

        console.log(error)

        if (targetModalVersion !== currentModalVersion) {
            return null
        }

        if (error instanceof DOMException) {
            displayLogModalTmdbSearchError('Search request timed out. Please try again.')
        } else {
            displayLogModalTmdbSearchError('Something went wrong. Please try again.')
        }

        return null
    });

    if (data !== null && targetModalVersion === currentModalVersion) {
        setLogPlayModalSearchSpinner(false)
        loadLogModalSearchResults(data)
    }
}

function displayLogModalTmdbSearchError(message) {
    document.getElementById('logPlayModalSearchErrorAlert').innerHTML = message
    document.getElementById('logPlayModalSearchErrorAlert').classList.remove('d-none')
}

function loadLogModalSearchResults(data) {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');

    if (data.length == 0) {
        document.getElementById('logPlayModalSearchNoResultAlert').classList.remove('d-none')
        return
    }

    searchResultList.style.marginTop = '1rem'

    data.forEach((item, index) => {
        let listElement = document.createElement('li');
        listElement.className = 'list-group-item list-group-item-action'
        listElement.style.cursor = 'pointer'
        listElement.style.display = 'flex'
        listElement.style.alignItems = 'center'
        listElement.style.padding = '.5rem'
        listElement.id = 'searchResult-' + index

        let releaseYear = '?'
        if (item.release_date != null && item.release_date.length > 4) {
            releaseYear = item.release_date.substring(0, 4)
        }

        backdropPath = item.backdrop_path != null ? 'https://image.tmdb.org/t/p/w780' + item.backdrop_path : null;
        posterPath = item.poster_path != null ? 'https://image.tmdb.org/t/p/w92' + item.poster_path : '/images/placeholder-image.png';
        listElement.innerHTML = '<img src="' + posterPath + '" alt="Girl in a jacket" style="margin-right: .5rem;width: 3rem"><span>' + item.title + ' (' + releaseYear + ')</span>'

        listElement.dataset.tmdbId = item.id
        listElement.dataset.poster = item.poster_path
        listElement.dataset.title = item.title
        listElement.dataset.releaseYear = releaseYear

        listElement.addEventListener('click', selectLogModalTmdbItemForLogging);

        searchResultList.append(listElement);
    });
}

function resetLogModalSearchResults() {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');
    searchResultList.style.marginTop = ''
    searchResultList.innerHTML = ''

    document.getElementById('logPlayModalSearchErrorAlert').classList.add('d-none')
    document.getElementById('logPlayModalSearchNoResultAlert').classList.add('d-none')
}

function backToLogModalSearchResults() {
    document.getElementById('logPlayModalWatchDateDiv').classList.add('d-none')
    document.getElementById('logPlayModalFooterBackButton').classList.remove('d-none')
    document.getElementById('logPlayModalFooterWatchlistButton').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.remove('d-none')
    document.getElementById('logPlayModalFooter').classList.add('d-none')
    document.getElementById('logPlayModalTitle').innerHTML = 'Add movie'

    removeAlert('logPlayModalAlert')
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
    setRatingStars('logPlayModal', 0)
}

async function selectLogModalTmdbItemForLogging(event) {
    const item = event.target.closest(".list-group-item")

    document.getElementById('logPlayModalTitle').innerHTML = item.dataset.title + ' (' + item.dataset.releaseYear + ')'
    document.getElementById('logPlayModalTmdbIdInput').value = item.dataset.tmdbId

    const rating = await fetchRating(item.dataset.tmdbId)
    setRatingStars('logPlayModal', rating)

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalFooterBackButton').classList.remove('d-none')
    document.getElementById('logPlayModalFooterWatchlistButton').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')
}

function updateLogPlayModalButtonState() {
    if (document.getElementById('logPlayModalSearchInput').value !== '') {
        document.getElementById('logPlayModalSearchButton').disabled = false;

        return
    }

    document.getElementById('logPlayModalSearchButton').disabled = true;
}

function loadLogModalSearchResultsOnEnterPress(event) {
    // 13=enter, works better to check the key code because the key is named differently on mobile
    if (event.keyCode === 13) {
        searchTmdbWithLogModalSearchInput()
    }
}

function resetLogModalLogInputs() {
    document.getElementById('logPlayModalTmdbIdInput').value = ''
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
    removeAlert('logPlayModalAlert')
}

function addToWatchlist(context) {
    const tmdbId = document.getElementById(context + 'TmdbIdInput').value

    fetch('/add-movie-to-watchlist', {
        method: 'post', headers: {
            'Content-type': 'application/json',
        }, body: JSON.stringify({
            'tmdbId': tmdbId,
        })
    }).then(function (response) {
        if (response.status === 200) {
            location.reload();

            return
        }

        addAlert('logPlayModalAlert', 'Could not add to watchlist. Please try again.', 'danger')
    }).catch(function (error) {
        console.log(error)
        addAlert('logPlayModalAlert', 'Could not add to watchlist. Please try again.', 'danger')
    })
}

function logMovie(context) {
    const rating = getRatingFromStars(context)
    const tmdbId = document.getElementById(context + 'TmdbIdInput').value
    const watchDate = document.getElementById(context + 'WatchDateInput').value
    const comment = document.getElementById(context + 'CommentInput').value
    const locationId = document.getElementById(context + 'LocationInput').value
    const dateFormatPhp = document.getElementById('dateFormatPhp').value

    removeAlert('logPlayModalAlert')

    if (validateWatchDate(context, watchDate) === false) {
        return
    }

    fetch('/log-movie', {
        method: 'post', headers: {
            'Content-type': 'application/json',
        }, body: JSON.stringify({
            'tmdbId': tmdbId,
            'watchDate': watchDate,
            'comment': comment,
            'dateFormat': dateFormatPhp,
            'personalRating': rating,
            'locationId': locationId
        })
    }).then(function (response) {
        if (response.status === 200) {
            location.reload();

            return
        }

        addAlert('logPlayModalAlert', 'Could not add play. Please try again.', 'danger')
    }).catch(function (error) {
        console.log(error)
        addAlert('logPlayModalAlert', 'Could not add play. Please try again.', 'danger')
    })
}

async function showLogPlayModalWithSpecificMovie(tmdbId, movieTitle, releaseYear) {
    const myModal = new bootstrap.Modal(document.getElementById('logPlayModal'), {
        keyboard: false
    });

    const rating = await fetchRating(tmdbId)
    setRatingStars('logPlayModal', rating)

    document.getElementById('logPlayModalTmdbIdInput').value = tmdbId
    document.getElementById('logPlayModalTitle').innerHTML = movieTitle + ' (' + releaseYear + ')'

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')
    document.getElementById('logPlayModalFooterBackButton').classList.add('d-none')
    document.getElementById('logPlayModalFooterWatchlistButton').classList.add('d-none')

    myModal.show()
}

/**
 * Watch date logic starting here
 */
function validateWatchDate(context, watchDate) {
    if (isValidDate(watchDate) === false) {
        document.getElementById(context + 'WatchDateInput').style.borderStyle = 'solid'
        document.getElementById(context + 'WatchDateInput').style.borderColor = '#dc3545'
        document.getElementById(context + 'ratingStars').style.marginTop = '0'

        return false
    }

    document.getElementById(context + 'WatchDateInput').style.borderStyle = ''
    document.getElementById(context + 'WatchDateInput').style.borderColor = ''
    document.getElementById(context + 'RatingStars').style.marginTop = '0.5rem'

    return true
}

function isValidDate(dateString) {
    return true

    // First check for the pattern
    if (!/^\d{1,2}\.\d{1,2}\.\d{4}$/.test(dateString)) {
        return false
    }

    // Parse the date parts to integers
    var parts = dateString.split('.')
    var day = parseInt(parts[0], 10)
    var month = parseInt(parts[1], 10)
    var year = parseInt(parts[2], 10)

    // Check the ranges of month and year
    if (year < 1000 || year > 3000 || month == 0 || month > 12) return false

    var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

    // Adjust for leap years
    if (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)) monthLength[1] = 29

    // Check the range of the day
    return day > 0 && day <= monthLength[month - 1]
}

function getCurrentDate() {
    const today = new Date()
    const dd = String(today.getDate()).padStart(2, '0')
    const mm = String(today.getMonth() + 1).padStart(2, '0') //January is 0!
    const yyyy = today.getFullYear()

    if (document.getElementById('dateFormatJavascript').value === 'dd.mm.yyyy') {
        return dd + '.' + mm + '.' + yyyy
    }
    if (document.getElementById('dateFormatJavascript').value === 'dd.mm.yy') {
        return dd + '.' + mm + '.' + yyyy.toString().slice(-2)
    }
    if (document.getElementById('dateFormatJavascript').value === 'yy-mm-dd') {
        return yyyy.toString().slice(-2) + '-' + mm + '-' + dd
    }

    return yyyy + '-' + mm + '-' + dd
}

/**
 * Rating star logic starting here
 */
async function fetchRating(tmdbId) {
    const response = await fetch('/fetchMovieRatingByTmdbdId?tmdbId=' + tmdbId)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    return data.personalRating
}

function setRatingStars(context, newRating) {
    if (getRatingFromStars(context) == newRating) {
        newRating = 0
    }

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        const ratingStarElement = document.getElementById(context + 'RatingStar' + ratingStarNumber);

        ratingStarElement.classList.remove('bi-star-fill', 'bi-star')
        ratingStarElement.classList.add(ratingStarNumber <= newRating ? 'bi-star-fill' : ratingStarElement.classList.add('bi-star'))
    }
}

function getRatingFromStars(context) {
    let rating = 0

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        if (document.getElementById(context + 'RatingStar' + ratingStarNumber).classList.contains('bi-star') === true) {
            break
        }

        rating = ratingStarNumber
    }

    return rating
}

function updateRatingStars(context, e) {
    setRatingStars(context, e.dataset.value)
}

function toggleThemeSwitch() {
    if (document.getElementById('darkModeInput').checked === true) {
        setTheme('dark')

        return
    }

    setTheme('light')
}

function addAlert(parentDivId, message, color, withCloseButton = true, marginBottom = 1) {
    let closeButton = ''
    if (withCloseButton === true) {
        closeButton = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
    }

    document.getElementById(parentDivId).innerHTML =
        '<div class="alert alert-' + color + ' alert-dismissible" role="alert" style="margin-bottom: ' + marginBottom + 'rem">'
        + message +
        closeButton +
        '</div>'
}

function removeAlert(parentDivId) {
    document.getElementById(parentDivId).innerHTML = ''
}

async function logout() {
    await fetch('/api/authentication/token', {
        method: 'DELETE',
    });

    window.location.href = '/'
}

async function fetchLocations() {
    const response = await fetch(
        '/settings/locations',
        {signal: AbortSignal.timeout(4000)}
    )

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json();
}

async function loadLogPlayModalLocationOptions() {
    let locations;
    try {
        locations = await fetchLocations();
    } catch (error) {
        addAlert('logPlayModalAlert', 'Could not load locations', 'danger', false);

        return;
    }

    const selectElement = document.getElementById('logPlayModalLocationInput');

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