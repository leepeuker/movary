<?php declare(strict_types=1);

return static function(FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/history',
        [\Movary\HttpController\MovieHistoryController::class, 'fetchHistory']
    );
    $routeCollector->addRoute(
        'GET',
        '/mostWatchedMovies',
        [\Movary\HttpController\MostWatchedMoviesController::class, 'fetchMostWatchedMovies']
    );
    $routeCollector->addRoute(
        'GET',
        '/mostWatchedActors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'fetchMostWatchedActors']
    );
    $routeCollector->addRoute(
        'GET',
        '/mostWatchedProductionCompanies',
        [\Movary\HttpController\MostWatchedProductionCompaniesController::class, 'fetchMostWatchedProductionCompanies']
    );
    $routeCollector->addRoute(
        'POST',
        '/letterboxd-rating',
        [\Movary\HttpController\Letterboxd::class, 'uploadRatingCsv']
    );
};
