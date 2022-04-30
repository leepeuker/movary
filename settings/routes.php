<?php declare(strict_types=1);

return static function(FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'POST',
        '/letterboxd-rating',
        [\Movary\HttpController\Letterboxd::class, 'uploadRatingCsv']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/ahistory',
        [\Movary\HttpController\MovieHistoryController::class, 'fetchHistory']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedMovies',
        [\Movary\HttpController\MostWatchedMoviesController::class, 'fetchMostWatchedMovies']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedActors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'fetchMostWatchedActors']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedGenres',
        [\Movary\HttpController\MostWatchedGenresController::class, 'fetchMostWatchedGenres']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedProductionCompanies',
        [\Movary\HttpController\MostWatchedProductionCompaniesController::class, 'fetchMostWatchedProductionCompanies']
    );
};
