const elems = document.querySelectorAll('.datepicker_input')
for (const elem of elems) {
	const datepicker = new Datepicker(elem, {
		// 'format': 'dd.mm.yyyy',
		title: 'Watch date',
	})
}

$('#watchDateModal').on('show.bs.modal', function (e) {
	let movieId = e.relatedTarget.id
	let movieTitle = document.getElementById(movieId + '_movieTitle').value
	let movieYear = document.getElementById(movieId + '_movieReleaseYear').value

	document.getElementById('watchDate').value = ''
	document.getElementById('tmdbId').value = movieId
	document.getElementById('watchDateModalTitle').innerHTML = movieTitle + ' (' + movieYear + ')'
})
