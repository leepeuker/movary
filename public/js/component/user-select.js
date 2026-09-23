const selectElement = document.querySelector('#changeUserContextSelect')

if (selectElement !== null) {
	selectElement.addEventListener('change', (e) => {
		const currentUrlPath = window.location.pathname
		const currentQueryString = window.location.search

		const regex = /\/users\/([a-zA-Z0-9]+)/;
		const match = currentUrlPath.match(regex);
		
		if (match === null) {
			return;
		}
		
		const currentRouteUsername = match[1];
		const newPath = currentUrlPath.replace(`/users/${currentRouteUsername}`, `/users/${selectElement.value}`)

		window.location.href = newPath + currentQueryString
	})
}
