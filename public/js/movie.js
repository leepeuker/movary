let ratingEditMode = false
let originalRating

const addWatchDateModal = document.getElementById('addWatchDateModal')
addWatchDateModal.addEventListener('show.bs.modal', async function () {
    const datepicker = new Datepicker(document.getElementById('addWatchDateModalInput'), {
        format: document.getElementById('dateFormatJavascript').value,
        title: 'Watch date',
    })

    document.getElementById('addWatchDateModalInput').value = getCurrentDate()
})

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
            alert('Could not delete.')
        }
    })
}

function editRating(e) {
    ratingEditMode = true

    if (originalRating === undefined) {
        originalRating = getRatingFromStars()
    }

    document.getElementById('ratingStarsSpan').classList.add('rating-edit-active')
    document.getElementById('editRatingButton').style.display = 'none'
    document.getElementById('saveRatingButton').style.display = 'inline'
    document.getElementById('resetRatingButton').style.display = 'inline'
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

function updateRatingStars(e) {
    if (ratingEditMode === false) {
        return
    }

    setRatingStars(e.id.substring(20, 10))
}

function getMovieId() {
    return document.getElementById('movieId').value
}

function getRouteUsername() {
    return document.getElementById('username').value
}

function saveRating() {
    let newRating = getRatingFromStars()

    fetch('/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/rating', {
        method: 'post',
        headers: {
            'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: 'rating=' + newRating
    })
        .then(function (data) {
            console.log('Request succeeded with JSON response', data)
        })
        .catch(function (error) {
            alert('Could not update rating.')
            console.log('Request failed', error)
        })

    ratingEditMode = false
    originalRating = newRating

    document.getElementById('ratingStarsSpan').classList.remove('rating-edit-active')
    document.getElementById('editRatingButton').style.display = 'inline'
    document.getElementById('saveRatingButton').style.display = 'none'
    document.getElementById('resetRatingButton').style.display = 'none'
}

function resetRating(e) {
    ratingEditMode = true

    setRatingStars(null)
    saveRating(e)

    ratingEditMode = false

    document.getElementById('ratingStarsSpan').classList.remove('rating-edit-active')
    document.getElementById('editRatingButton').style.display = 'inline'
    document.getElementById('saveRatingButton').style.display = 'none'
    document.getElementById('resetRatingButton').style.display = 'none'
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
                    alert('Could not create new watch date.')
                }
            })
        },
        error: function (xhr, textStatus, errorThrown) {
            alert('Could not delete old watch date.')
        }
    })
}

function addWatchDate() {
    const watchDate = document.getElementById('addWatchDateModalInput').value;
    const comment = document.getElementById('addWatchDateModalCommentInput').value;

    if (validateWatchDate(watchDate) === false) {
        return;
    }

    const apiUrl = '/users/' + getRouteUsername() + '/movies/' + getMovieId() + '/history'

    $.ajax({
        url: apiUrl,
        type: 'PUT',
        data: JSON.stringify({
            'watchDate': watchDate,
            'plays': 1,
            'comment': comment,
            'dateFormat': document.getElementById('dateFormatPhp').value
        }),
        success: function (data, textStatus, xhr) {
            window.location.reload()
        },
        error: function (xhr, textStatus, errorThrown) {
            alert('Could not create new watch date.')
        }
    })
}

function validateWatchDate(watchDate) {
    document.getElementById('addWatchDateModalValidationRequiredErrorMessage').classList.remove('d-block')
    document.getElementById('addWatchDateModalValidationFormatErrorMessage').classList.remove('d-block')

    if (!watchDate) {
        document.getElementById('addWatchDateModalInput').style.borderStyle = 'solid'
        document.getElementById('addWatchDateModalInput').style.borderColor = '#dc3545'
        document.getElementById('addWatchDateModalValidationRequiredErrorMessage').classList.add('d-block')

        return false
    }

    if (isValidDate(watchDate) === false) {
        document.getElementById('addWatchDateModalInput').style.borderStyle = 'solid'
        document.getElementById('addWatchDateModalInput').style.borderColor = '#dc3545'
        document.getElementById('addWatchDateModalValidationFormatErrorMessage').classList.add('d-block')

        return false
    }

    document.getElementById('addWatchDateModalInput').style.borderStyle = ''
    document.getElementById('addWatchDateModalInput').style.borderColor = ''

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

function refreshTmdbData() {
    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;

    refreshTmdbDataRequest().then(() => {
        location.reload()
    }).catch(() => {
        alert('Could not refresh tmdb data. Please try again.')
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
    document.getElementById('refreshTmdbDataButton').disabled = true;
    document.getElementById('refreshImdbRatingButton').disabled = true;

    refreshImdbRatingRequest().then(() => {
        location.reload()
    }).catch(() => {
        alert('Could not refresh imdb rating. Please try again.')
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
