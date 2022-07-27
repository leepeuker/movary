if ('serviceWorker' in navigator) {
	window.addEventListener('load', function () {
		navigator.serviceWorker
			.register('/serviceWorker.js')
			.then(res => console.log('service worker registered'))
			.catch(err => console.log('service worker not registered', err))
	})
}

function changeUserContext (e) {
	const currentUrlPath = window.location.pathname

	window.location.href = currentUrlPath.replace(/\/[a-zA-Z0-9]+\//, '/' + e.value + '/')
}
