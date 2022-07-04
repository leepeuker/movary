const elems = document.querySelectorAll('.datepicker_input')
for (const elem of elems) {
	const datepicker = new Datepicker(elem, {
		format: 'dd.mm.yyyy',
		title: 'Watch date',
	})
}

const watchModal = document.getElementById('watchDateModal')
watchModal.addEventListener('show.bs.modal', async function (e) {
	let movieId = e.relatedTarget.id
	let movieTitle = document.getElementById(movieId + '_movieTitle').value
	let movieYear = document.getElementById(movieId + '_movieReleaseYear').value

	document.getElementById('watchDate').value = ''
	document.getElementById('tmdbId').value = movieId
	document.getElementById('watchDateModalTitle').innerHTML = movieTitle + ' (' + movieYear + ')'
	let ratingNumber = await fetchRating(movieId)
	setRatingStars(ratingNumber)
})

watchModal.addEventListener('hidden.bs.modal', function (e) {
	document.getElementById('watchDate').value = ''
	document.getElementById('tmdbId').value = ''
	document.getElementById('watchDateModalTitle').innerHTML = ''

	document.getElementById('watchDate').style.borderStyle = ''
	document.getElementById('watchDate').style.borderColor = ''
	document.getElementById('ratingStars').style.marginTop = '0.5rem'

	document.getElementById('watchDateValidationRequiredErrorMessage').classList.remove('d-block')
	document.getElementById('watchDateValidationFormatErrorMessage').classList.remove('d-block')

	setRatingStars(0)
})

async function fetchRating (tmdbId) {
	const response = await fetch('/fetchMovieRatingByTmdbdId?tmdbId=' + tmdbId)

	if (!response.ok) {
		throw new Error(`HTTP error! status: ${response.status}`)
	}

	const data = await response.json()

	return data.personalRating
}

function setRatingStars (newRating) {
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

function getRatingFromStars () {
	let rating = 0

	for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
		if (document.getElementById('ratingStar' + ratingStarNumber).classList.contains('bi-star') === true) {
			break
		}

		rating = ratingStarNumber
	}

	return rating
}

function updateRatingStars (e) {
	setRatingStars(e.id.substring(20, 10))
}

function getTmdbId () {
	return document.getElementById('tmdbId').value
}

function getWatchDate () {
	return document.getElementById('watchDate').value
}

const alertPlaceholder = document.getElementById('alerts')
const addAlertMessage = (message, type) => {
	const wrapper = document.createElement('div')
	wrapper.innerHTML = [`<div class="alert alert-${type} alert-dismissible" role="alert">`, `   <div>${message}</div>`, '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>', '</div>'].join('')

	alertPlaceholder.append(wrapper)
}

function logMovie () {
	let rating = getRatingFromStars()
	let tmdbId = getTmdbId()
	let watchDate = getWatchDate()
	let movieTitle = document.getElementById('watchDateModalTitle').innerHTML

	document.getElementById('watchDateValidationRequiredErrorMessage').classList.remove('d-block')
	document.getElementById('watchDateValidationFormatErrorMessage').classList.remove('d-block')

	if (!watchDate) {
		document.getElementById('watchDate').style.borderStyle = 'solid'
		document.getElementById('watchDate').style.borderColor = '#dc3545'
		document.getElementById('ratingStars').style.marginTop = '0'
		document.getElementById('watchDateValidationRequiredErrorMessage').classList.add('d-block')
		return
	}
	if (isValidDate(watchDate) === false) {
		document.getElementById('watchDate').style.borderStyle = 'solid'
		document.getElementById('watchDate').style.borderColor = '#dc3545'
		document.getElementById('ratingStars').style.marginTop = '0'
		document.getElementById('watchDateValidationFormatErrorMessage').classList.add('d-block')
		return
	}

	document.getElementById('watchDate').style.borderStyle = ''
	document.getElementById('watchDate').style.borderColor = ''
	document.getElementById('ratingStars').style.marginTop = '0.5rem'

	fetch('/log-movie', {
		method: 'post', headers: {
			'Content-type': 'application/json',
		}, body: JSON.stringify({
			'tmdbId': tmdbId, 'watchDate': watchDate, 'personalRating': rating,
		})
	})
		.then(function (response) {
			if (response.status === 200) {
				addAlertMessage('Added: ' + movieTitle + ' at ' + watchDate, 'success')
			} else {
				console.log(response)
				addAlertMessage('Could not add: ' + movieTitle + ' at ' + watchDate, 'danger')
			}

			bootstrap.Modal.getInstance(watchModal).hide()
		})
		.catch(function (error) {
			console.log(error)
			addAlertMessage('Could not add: ' + movieTitle + ' at ' + watchDate, 'danger')

			bootstrap.Modal.getInstance(watchModal).hide()
		})
}

function isValidDate (dateString) {
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
