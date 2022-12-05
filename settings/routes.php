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
        '/{username:[a-zA-Z0-9]+}/dashboard',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account',
        [\Movary\HttpController\SettingsController::class, 'renderAccountPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/trakt',
        [\Movary\HttpController\SettingsController::class, 'renderTraktPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/letterboxd',
        [\Movary\HttpController\SettingsController::class, 'renderLetterboxdPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex',
        [\Movary\HttpController\SettingsController::class, 'renderPlexPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/app',
        [\Movary\HttpController\SettingsController::class, 'renderAppPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}/history',
        [\Movary\HttpController\HistoryController::class, 'renderHistory']
    );
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}/movies',
        [\Movary\HttpController\MoviesController::class, 'renderPage']
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
        '/{username:[a-zA-Z0-9]+}/most-watched-actors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}/most-watched-directors',
        [\Movary\HttpController\MostWatchedDirectorsController::class, 'renderPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue',
        [\Movary\HttpController\JobController::class, 'renderQueuePage']
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue/purge-history',
        [\Movary\HttpController\JobController::class, 'purgeHistory']
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
        'POST',
        '/create-user',
        [\Movary\HttpController\CreateUserController::class, 'createUser']
    );
    $routeCollector->addRoute(
        'GET',
        '/fetchMovieRatingByTmdbdId',
        [\Movary\HttpController\MovieController::class, 'fetchMovieRatingByTmdbdId']
    );
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}/movie/{id:\d+}',
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
        '/{username:[a-zA-Z0-9]+}/person/{id:\d+}',
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
        'POST',
        '/user/general',
        [\Movary\HttpController\SettingsController::class, 'updateGeneral']
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
        '/jobs/schedule/letterboxd-history-sync',
        [\Movary\HttpController\JobController::class, 'scheduleLetterboxdHistoryImport']
    );
    $routeCollector->addRoute(
        'POST',
        '/jobs/schedule/letterboxd-ratings-sync',
        [\Movary\HttpController\JobController::class, 'scheduleLetterboxdRatingsImport']
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
