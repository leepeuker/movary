<?php declare(strict_types=1);

return static function(FastRoute\RouteCollector $routeCollector) {
    ### Frontend
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings',
        [\Movary\HttpController\SettingsController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/history',
        [\Movary\HttpController\HistoryController::class, 'renderHistory']
    );
    $routeCollector->addRoute(
        'POST',
        '/letterboxd-rating',
        [\Movary\HttpController\Letterboxd::class, 'uploadRatingCsv']
    );
    $routeCollector->addRoute(
        'POST',
        '/refresh-trakt',
        [\Movary\HttpController\SyncTraktController::class, 'execute']
    );
    $routeCollector->addRoute(
        'POST',
        '/refresh-tmdb',
        [\Movary\HttpController\SyncTmdbController::class, 'execute']
    );
    $routeCollector->addRoute(
        'POST',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'login']
    );
    $routeCollector->addRoute(
        'GET',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'renderLoginPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/logout',
        [\Movary\HttpController\AuthenticationController::class, 'logout']
    );
    $routeCollector->addRoute(
        'GET',
        '/most-watched-actors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/most-watched-directors',
        [\Movary\HttpController\MostWatchedDirectorsController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/log-movie',
        [\Movary\HttpController\HistoryController::class, 'renderLogMoviePage']
    );
    $routeCollector->addRoute(
        'POST',
        '/log-movie',
        [\Movary\HttpController\HistoryController::class, 'logMovie']
    );
    $routeCollector->addRoute(
        'GET',
        '/movie/{id:\d+}',
        [\Movary\HttpController\MovieController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'DELETE',
        '/movie/{id:\d+}/history',
        [\Movary\HttpController\HistoryController::class, 'deleteHistoryEntry']
    );
    $routeCollector->addRoute(
        'POST',
        '/movie/{id:\d+}/rating',
        [\Movary\HttpController\MovieController::class, 'updateRating']
    );
    $routeCollector->addRoute(
        'GET',
        '/person/{id:\d+}',
        [\Movary\HttpController\PersonController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/user/plex-webhook-id',
        [\Movary\HttpController\PlexController::class, 'getPlexWebhookId']
    );
    $routeCollector->addRoute(
        'PUT',
        '/user/plex-webhook-id',
        [\Movary\HttpController\PlexController::class, 'regeneratePlexWebhookId']
    );

    $routeCollector->addRoute(
        'POST',
        '/plex/{id:.+}',
        [\Movary\HttpController\PlexController::class, 'handlePlexWebhook']
    );
};
