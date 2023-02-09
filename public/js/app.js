if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/serviceWorker.js')
            .then(res => console.log('service worker registered'))
            .catch(err => console.log('service worker not registered', err))
    })
}

async function searchTmdbWithLogModalSearchInput() {
    resetLogModalSearchResults()
    document.getElementById('logPlayModalSearchSpinner').classList.remove('d-none')

    const data = await fetch('/settings/netflix/search', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'query': document.getElementById('logPlayModalSearchInput').value
        })
    }).then(response => {
        if (!response.ok) {
            console.error(response);
            document.getElementById('logPlayModalSearchSpinner').classList.add('d-none')
            displayLogModalTmdbSearchError()

            return null;
        }

        return response.json()
    }).catch(function (error) {
        console.error(error);

        document.getElementById('logPlayModalSearchSpinner').classList.add('d-none')
        displayLogModalTmdbSearchError()
    });

    if (data !== null) {
        document.getElementById('logPlayModalSearchSpinner').classList.add('d-none')
        loadLogModalSearchResults(data)
    }
}

function displayLogModalTmdbSearchError() {
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
            console.log(item.release_date)
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
    document.getElementById('logPlayModalSearchDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.remove('d-none')
    document.getElementById('logPlayModalFooter').classList.add('d-none')
    document.getElementById('logPlayModalTitle').innerHTML = 'Log play'

    document.getElementById('logPlayModalLogErrorAlert').classList.add('d-none')
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
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')
}

document.getElementById('logPlayModalSearchInput').addEventListener('change', updateLogPlayModalButtonState);
document.getElementById('logPlayModalSearchInput').addEventListener('input', updateLogPlayModalButtonState);

function updateLogPlayModalButtonState() {
    if (document.getElementById('logPlayModalSearchInput').value !== '') {
        document.getElementById('logPlayModalSearchButton').disabled = false;

        return
    }

    document.getElementById('logPlayModalSearchButton').disabled = true;
}

document.getElementById('logPlayModalSearchInput').addEventListener('keypress', loadLogModalSearchResultsOnEnterPress);
function loadLogModalSearchResultsOnEnterPress(event) {
    // 13=enter, works better to check the key code because the key is named differently on mobile
    if (event.keyCode === 13) {
        searchTmdbWithLogModalSearchInput()
    }
}

document.getElementById('logPlayModal').addEventListener('show.bs.modal', function () {
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
})

document.getElementById('logPlayModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('logPlayModalSearchInput').value = ''
    resetLogModalLogInputs()
    resetLogModalSearchResults()
    backToLogModalSearchResults()
})

function resetLogModalLogInputs() {
    document.getElementById('logPlayModalTmdbIdInput').value = ''
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
    document.getElementById('logPlayModalLogErrorAlert').classList.add('d-none')
}

function logMovie(context) {
    const rating = getRatingFromStars(context)
    const tmdbId = document.getElementById(context + 'TmdbIdInput').value
    const watchDate = document.getElementById(context + 'WatchDateInput').value
    const comment = document.getElementById(context + 'CommentInput').value
    const dateFormatPhp = document.getElementById('dateFormatPhp').value
    document.getElementById('logPlayModalLogErrorAlert').classList.add('d-none')

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
        })
    }).then(function (response) {
        if (response.status === 200) {
            location.reload();

            return
        }

        document.getElementById('logPlayModalLogErrorAlert').classList.remove('d-none')
    }).catch(function (error) {
        console.log(error)
        document.getElementById('logPlayModalLogErrorAlert').classList.remove('d-none')
    })
}

async function showLogPlayModalWithSpecificMovie(tmdbId, movieTitle) {
    const myModal = new bootstrap.Modal(document.getElementById('logPlayModal'), {
        keyboard: false
    });

    const rating = await fetchRating(tmdbId)
    setRatingStars('logPlayModal', rating)

    document.getElementById('logPlayModalTmdbIdInput').value = tmdbId
    document.getElementById('logPlayModalTitle').innerHTML = movieTitle

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')

    myModal.show()
}

/**
 * Watch date logic starting here
 */
function validateWatchDate(context, watchDate) {
    if (!watchDate) {
        document.getElementById(context + 'WatchDateInput').style.borderStyle = 'solid'
        document.getElementById(context + 'WatchDateInput').style.borderColor = '#dc3545'
        document.getElementById(context + 'RatingStars').style.marginTop = '0'

        return false
    }

    if (isValidDate(watchDate) === false) {
        document.getElementById(context + 'WatchDateInput').style.borderStyle = 'solid'
        document.getElementById(context + 'WatchDateInput').style.borderColor = '#dc3545'
        document.getElementById(context + 'ratingStars').style.marginTop = '0'

        return false
    }

    console.log(context + 'watchDateInput')
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

new Datepicker(document.getElementById('logPlayModalWatchDateInput'), {
    format: document.getElementById('dateFormatJavascript').value,
    title: 'Watch date',
})
