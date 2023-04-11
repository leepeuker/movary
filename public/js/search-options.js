function toggleSearchOptions () {
	let searchOptions = document.getElementById('searchOptions')

	if (searchOptions.style.display === 'none') {
		searchOptions.style.display = 'block'

		return
	}

	searchOptions.style.display = 'none'
}
