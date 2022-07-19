<?php declare(strict_types=1);

return static function(FastRoute\RouteCollector $routeCollector) {
    ### Frontend
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\LandingPageController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/{userId:\d+}/dashboard',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings',
        [\Movary\HttpController\SettingsController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/{userId:\d+}/history',
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
        '/{userId:\d+}/most-watched-actors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/{userId:\d+}/most-watched-directors',
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
        '/fetchMovieRatingByTmdbdId',
        [\Movary\HttpController\MovieController::class, 'fetchMovieRatingByTmdbdId']
    );
    $routeCollector->addRoute(
        'GET',
        '/{userId:\d+}/movie/{id:\d+}',
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
        '/{userId:\d+}/person/{id:\d+}',
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
        '/user/trakt',
        [\Movary\HttpController\SettingsController::class, 'updateTrakt']
    );
    $routeCollector->addRoute(
        'POST',
        '/user/password',
        [\Movary\HttpController\SettingsController::class, 'updatePassword']
    );
    $routeCollector->addRoute(
        'GET',
        '/user/delete-ratings',
        [\Movary\HttpController\SettingsController::class, 'deleteRatings']
    );
    $routeCollector->addRoute(
        'GET',
        '/user/delete-history',
        [\Movary\HttpController\SettingsController::class, 'deleteHistory']
    );
    $routeCollector->addRoute(
        'GET',
        '/user/delete-account',
        [\Movary\HttpController\SettingsController::class, 'deleteAccount']
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-history-sync',
        [\Movary\HttpController\JobController::class, 'scheduleTraktHistorySync']
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-ratings-sync',
        [\Movary\HttpController\JobController::class, 'scheduleTraktRatingsSync']
    );
    $routeCollector->addRoute(
        'POST',
        '/user/date-format',
        [\Movary\HttpController\SettingsController::class, 'updateDateFormatId']
    );
    $routeCollector->addRoute(
        'DELETE',
        '/user/plex-webhook-id',
        [\Movary\HttpController\PlexController::class, 'deletePlexWebhookId']
    );
    $routeCollector->addRoute(
        'POST',
        '/plex/{id:.+}',
        [\Movary\HttpController\PlexController::class, 'handlePlexWebhook']
    );
    $routeCollector->addRoute(
        'GET',
        '/user/export/csv/{exportType:.+}',
        [\Movary\HttpController\ExportController::class, 'getCsvExport']
    );
    $routeCollector->addRoute(
        'POST',
        '/user/import/csv/{exportType:.+}',
        [\Movary\HttpController\ImportController::class, 'handleCsvImport']
    );
};
