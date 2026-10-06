try {
    if (localStorage.getItem('movieListView') === 'table') {
        document.documentElement.dataset.movieListView = 'table';
    }
} catch {
}
