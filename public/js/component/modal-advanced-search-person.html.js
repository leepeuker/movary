document.getElementById('searchTermInput').addEventListener('keypress', function (event) {
    if (event.key === "Enter" || event.keyCode === 13) {
        event.preventDefault();
        document.getElementById('directSearchButton').click();
    }
});

const searchSortBySelect = document.getElementById('searchSortBySelect');
const searchSortOrderSelect = document.getElementById('searchSortOrderSelect');
const searchGenderSelect = document.getElementById('searchGenderSelect');
const searchPerPageSelect = document.getElementById('searchPerPageSelect');

function search() {
    let sortBy = searchSortBySelect.value
    let sortOrder = searchSortOrderSelect.value
    let searchGender = searchGenderSelect.value
    let searchPerPage = searchPerPageSelect.value
    let searchTerm = document.getElementById('searchTermInput').value

    let getParameters = '?'

    getParameters += 'sb=' + sortBy
    getParameters += '&so=' + sortOrder
    getParameters += '&pp=' + searchPerPage

    if (searchGender != '') {
        getParameters += '&ge=' + searchGender
    }

    const urlWithoutGetParameters = window.location.href.split('?')[0];

    window.location.href = urlWithoutGetParameters + getParameters;
}

function resetSearchOptions() {
    searchSortBySelect.value = 'name'
    searchSortOrderSelect.value = 'asc'
    searchGenderSelect.value = ''
    searchPerPageSelect.value = '24'

    enableSaveSortingOptionsButton();
}

function setSortOptionsCookie() {
    document.getElementById("saveSortingOptionsButton").setAttribute("disabled", "");

    var date = new Date();
    var expiration = date.setTime(date.getTime() + 60 * 60 * 24 * 365);
    document.cookie = "person-sort-order=" + searchSortOrderSelect.value + ";expires=" + expiration + ";path=/";
    document.cookie = "person-sort-by=" + searchSortBySelect.value + ";expires=" + expiration + ";path=/";
}

function enableSaveSortingOptionsButton() {
    var saveSortingOptionsButton = document.getElementById("saveSortingOptionsButton");
    if (saveSortingOptionsButton.hasAttribute("disabled")) {
        saveSortingOptionsButton.removeAttribute("disabled");
    }
}
