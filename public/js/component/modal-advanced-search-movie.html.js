document.getElementById('searchTermInput').addEventListener('keypress', function (event) {
    if (event.key === "Enter" || event.keyCode === 13) {
        event.preventDefault();
        document.getElementById('directSearchButton').click();
    }
});

const searchSortBySelect = document.getElementById('searchSortBySelect');
const searchSortOrderSelect = document.getElementById('searchSortOrderSelect');
const searchGenreSelect = document.getElementById('searchGenreSelect');
const searchLanguageSelect = document.getElementById('searchLanguageSelect');
const searchReleaseYearSelect = document.getElementById('searchReleaseYearSelect');
const searchPerPageSelect = document.getElementById('searchPerPageSelect');
const searchUserRatingSelect = document.getElementById('searchUserRatingSelect');
const searchMinUserRatingSelect = document.getElementById('searchMinUserRatingSelect');
const searchMaxUserRatingSelect = document.getElementById('searchMaxUserRatingSelect');
const searchLocationSelect = document.getElementById('searchLocationSelect');

function search() {
    let sortBy = searchSortBySelect.value
    let sortOrder = searchSortOrderSelect.value
    let searchGenre = searchGenreSelect.value
    let searchLanguage = searchLanguageSelect.value
    let searchReleaseYear = searchReleaseYearSelect.value
    let searchPerPage = searchPerPageSelect.value
    let searchUserRating = searchUserRatingSelect.value
    let searchMinUserRating = searchMinUserRatingSelect.value
    let searchMaxUserRating = searchMaxUserRatingSelect.value
    let searchTerm = document.getElementById('searchTermInput').value
    let searchLocation = searchLocationSelect.value

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
    if (searchLocation != '') {
        getParameters += '&loc=' + searchLocation
    }
    if (searchUserRating != '') {
        getParameters += '&ur=' + searchUserRating
        if (searchUserRating === '1') {
            getParameters += '&minur=' + searchMinUserRating
            getParameters += '&maxur=' + searchMaxUserRating
        }
    }

    const urlWithoutGetParameters = window.location.href.split('?')[0];

    window.location.href = urlWithoutGetParameters + getParameters;
}

function resetSearchOptions() {
    searchSortBySelect.value = 'title'
    searchSortOrderSelect.value = 'asc'
    searchGenreSelect.value = ''
    searchLanguageSelect.value = ''
    searchReleaseYearSelect.value = ''
    searchUserRatingSelect.value = ''
    searchMinUserRatingSelect.value = ''
    searchMaxUserRatingSelect.value = ''
    searchPerPageSelect.value = '24'
    searchLocationSelect.value = ''
}


function toggleMinAndMaxUserRatingDivVisibility(displayUserRating) {
    if (displayUserRating === false) {
        document.getElementById('searchMinUserRatingDiv').classList.add('d-none')
        document.getElementById('searchMaxUserRatingDiv').classList.add('d-none')

        return
    }

    document.getElementById('searchMinUserRatingDiv').classList.remove('d-none')
    document.getElementById('searchMaxUserRatingDiv').classList.remove('d-none')
}

document.getElementById('searchUserRatingSelect').addEventListener('change', (e) => {
    toggleMinAndMaxUserRatingDivVisibility(e.target.value === '1')
})

document.getElementById('searchOptionsMovieModal').addEventListener('show.bs.modal', function () {
    toggleMinAndMaxUserRatingDivVisibility(document.getElementById('searchUserRatingSelect').value === '1')
})

document.getElementById('searchMinUserRatingDiv').addEventListener('change', (e) => {
    adjustMaxRatingToMatchMinRating(Number(e.target.value))
})

document.getElementById('searchMaxUserRatingDiv').addEventListener('change', (e) => {
    adjustMinRatingToMatchMaxRating(Number(e.target.value))
})

function adjustMaxRatingToMatchMinRating(minRating) {
    const maxUserRatingSelect = document.getElementById('searchMaxUserRatingSelect');

    for (let i = 0; i < maxUserRatingSelect.options.length; i++) {
        let maxOption = maxUserRatingSelect.options[i];

        maxOption.disabled = Number(maxOption.value) < minRating
    }

    if (Number(maxUserRatingSelect.value) < minRating) {
        maxUserRatingSelect.value = minRating
    }
}

function adjustMinRatingToMatchMaxRating(maxRating) {
    const minUserRatingSelect = document.getElementById('searchMinUserRatingSelect');

    for (let i = 0; i < minUserRatingSelect.options.length; i++) {
        let minOption = minUserRatingSelect.options[i];

        minOption.disabled = Number(minOption.value) > maxRating
    }

    if (Number(minUserRatingSelect.value) > maxRating) {
        minUserRatingSelect.value = maxRating
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('searchMinUserRatingSelect').classList.contains('d-none')) {
        return
    }

    adjustMaxRatingToMatchMinRating(Number(document.getElementById('searchMinUserRatingSelect').value))
    adjustMinRatingToMatchMaxRating(Number(document.getElementById('searchMaxUserRatingSelect').value))
})
