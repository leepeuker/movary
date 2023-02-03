<?php declare(strict_types=1);

return static function (FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\LandingPageController::class, 'render'],
    );
    $routeCollector->addRoute(
        'POST',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'login'],
    );
    $routeCollector->addRoute(
        'GET',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'renderLoginPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/logout',
        [\Movary\HttpController\AuthenticationController::class, 'logout'],
    );
    $routeCollector->addRoute(
        'POST',
        '/create-user',
        [\Movary\HttpController\CreateUserController::class, 'createUser'],
    );

    #####################
    # Webhook listeners #
    #####################
    $routeCollector->addRoute(
        'POST',
        '/plex/{id:.+}',
        [\Movary\HttpController\PlexController::class, 'handlePlexWebhook'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jellyfin/{id:.+}',
        [\Movary\HttpController\JellyfinController::class, 'handleJellyfinWebhook'],
    );

    #############
    # Job Queue #
    #############
    $routeCollector->addRoute(
        'GET',
        '/job-queue',
        [\Movary\HttpController\JobController::class, 'renderQueuePage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue/purge-processed',
        [\Movary\HttpController\JobController::class, 'purgeProcessedJobs'],
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue/purge-all',
        [\Movary\HttpController\JobController::class, 'purgeAllJobs'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-history-sync',
        [\Movary\HttpController\JobController::class, 'scheduleTraktHistorySync'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-ratings-sync',
        [\Movary\HttpController\JobController::class, 'scheduleTraktRatingsSync'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jobs/schedule/letterboxd-diary-sync',
        [\Movary\HttpController\JobController::class, 'scheduleLetterboxdDiaryImport'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jobs/schedule/letterboxd-ratings-sync',
        [\Movary\HttpController\JobController::class, 'scheduleLetterboxdRatingsImport'],
    );

    ############
    # Settings #
    ############
    $routeCollector->addRoute(
        'GET',
        '/settings/account',
        [\Movary\HttpController\SettingsController::class, 'renderAccountPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account',
        [\Movary\HttpController\SettingsController::class, 'updateGeneral'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/password',
        [\Movary\HttpController\SettingsController::class, 'updatePassword'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/export/csv/{exportType:.+}',
        [\Movary\HttpController\ExportController::class, 'getCsvExport'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/import/csv/{exportType:.+}',
        [\Movary\HttpController\ImportController::class, 'handleCsvImport'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-ratings',
        [\Movary\HttpController\SettingsController::class, 'deleteRatings'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-history',
        [\Movary\HttpController\SettingsController::class, 'deleteHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-account',
        [\Movary\HttpController\SettingsController::class, 'deleteAccount'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/trakt',
        [\Movary\HttpController\SettingsController::class, 'renderTraktPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/trakt',
        [\Movary\HttpController\SettingsController::class, 'updateTrakt'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/trakt/verify-credentials',
        [\Movary\HttpController\SettingsController::class, 'traktVerifyCredentials'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/letterboxd',
        [\Movary\HttpController\SettingsController::class, 'renderLetterboxdPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/letterboxd-export',
        [\Movary\HttpController\SettingsController::class, 'generateLetterboxdExportData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex',
        [\Movary\HttpController\SettingsController::class, 'renderPlexPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex',
        [\Movary\HttpController\SettingsController::class, 'updatePlex'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/webhook-id',
        [\Movary\HttpController\PlexController::class, 'getPlexWebhookId'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/plex/webhook-id',
        [\Movary\HttpController\PlexController::class, 'regeneratePlexWebhookId'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/plex/webhook-id',
        [\Movary\HttpController\PlexController::class, 'deletePlexWebhookId'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/jellyfin',
        [\Movary\HttpController\SettingsController::class, 'renderJellyfinPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin',
        [\Movary\HttpController\SettingsController::class, 'updateJellyfin'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/jellyfin/webhook-id',
        [\Movary\HttpController\JellyfinController::class, 'getJellyfinWebhookId'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/jellyfin/webhook-id',
        [\Movary\HttpController\JellyfinController::class, 'regenerateJellyfinWebhookId'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/jellyfin/webhook-id',
        [\Movary\HttpController\JellyfinController::class, 'deleteJellyfinWebhookId'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/app',
        [\Movary\HttpController\SettingsController::class, 'renderAppPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/netflix',
        [\Movary\HttpController\SettingsController::class, 'renderNetflixPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix',
        [\Movary\HttpController\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix/import',
        [\Movary\HttpController\NetflixController::class, 'importNetflixData'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix/search',
        [\Movary\HttpController\NetflixController::class, 'searchTmbd'],
    );

    ##########
    # Movies #
    ##########
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/refresh-tmdb',
        [\Movary\HttpController\MovieController::class, 'refreshTmdbData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/refresh-imdb',
        [\Movary\HttpController\MovieController::class, 'refreshImdbRating'],
    );

    ##############
    # User media #
    ##############
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/dashboard',
        [\Movary\HttpController\DashboardController::class, 'render'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/history',
        [\Movary\HttpController\HistoryController::class, 'renderHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/movies',
        [\Movary\HttpController\MoviesController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/actors',
        [\Movary\HttpController\ActorsController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/directors',
        [\Movary\HttpController\DirectorsController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}',
        [\Movary\HttpController\MovieController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}',
        [\Movary\HttpController\PersonController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history',
        [\Movary\HttpController\HistoryController::class, 'deleteHistoryEntry'],
    );
    $routeCollector->addRoute(
        'POST',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history',
        [\Movary\HttpController\HistoryController::class, 'createHistoryEntry'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history',
        [\Movary\HttpController\HistoryController::class, 'updateHistoryEntry'],
    );
    $routeCollector->addRoute(
        'POST',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating',
        [\Movary\HttpController\MovieController::class, 'updateRating'],
    );
    $routeCollector->addRoute(
        'GET',
        '/log-movie',
        [\Movary\HttpController\HistoryController::class, 'renderLogMoviePage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/log-movie',
        [\Movary\HttpController\HistoryController::class, 'logMovie'],
    );
    $routeCollector->addRoute(
        'GET',
        '/fetchMovieRatingByTmdbdId',
        [\Movary\HttpController\MovieController::class, 'fetchMovieRatingByTmdbdId'],
    );

    // Added last, so that more specific routes can be defined (possible username vs route collisions here!)
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}[/]',
        [\Movary\HttpController\DashboardController::class, 'redirectToDashboard'],
    );
};
