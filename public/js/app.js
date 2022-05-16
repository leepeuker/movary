if ('serviceWorker' in navigator) {
	window.addEventListener('load', function () {
		navigator.serviceWorker
			.register('/serviceWorker.js')
			.then(res => console.log('service worker registered'))
			.catch(err => console.log('service worker not registered', err))
	})
}

function showDirectorModal (id) {
	resetPersonModal()

	$.ajax({
			type: 'GET',
			url: '/api/person/' + id + '/director',
			success: function (watchedMovies) {

				let ul = document.getElementById('personModalMovies')

				watchedMovies.forEach(function (item) {
					let li = document.createElement('li')
					li.appendChild(document.createTextNode(item.title + ' (' + item.year + ')'))
					ul.appendChild(li)
				})
			},
			error: function (data) {
				console.log(data)
			}
		}
	)

	document.getElementById('personModalName').innerHTML = document.getElementById('directorName_' + id).value
	$('#personModal').modal('toggle')
}

function showActorModal (id) {
	resetPersonModal()

	$.ajax({
			type: 'GET',
			url: '/api/person/' + id + '/actor',
			success: function (watchedMovies) {

				let ul = document.getElementById('personModalMovies')

				watchedMovies.forEach(function (item) {
					let li = document.createElement('li')
					li.appendChild(document.createTextNode(item.title + ' (' + item.year + ')'))
					ul.appendChild(li)
				})
			},
			error: function (data) {
				console.log(data)
			}
		}
	)

	document.getElementById('personModalName').innerHTML = document.getElementById('actorName_' + id).value
	$('#personModal').modal('toggle')
}

function resetPersonModal () {
	document.getElementById('personModalName').innerHTML = ''
	document.getElementById('personModalMovies').innerHTML = ''
}
