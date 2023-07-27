function toggleSearchOptions () {
	let searchOptions = document.getElementById('searchOptions')

	if (searchOptions.style.display === 'none') {
		searchOptions.style.display = 'block'
                document.getElementById("toggleSearchOptionsButton").childNodes[1].className = "bi bi-chevron-up"

		return
	}

        document.getElementById("toggleSearchOptionsButton").childNodes[1].className = "bi bi-chevron-down"
	searchOptions.style.display = 'none'
}
