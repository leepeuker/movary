const selectElement = document.querySelector('#changeUserContextSelect')

if (selectElement !== null) {
	selectElement.addEventListener('change', (e) => {
		const currentUrlPath = window.location.pathname

		window.location.href = currentUrlPath.replace(/\/[a-zA-Z0-9]+\//, '/' + selectElement.value + '/')
	})
}
