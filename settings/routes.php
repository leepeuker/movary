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
        '/mostWatched',
        [\Movary\HttpController\MostWatchedController::class, 'fetchMostWatched']
    );
    $routeCollector->addRoute(
        'POST',
        '/letterboxd-rating',
        [\Movary\HttpController\Letterboxd::class, 'uploadRatingCsv']
    );
};
