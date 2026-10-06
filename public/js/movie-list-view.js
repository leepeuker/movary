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

setMovieView(document.documentElement.dataset.movieListView);
