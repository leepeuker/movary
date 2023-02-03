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

    await fetch('/settings/netflix/search', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'query': document.getElementById('logPlayModalSearchInput').value
        })
    }).then(response => {
        if (!response.ok) {
            console.error(response);
            alert('error')

            return false;
        }

        return response.json()
    }).then(data => {
        loadLogModalSearchResults(data)
    }).catch(function (error) {
        console.error(error);
        alert('error')
    });
}

function loadLogModalSearchResults(data) {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');

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

        listElement.addEventListener('click', selectTmdbItemForLogging);

        searchResultList.append(listElement);
    });
    console.log(data)
}

function resetLogModalSearchResults() {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');
    searchResultList.style.marginTop = ''
    searchResultList.innerHTML = ''
}

function backToLogModalSearchResults() {
    document.getElementById('logPlayModalWatchDateDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.remove('d-none')
    document.getElementById('logPlayModalFooter').classList.add('d-none')
    document.getElementById('logPlayModalTitle').innerHTML = 'Log play'

    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
    setRatingStars(0)
}

function selectTmdbItemForLogging(event) {
    const item = event.target.closest(".list-group-item")

    document.getElementById('logPlayModalTitle').innerHTML = item.dataset.title + ' (' + item.dataset.releaseYear + ')'
    document.getElementById('logPlayModalTmdbIdInput').value = item.dataset.tmdbId

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')

    console.log(document.getElementById('logPlayModalFooter').classList)
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
    if (event.key === "Enter") {
        searchTmdbWithLogModalSearchInput()
    }
}

document.getElementById('logPlayModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('logPlayModalSearchInput').value = ''
    resetLogModalSearchResults()
    backToLogModalSearchResults()
})
document.getElementById('logPlayModal').addEventListener('show.bs.modal', function () {
    resetLogModalLogInputs()
})

function resetLogModalLogInputs() {
    document.getElementById('logPlayModalTmdbIdInput').value = ''
    document.getElementById('logPlayModalWatchDateInput').value = getCurrentDate()
    document.getElementById('logPlayModalCommentInput').value = ''
}

function logMovie() {
    let rating = getRatingFromStars()
    let tmdbId = document.getElementById('logPlayModalTmdbIdInput').value
    let watchDate = document.getElementById('logPlayModalWatchDateInput').value
    let comment = document.getElementById('logPlayModalCommentInput').value
    let dateFormatPhp = document.getElementById('dateFormatPhp').value

    if (validateWatchDate(watchDate) === false) {
        return
    }

    fetch('/log-movie', {
        method: 'post',
        headers: {
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
            alert('Added watch date')
        } else {
            console.log(response)
            alert('Could not log movie: ')
        }
    }).catch(function (error) {
        console.log(error)
        alert('Could not log movie: ')
    })
}

/**
 * Log movie modal: Watch date logic starting here
 */
new Datepicker(document.getElementById('logPlayModalWatchDateInput'), {
    format: document.getElementById('dateFormatJavascript').value,
    title: 'Watch date',
})

function validateWatchDate(watchDate) {
    if (!watchDate) {
        document.getElementById('logPlayModalWatchDateInput').style.borderStyle = 'solid'
        document.getElementById('logPlayModalWatchDateInput').style.borderColor = '#dc3545'
        document.getElementById('logPlayModalRatingStars').style.marginTop = '0'

        return false
    }

    if (isValidDate(watchDate) === false) {
        document.getElementById('logPlayModalWatchDateInput').style.borderStyle = 'solid'
        document.getElementById('logPlayModalWatchDateInput').style.borderColor = '#dc3545'
        document.getElementById('logPlayModalRatingStars').style.marginTop = '0'

        return false
    }

    document.getElementById('logPlayModalWatchDateInput').style.borderStyle = ''
    document.getElementById('logPlayModalWatchDateInput').style.borderColor = ''
    document.getElementById('logPlayModalRatingStars').style.marginTop = '0.5rem'

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
 * Log movie modal: Rating star logic starting here
 */
async function fetchRating(tmdbId) {
    const response = await fetch('/fetchMovieRatingByTmdbdId?tmdbId=' + tmdbId)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    return data.personalRating
}

function setRatingStars(newRating) {
    if (getRatingFromStars() == newRating) {
        newRating = null
    }

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        document.getElementById('ratingStar' + ratingStarNumber).classList.remove('bi-star-fill')
        document.getElementById('ratingStar' + ratingStarNumber).classList.remove('bi-star')

        if (ratingStarNumber <= newRating) {
            document.getElementById('ratingStar' + ratingStarNumber).classList.add('bi-star-fill')
        } else {
            document.getElementById('ratingStar' + ratingStarNumber).classList.add('bi-star')
        }
    }
}

function getRatingFromStars() {
    let rating = 0

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        if (document.getElementById('ratingStar' + ratingStarNumber).classList.contains('bi-star') === true) {
            break
        }

        rating = ratingStarNumber
    }

    return rating
}

function updateRatingStars(e) {
    setRatingStars(e.id.substring(20, 10))
}
