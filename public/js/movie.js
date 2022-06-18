function myFunction (e) {
	const confirmed = confirm('Are you sure?')

	if (confirmed === false) {
		return
	}

	const watchDate = document.getElementById(e.id + '-watch-date')

	const apiUrl = '/movie/' + watchDate.getAttribute('movie-id') + '/history'

	$.ajax({
		url: apiUrl,
		type: 'DELETE',
		data: JSON.stringify({
			'date': watchDate.getAttribute('date')
		}),
		success: function (data, textStatus, xhr) {
			window.location.reload()
		},
		error: function (xhr, textStatus, errorThrown) {
			alert('Could not delete.')
		}
	})
}
