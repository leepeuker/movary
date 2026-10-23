if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register(SERVICE_WORKER_URL, {updateViaCache: 'none'})
            .then(function (registration) {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(function (error) {
                console.log('Service Worker registration failed:', error);
            });
    })
}

let currentModalVersion = 1;

function fetchWithCsrf(input, options = {}) {
    const requestUrl = new URL(input instanceof Request ? input.url : input, window.location.href)
    const method = (options.method ?? (input instanceof Request ? input.method : 'GET')).toUpperCase()
    const unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE']

    if (requestUrl.origin !== window.location.origin || unsafeMethods.includes(method) === false) {
        return window.fetch(input, options)
    }

    const headers = new Headers(options.headers ?? (input instanceof Request ? input.headers : undefined))
    headers.set('X-CSRF-Token', getCsrfToken())

    return window.fetch(input, {...options, headers: headers})
}

function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (token == null || token === '') {
        throw new Error('Missing CSRF token')
    }

    return token
}

function setLocalizedUtcDateTime(element, utcDateTime, includeTimeZone = true) {
    if (utcDateTime === '-') {
        element.textContent = '-'
        element.removeAttribute('title')
        return
    }

    const date = new Date(utcDateTime.replace(' ', 'T') + 'Z')
    if (Number.isNaN(date.getTime())) {
        element.textContent = utcDateTime + ' UTC'
        element.removeAttribute('title')
        return
    }

    const dateTimeParts = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZoneName: 'short',
        hourCycle: 'h23',
    }).formatToParts(date)
    const parts = Object.fromEntries(dateTimeParts.map((part) => [part.type, part.value]))

    element.textContent = parts.year + '-' + parts.month + '-' + parts.day + ' ' +
        parts.hour + ':' + parts.minute + ':' + parts.second +
        (includeTimeZone ? ' ' + parts.timeZoneName : '')
    element.title = utcDateTime + ' UTC'
}

document.addEventListener('DOMContentLoaded', function () {
    const localTimeZone = new Intl.DateTimeFormat(undefined, {timeZoneName: 'short'})
        .formatToParts(new Date())
        .find((part) => part.type === 'timeZoneName').value
    document.querySelectorAll('[data-local-time-zone-label]').forEach((element) => {
        element.textContent += ' (' + localTimeZone + ')'
    })

    document.querySelectorAll('[data-utc-date-time]').forEach((element) => {
        setLocalizedUtcDateTime(element, element.dataset.utcDateTime, false)
    })

    document.querySelectorAll('.settings-table-action').forEach((button) => {
        bootstrap.Tooltip.getOrCreateInstance(button)
    })

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

        document.querySelectorAll('#logPlayModal [data-bs-toggle="tooltip"]').forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })

        document.getElementById('logPlayModal').addEventListener('show.bs.modal', function () {
            document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
            document.getElementById("openedFromMoviePage").setAttribute("value", "")

            currentModalVersion++
        })

        loadLogPlayModalLocationOptions()

        document.getElementById('logPlayModalLocationInput').addEventListener('change', function () {
            updateLocationSelectPlaceholderStyle(this)
        })

        document.getElementById('logPlayModal').addEventListener('hidden.bs.modal', function () {
            setLogPlayModalSearchSpinner(false)
            logPlayModalSearchInput.value = ''
            resetLogModalLogInputs()
            resetLogModalSearchResults()
            backToLogModalSearchResults()
        })
    }

    const editPlayLocationInput = document.getElementById('editWatchDateModalLocationInput')
    if (editPlayLocationInput !== null) {
        editPlayLocationInput.addEventListener('change', function () {
            updateLocationSelectPlaceholderStyle(this)
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
    document.querySelector('meta[name="theme-color"]')?.setAttribute(
        'content',
        theme === 'dark' ? '#212529' : '#ffffff',
    )

    if (theme === 'light') {
        updateHtmlThemeColors('light', 'dark')

        return;
    }

    updateHtmlThemeColors('dark', 'light')
}

function updateHtmlThemeColors(mainColor, secondaryColor) {
    const toggleWatchDatesButton = document.getElementById('toggleWatchDatesButton');
    const logSpecificMovieButton = document.getElementById('logSpecificMovieButton');
    const reviewButton = document.getElementById('reviewButton');
    const watchlistButton = document.getElementById('watchlistButton');
    const moreSpecificMovieButton = document.getElementById('moreSpecificMovieButton');
    if (logSpecificMovieButton != null && moreSpecificMovieButton != null && toggleWatchDatesButton != null) {
        toggleWatchDatesButton.classList.add('btn-' + secondaryColor)
        toggleWatchDatesButton.classList.remove('btn-' + mainColor)
        logSpecificMovieButton.classList.add('btn-outline-' + secondaryColor)
        logSpecificMovieButton.classList.remove('btn-outline-' + mainColor)
        reviewButton.classList.add('btn-outline-' + secondaryColor)
        reviewButton.classList.remove('btn-outline-' + mainColor)
        watchlistButton.classList.add('btn-outline-' + secondaryColor)
        watchlistButton.classList.remove('btn-outline-' + mainColor)
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

function updateLocationSelectPlaceholderStyle(selectElement) {
    selectElement.classList.toggle('text-body-secondary', selectElement.value === '')
}

async function searchTmdbWithLogModalSearchInput() {
    resetLogModalSearchResults()
    setLogPlayModalSearchSpinner(true)

    let targetModalVersion = currentModalVersion
    const searchParameters = new URLSearchParams({
        search: document.getElementById('logPlayModalSearchInput').value,
    })

    const data = await fetch(APPLICATION_URL + '/movies/search?' + searchParameters.toString(), {
        signal: AbortSignal.timeout(4000),
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
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
        loadLogModalSearchResults(data.results || [])
    }
}

function displayLogModalTmdbSearchError(message) {
    document.getElementById('logPlayModalSearchErrorAlert').textContent = message
    document.getElementById('logPlayModalSearchErrorAlert').classList.remove('d-none')
}

function formatMovieTitle(localizedTitle, originalTitle) {
    if (!localizedTitle) {
        return originalTitle || ''
    }

    if (!originalTitle || originalTitle === localizedTitle) {
        return localizedTitle
    }

    if (MOVIE_TITLE_DISPLAY === 'original') {
        return originalTitle
    }

    if (MOVIE_TITLE_DISPLAY === 'both') {
        return localizedTitle + ' (' + originalTitle + ')'
    }

    return localizedTitle
}

function encodeBase64(value) {
    const utf8Bytes = new TextEncoder().encode(value)

    return btoa(String.fromCharCode(...utf8Bytes))
}

function createMovieStatusIcon(iconClass, title) {
    const icon = document.createElement('i')
    icon.classList.add('bi', iconClass)
    icon.dataset.bsToggle = 'tooltip'
    icon.dataset.bsTitle = title

    return icon
}

function appendMovieStatusIcons(parent, isWatched, isOnWatchlist, movieUrl = null) {
    const icons = []

    if (isWatched) {
        icons.push(createMovieStatusIcon('bi-eye-fill', 'Watched'))
    }
    if (isOnWatchlist) {
        icons.push(createMovieStatusIcon('bi-bookmark-fill', 'On Watchlist'))
    }

    icons.forEach((icon) => {
        if (movieUrl === null) {
            parent.append(icon)
            return
        }

        const link = document.createElement('a')
        link.href = movieUrl
        link.append(icon)
        parent.append(link)
    })
}

function loadLogModalSearchResults(data) {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');

    if (data.length === 0) {
        document.getElementById('logPlayModalSearchNoResultAlert').classList.remove('d-none')
        return
    }

    searchResultList.style.marginTop = '1rem'

    data.forEach((item, index) => {
        const displayTitle = formatMovieTitle(item.title, item.originalTitle)
        let listElement = document.createElement('li');
        listElement.className = 'list-group-item list-group-item-action'
        listElement.style.cursor = 'pointer'
        listElement.style.display = 'flex'
        listElement.style.alignItems = 'center'
        listElement.style.padding = '.5rem'
        listElement.id = 'searchResult-' + index

        let releaseYear = '?'
        if (item.releaseDate != null && item.releaseDate.length > 4) {
            releaseYear = item.releaseDate.substring(0, 4)
        }

        const posterPath = item.tmdbPosterPath != null
            ? 'https://image.tmdb.org/t/p/w92' + item.tmdbPosterPath
            : APPLICATION_URL + '/images/placeholder/' + encodeBase64(displayTitle)
        const poster = document.createElement('img')
        poster.src = posterPath
        poster.alt = 'Poster for ' + displayTitle
        poster.style.marginRight = '.5rem'
        poster.style.width = '3rem'

        const description = document.createElement('p')
        description.style.margin = '0'

        const title = document.createElement('b')
        title.textContent = displayTitle
        description.append(title, ' (' + releaseYear + ')')
        appendMovieStatusIcons(description, item.isWatched, item.isOnWatchlist)
        description.append(document.createElement('br'))

        const overview = document.createElement('span')
        overview.style.opacity = '0.75'
        overview.style.overflow = 'hidden'
        overview.style.display = '-webkit-box'
        overview.style.webkitLineClamp = '2'
        overview.style.webkitBoxOrient = 'vertical'
        overview.style.maxHeight = '2lh'
        overview.textContent = item.overview || ''
        description.append(overview)
        listElement.append(poster, description)

        listElement.dataset.tmdbId = item.ids.tmdb
        if (item.ids.movary)
            listElement.dataset.movaryId = item.ids.movary
        listElement.dataset.poster = item.tmdbPosterPath
        listElement.dataset.title = displayTitle
        listElement.dataset.releaseYear = releaseYear
        if (item.isOnWatchlist)
            listElement.dataset.isOnWatchlist = 'on'
        if (item.isWatched)
            listElement.dataset.isWatched = 'on'

        listElement.addEventListener('click', selectLogModalTmdbItemForLogging);

        searchResultList.append(listElement);
        listElement.querySelectorAll("i.bi[data-bs-toggle='tooltip']").forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
}

function resetLogModalSearchResults() {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');
    searchResultList.style.marginTop = ''
    searchResultList.replaceChildren()

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
    document.getElementById('logPlayModalTitle').textContent = 'Add movie'

    removeAlert('logPlayModalAlert')
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
    setLogPlayModalReview({review: null, containsSpoilers: false})
    disableLogPlayAdvancedMode()
    document.getElementById('advancedLogPlayModeButton').classList.add('d-none')
    setRatingStars('logPlayModal', 0)
}

async function selectLogModalTmdbItemForLogging(event) {
    const item = event.target.closest(".list-group-item")

    // Icons indicating watched/on watchlist link to the Movary page when it exists.
    let movaryUrl = null
    if (item.dataset.movaryId) {
        movaryUrl = APPLICATION_URL + "/users/" + document.getElementById('currentUserName').value + "/movies/" + item.dataset.movaryId
    }

    const modalTitle = document.getElementById('logPlayModalTitle')
    modalTitle.replaceChildren(item.dataset.title + ' (' + item.dataset.releaseYear + ')')
    appendMovieStatusIcons(
        modalTitle,
        item.dataset.isWatched === 'on',
        item.dataset.isOnWatchlist === 'on',
        movaryUrl,
    )
    document.querySelectorAll("#logPlayModalTitle i.bi").forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl)
    });
    document.getElementById('logPlayModalTmdbIdInput').value = item.dataset.tmdbId
    document.getElementById('logPlayModalFooterTMDBLink').setAttribute(
        "href", "https://www.themoviedb.org/movie/" + item.dataset.tmdbId
    )

    const [rating, review] = await Promise.all([
        fetchRating(item.dataset.tmdbId),
        fetchReview(item.dataset.tmdbId),
    ])
    setRatingStars('logPlayModal', rating)
    setLogPlayModalReview(review)

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalFooterTMDBLink').classList.remove('d-none')
    document.getElementById('logPlayModalFooterBackButton').classList.remove('d-none')
    document.getElementById('logPlayModalFooterWatchlistButton').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')
    document.getElementById('advancedLogPlayModeButton').classList.remove('d-none')
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
    setLogPlayModalReview({review: null, containsSpoilers: false})
    disableLogPlayAdvancedMode()
    removeAlert('logPlayModalAlert')
}

function toggleLogPlayAdvancedMode() {
    if (document.getElementById('advancedLogPlayDetails').classList.contains('d-none')) {
        document.getElementById('advancedLogPlayDetails').classList.remove('d-none')
        updateLogPlayAdvancedModeButton('Simple mode', true)
        return
    }

    disableLogPlayAdvancedMode()
}

function disableLogPlayAdvancedMode() {
    document.getElementById('advancedLogPlayDetails').classList.add('d-none')
    updateLogPlayAdvancedModeButton('Advanced mode', false)
}

function updateLogPlayAdvancedModeButton(label, active) {
    const button = document.getElementById('advancedLogPlayModeButton')
    button.setAttribute('data-bs-title', label)
    button.setAttribute('aria-label', label)
    button.classList.toggle('active', active)
    bootstrap.Tooltip.getOrCreateInstance(button).setContent({'.tooltip-inner': label})
}

function setLogPlayModalReview(reviewData) {
    const review = reviewData.review ?? ''
    document.getElementById('logPlayModalReviewInput').value = review
    document.getElementById('logPlayModalOriginalReview').value = review
    document.getElementById('logPlayModalReviewContainsSpoilers').value = reviewData.containsSpoilers === true ? '1' : '0'
}

function addToWatchlist(context) {
    const tmdbId = document.getElementById(context + 'TmdbIdInput').value
    const postToMastodon = document.getElementById('postToMastodon').checked

    fetchWithCsrf(APPLICATION_URL + '/add-movie-to-watchlist', {
        method: 'post', headers: {
            'Content-type': 'application/json',
        }, body: JSON.stringify({
            'tmdbId': tmdbId,
            'postToMastodon': postToMastodon,
        })
    }).then(function (response) {
        if (response.status === 200) {
            window.location = APPLICATION_URL + "/users/" + document.getElementById('currentUserName').value + "/watchlist"

            return
        }

        addAlert('logPlayModalAlert', 'Could not add to watchlist. Please try again.', 'danger')
    }).catch(function (error) {
        console.log(error)
        addAlert('logPlayModalAlert', 'Could not add to watchlist. Please try again.', 'danger')
    })
}

async function logMovie(context) {
    const rating = getRatingFromStars(context)
    const tmdbId = document.getElementById(context + 'TmdbIdInput').value
    const watchDate = document.getElementById(context + 'WatchDateInput').value
    const comment = document.getElementById(context + 'CommentInput').value
    const locationId = document.getElementById(context + 'LocationInput').value
    const postToMastodon = document.getElementById('postToMastodon').checked
    const dateFormatPhp = document.getElementById('dateFormatPhp').value
    const review = document.getElementById('logPlayModalReviewInput').value
    const originalReview = document.getElementById('logPlayModalOriginalReview').value

    removeAlert('logPlayModalAlert')

    if (validateWatchDate(context, watchDate) === false) {
        return
    }

    if (review !== originalReview && review.trim() === '') {
        addAlert('logPlayModalAlert', 'A movie review must not be empty.', 'danger')
        return
    }

    const requestData = {
        'tmdbId': tmdbId,
        'watchDate': watchDate,
        'comment': comment,
        'dateFormat': dateFormatPhp,
        'personalRating': rating,
        'locationId': locationId,
        'postToMastodon': postToMastodon,
    }
    if (review !== originalReview) {
        requestData.review = review
        requestData.reviewContainsSpoilers = document.getElementById('logPlayModalReviewContainsSpoilers').value === '1'
    }

    let response
    try {
        response = await fetchWithCsrf(APPLICATION_URL + '/log-movie', {
            method: 'post', headers: {
            'Content-type': 'application/json',
            }, body: JSON.stringify(requestData)
        })
    } catch (error) {
        console.log(error)
        addAlert('logPlayModalAlert', 'Could not add play. Please try again.', 'danger')
        return
    }

    if (response.status !== 200) {
        const message = response.status === 400 ? await response.text() : 'Could not add play. Please try again.'
        addAlert('logPlayModalAlert', message, 'danger')
        return
    }

    // do not redirect to /history/ page if play logged from /movies/<id> page
    if (document.getElementById("openedFromMoviePage").getAttribute("value") == "1") {
        window.location.reload()
    }else {
        window.location = APPLICATION_URL + "/users/" + document.getElementById('currentUserName').value + "/history"
    }
}

async function showLogPlayModalWithSpecificMovie(tmdbId, movieTitle, releaseYear) {
    const myModal = new bootstrap.Modal(document.getElementById('logPlayModal'), {
        keyboard: false
    });

    const [rating, review] = await Promise.all([
        fetchRating(tmdbId),
        fetchReview(tmdbId),
    ])
    setRatingStars('logPlayModal', rating)
    setLogPlayModalReview(review)

    document.getElementById('logPlayModalTmdbIdInput').value = tmdbId
    document.getElementById('logPlayModalTitle').textContent = movieTitle + ' (' + releaseYear + ')'

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')
    document.getElementById('logPlayModalFooterTMDBLink').classList.add('d-none')
    document.getElementById('logPlayModalFooterBackButton').classList.add('d-none')
    document.getElementById('logPlayModalFooterWatchlistButton').classList.add('d-none')
    document.getElementById('advancedLogPlayModeButton').classList.remove('d-none')

    myModal.show()

    document.getElementById("openedFromMoviePage").setAttribute("value", "1") // after bs.open event
}

/**
 * Watch date logic starting here
 */
function validateWatchDate(context, watchDate) {
    const dateFormat = document.getElementById('dateFormatJavascript').value

    if (isValidDate(watchDate, dateFormat) === false) {
        document.getElementById(context + 'WatchDateInput').style.borderStyle = 'solid'
        document.getElementById(context + 'WatchDateInput').style.borderColor = '#dc3545'
        document.getElementById(context + 'RatingStars').style.marginTop = '0'

        return false
    }

    document.getElementById(context + 'WatchDateInput').style.borderStyle = ''
    document.getElementById(context + 'WatchDateInput').style.borderColor = ''
    document.getElementById(context + 'RatingStars').style.marginTop = '0.5rem'

    return true
}

function isValidDate(dateString, dateFormat) {
    if (typeof dateString !== 'string') {
        return false
    }

    let match
    let day
    let month
    let year

    if (dateFormat === 'yyyy-mm-dd' || dateFormat === 'yy-mm-dd') {
        const yearLength = dateFormat === 'yyyy-mm-dd' ? 4 : 2
        match = dateString.match(new RegExp('^(\\d{' + yearLength + '})-(\\d{1,2})-(\\d{1,2})$'))
        if (match === null) {
            return false
        }

        year = Number.parseInt(match[1], 10)
        month = Number.parseInt(match[2], 10)
        day = Number.parseInt(match[3], 10)
    } else if (dateFormat === 'dd.mm.yyyy' || dateFormat === 'dd.mm.yy') {
        const yearLength = dateFormat === 'dd.mm.yyyy' ? 4 : 2
        match = dateString.match(new RegExp('^(\\d{1,2})\\.(\\d{1,2})\\.(\\d{' + yearLength + '})$'))
        if (match === null) {
            return false
        }

        day = Number.parseInt(match[1], 10)
        month = Number.parseInt(match[2], 10)
        year = Number.parseInt(match[3], 10)
    } else {
        return false
    }

    if (dateFormat === 'yy-mm-dd' || dateFormat === 'dd.mm.yy') {
        year += year >= 70 ? 1900 : 2000
    }

    if (year < 1000 || year > 3000 || month === 0 || month > 12) {
        return false
    }

    const monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

    if (year % 400 === 0 || (year % 100 !== 0 && year % 4 === 0)) {
        monthLength[1] = 29
    }

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
    const response = await fetch(APPLICATION_URL + '/fetchMovieRatingByTmdbdId?tmdbId=' + tmdbId)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    return data.personalRating
}

async function fetchReview(tmdbId) {
    const response = await fetch(APPLICATION_URL + '/fetchMovieReviewByTmdbId?tmdbId=' + tmdbId)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
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
    const alert = document.createElement('div')
    alert.classList.add('alert', 'alert-' + color, 'alert-dismissible')
    alert.setAttribute('role', 'alert')
    alert.style.marginBottom = marginBottom + 'rem'
    alert.textContent = message

    if (withCloseButton === true) {
        const closeButton = document.createElement('button')
        closeButton.type = 'button'
        closeButton.classList.add('btn-close')
        closeButton.dataset.bsDismiss = 'alert'
        closeButton.setAttribute('aria-label', 'Close')
        alert.append(closeButton)
    }

    document.getElementById(parentDivId).replaceChildren(alert)
}

function removeAlert(parentDivId) {
    document.getElementById(parentDivId).replaceChildren()
}

let applicationDialogActive = false

function showConfirmationModal({title, message, confirmLabel = 'Confirm', confirmClass = 'btn-primary', restoreSourceModalOnConfirm = false} = {}) {
    return showApplicationDialog({
        title: title,
        message: message,
        actionLabel: confirmLabel,
        actionClass: confirmClass,
        showActionButton: true,
        restoreSourceModalOnConfirm: restoreSourceModalOnConfirm,
    })
}

function showMessageModal({title = 'Notice', message} = {}) {
    return showApplicationDialog({
        title: title,
        message: message,
        actionLabel: '',
        actionClass: 'btn-primary',
        showActionButton: false,
        restoreSourceModalOnConfirm: true,
    })
}

function showApplicationDialog({title, message, actionLabel, actionClass, showActionButton, restoreSourceModalOnConfirm}) {
    if (applicationDialogActive === true) {
        return Promise.resolve(false)
    }

    const modalElement = document.getElementById('applicationDialogModal')
    const titleElement = document.getElementById('applicationDialogModalTitle')
    const messageElement = document.getElementById('applicationDialogModalMessage')
    const footerElement = document.getElementById('applicationDialogModalFooter')
    const actionButton = document.getElementById('applicationDialogModalActionButton')

    if (modalElement === null || titleElement === null || messageElement === null || footerElement === null || actionButton === null) {
        return Promise.reject(new Error('Application dialog modal is not available'))
    }

    applicationDialogActive = true

    titleElement.textContent = title ?? ''
    messageElement.textContent = message ?? ''
    footerElement.classList.toggle('d-none', showActionButton === false)
    actionButton.textContent = actionLabel
    actionButton.className = 'btn ' + actionClass
    actionButton.classList.toggle('d-none', showActionButton === false)

    const triggerElement = document.activeElement instanceof HTMLElement ? document.activeElement : null
    const sourceModalElement = document.querySelector('.modal.show:not(#applicationDialogModal)')
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement)
    const sourceModal = sourceModalElement === null ? null : bootstrap.Modal.getOrCreateInstance(sourceModalElement)

    return new Promise((resolve) => {
        let confirmed = false

        const confirm = () => {
            confirmed = true
            modal.hide()
        }

        const finish = () => {
            applicationDialogActive = false
            actionButton.removeEventListener('click', confirm)

            if (triggerElement !== null && document.contains(triggerElement) && (sourceModalElement === null || sourceModalElement.classList.contains('show'))) {
                triggerElement.focus()
            }

            resolve(confirmed)
        }

        const restoreSourceModal = () => {
            if (sourceModalElement === null || sourceModal === null || (confirmed === true && restoreSourceModalOnConfirm === false)) {
                finish()

                return
            }

            sourceModalElement.addEventListener('shown.bs.modal', finish, {once: true})
            sourceModal.show()
        }

        actionButton.addEventListener('click', confirm, {once: true})
        modalElement.addEventListener('hidden.bs.modal', restoreSourceModal, {once: true})

        if (sourceModalElement === null || sourceModal === null) {
            modal.show()

            return
        }

        sourceModalElement.addEventListener('hidden.bs.modal', () => modal.show(), {once: true})
        sourceModal.hide()
    })
}

async function logout() {
    await fetchWithCsrf(APPLICATION_URL + '/logout', {
        method: 'DELETE',
    });

    window.location.href = APPLICATION_URL + '/'
}

async function fetchLocations() {
    const response = await fetch(
        APPLICATION_URL + '/settings/locations',
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

    selectElement.replaceChildren();

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
    updateLocationSelectPlaceholderStyle(selectElement)
}
