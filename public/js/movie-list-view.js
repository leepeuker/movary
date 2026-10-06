const moviePosterView = document.getElementById('moviePosterView');
const movieTableView = document.getElementById('movieTableView');
const movieViewToggleButton = document.getElementById('movieViewToggleButton');
const movieTableViewIcon = document.getElementById('movieTableViewIcon');
const moviePosterViewIcon = document.getElementById('moviePosterViewIcon');
const movieViewStorageKey = movieViewToggleButton.dataset.storageKey;
let currentMovieView;

function setMovieView(view) {
    const tableViewSelected = view === 'table';
    currentMovieView = tableViewSelected ? 'table' : 'posters';

    document.documentElement.dataset.movieListView = currentMovieView;
    movieTableViewIcon.classList.toggle('d-none', tableViewSelected);
    moviePosterViewIcon.classList.toggle('d-none', tableViewSelected === false);

    const toggleButtonLabel = tableViewSelected ? 'Show poster view' : 'Show table view';
    movieViewToggleButton.setAttribute('aria-label', toggleButtonLabel);
    movieViewToggleButton.setAttribute('title', toggleButtonLabel);

    try {
        localStorage.setItem(movieViewStorageKey, currentMovieView);
    } catch {
    }
}

movieViewToggleButton.addEventListener('click', () => {
    setMovieView(currentMovieView === 'table' ? 'posters' : 'table');
});

document.querySelectorAll('#movieTableView button[data-sort-by]').forEach((sortButton) => {
    const table = sortButton.closest('table');
    const tableHeader = sortButton.closest('th');
    const sortBy = sortButton.dataset.sortBy;
    const isCurrentSort = table.dataset.sortBy === sortBy;
    const nextSortOrder = isCurrentSort && table.dataset.sortOrder === 'asc' ? 'desc' : 'asc';
    const label = sortButton.textContent.trim();

    tableHeader.setAttribute('aria-sort', isCurrentSort ? (table.dataset.sortOrder === 'asc' ? 'ascending' : 'descending') : 'none');
    sortButton.setAttribute('aria-label', `Sort by ${label}, ${nextSortOrder === 'asc' ? 'ascending' : 'descending'}`);

    if (isCurrentSort) {
        const directionIndicator = document.createElement('span');
        directionIndicator.classList.add('fs-5');
        directionIndicator.setAttribute('aria-hidden', 'true');
        directionIndicator.textContent = table.dataset.sortOrder === 'asc' ? ' ↑' : ' ↓';
        sortButton.appendChild(directionIndicator);
    }

    sortButton.addEventListener('click', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('sb', sortBy);
        url.searchParams.set('so', nextSortOrder);
        url.searchParams.delete('p');
        window.location.assign(url.toString());
    });
});

setMovieView(document.documentElement.dataset.movieListView);
