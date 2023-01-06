const selectElement = document.querySelector('#changeUserContextSelect')

if (selectElement !== null) {
	selectElement.addEventListener('change', (e) => {
		const currentUrlPath = window.location.pathname

		const regex = /(?<!^)\/([a-zA-Z0-9]+)\//;
		const currentRouteUsername = currentUrlPath.match(regex)[1];

		window.location.href = currentUrlPath.replace(currentRouteUsername, selectElement.value)
	})
}
