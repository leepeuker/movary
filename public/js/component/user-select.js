const selectElement = document.querySelector('#changeUserContextSelect')

if (selectElement !== null) {
	selectElement.addEventListener('change', (e) => {
		const currentUrlPath = window.location.pathname

		const regex = /\/users\/([a-zA-Z0-9]+)/;
		const match = currentUrlPath.match(regex);
		
		if (match === null) {
			return;
		}
		
		const currentRouteUsername = match[1];

		window.location.href = currentUrlPath.replace(`/users/${currentRouteUsername}`, `/users/${selectElement.value}`)
	})
}
