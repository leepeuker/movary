document.getElementById('searchTermInput').addEventListener('keypress', function(event) {
    if (event.key === "Enter" || event.keyCode === 13) {
        event.preventDefault();
        document.getElementById('directSearchButton').click();
    }
});

function search() {
    let sortBy = document.getElementById('searchSortBySelect').value
    let sortOrder = document.getElementById('searchSortOrderSelect').value
    let searchGenre = document.getElementById('searchGenreSelect').value
    let searchLanguage = document.getElementById('searchLanguageSelect').value
    let searchReleaseYear = document.getElementById('searchReleaseYearSelect').value
    let searchPerPage = document.getElementById('searchPerPageSelect').value
    let searchTerm = document.getElementById('searchTermInput').value

    let getParameters = '?'

    getParameters += 'sb=' + sortBy
    getParameters += '&so=' + sortOrder
    getParameters += '&pp=' + searchPerPage

    if (searchGenre != '') {
        getParameters += '&ge=' + searchGenre
    }
    if (searchLanguage != '') {
        getParameters += '&la=' + searchLanguage
    }
    if (searchReleaseYear != '') {
        getParameters += '&ry=' + searchReleaseYear
    }
    if (searchTerm != '') {
        getParameters += '&s=' + searchTerm
    }

    const urlWithoutGetParameters = window.location.href.split('?')[0];

    window.location.href = urlWithoutGetParameters + getParameters;
}

function resetSearchOptions() {

}