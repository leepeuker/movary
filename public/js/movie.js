let ratingEditMode = false
let originalRating

function deleteWatchDate() {
    const confirmed = confirm('Are you sure?')

    if (confirmed === false) {
        return
    }

    const apiUrl = '/movie/' + getMovieId() + '/history'

    $.ajax({
        url: apiUrl,
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

function saveRating() {
    let newRating = getRatingFromStars()
    let movieId = getMovieId()

    fetch('/movie/' + movieId + '/rating', {
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
        toggleWatchDatesButtonDiv.style.marginBottom = '0.7rem';
        watchDatesListDiv.style.marginBottom = '0';
    } else {
        toggleWatchDatesButton.classList.add('active')
        watchDatesListDiv.style.display = 'block';
        toggleWatchDatesButtonDiv.style.marginBottom = '0';
        watchDatesListDiv.style.marginBottom = '0.7rem';
    }
}

function loadWatchDateModal(e) {
    const modal = new bootstrap.Modal('#watchDateModal', {
        keyboard: false
    })

    const watchDateIndex = getWatchDateIndexNumber(e);

    const originalDate = document.getElementById('inputDate-' + watchDateIndex).value;
    const originalPlays = document.getElementById('inputPlays-' + watchDateIndex).value;

    document.getElementById('modalWatchDate').value = originalDate;
    document.getElementById('modalWatchDatePlays').value = originalPlays;
    document.getElementById('originalWatchDate').value = originalDate;
    document.getElementById('originalWatchDatePlays').value = originalPlays;

    const datepicker = new Datepicker(document.getElementById('modalWatchDate'), {
        format: document.getElementById('dateFormatJavascript').value,
        title: 'Watch date',
    })

    modal.show()
}

function getWatchDateIndexNumber(e) {
    const paragraph = e.id;
    const regex = /watchDate-(\d+)/;

    return paragraph.match(regex)[1];
}

function editWatchDate() {
    const originalWatchDate = document.getElementById('originalWatchDate').value;

    const newWatchDate = document.getElementById('modalWatchDate').value;
    const newWatchDatePlays = document.getElementById('modalWatchDatePlays').value;

    const apiUrl = '/movie/' + getMovieId() + '/history'

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
                    'dateFormat': document.getElementById('dateFormatPhp').value
                }),
                success: function (data, textStatus, xhr) {
                    window.location.reload()
                },
                error: function (xhr, textStatus, errorThrown) {
                    alert('Could not update.')
                }
            })
        },
        error: function (xhr, textStatus, errorThrown) {
            alert('Could not delete original watch date.')
        }
    })
}
