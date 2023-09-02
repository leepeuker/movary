<?php declare(strict_types=1);

return static function (FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\Web\LandingPageController::class, 'render'],
    );
    $routeCollector->addRoute(
        'POST',
        '/login',
        [\Movary\HttpController\Web\AuthenticationController::class, 'login'],
    );
    $routeCollector->addRoute(
        'GET',
        '/login',
        [\Movary\HttpController\Web\AuthenticationController::class, 'renderLoginPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/verify-totp',
        [\Movary\HttpController\Web\TwoFactorAuthenticationController::class, 'verifyTotp'],
    );
    $routeCollector->addRoute(
        'GET',
        '/logout',
        [\Movary\HttpController\Web\AuthenticationController::class, 'logout'],
    );
    $routeCollector->addRoute(
        'POST',
        '/create-user',
        [\Movary\HttpController\Web\CreateUserController::class, 'createUser'],
    );
    $routeCollector->addRoute(
        'GET',
        '/create-user',
        [\Movary\HttpController\Web\CreateUserController::class, 'renderPage'],
    );

    #####################
    # Webhook listeners #
    #####################
    $routeCollector->addRoute(
        'POST',
        '/plex/{id:.+}',
        [\Movary\HttpController\Web\PlexController::class, 'handlePlexWebhook'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jellyfin/{id:.+}',
        [\Movary\HttpController\Web\JellyfinController::class, 'handleJellyfinWebhook'],
    );
    $routeCollector->addRoute(
        'POST',
        '/emby/{id:.+}',
        [\Movary\HttpController\Web\EmbyController::class, 'handleEmbyWebhook'],
    );

    #############
    # Job Queue #
    #############
    $routeCollector->addRoute(
        'GET',
        '/jobs',
        [\Movary\HttpController\Web\JobController::class, 'getJobs'],
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue/purge-processed',
        [\Movary\HttpController\Web\JobController::class, 'purgeProcessedJobs'],
    );
    $routeCollector->addRoute(
        'GET',
        '/job-queue/purge-all',
        [\Movary\HttpController\Web\JobController::class, 'purgeAllJobs'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-history-sync',
        [\Movary\HttpController\Web\JobController::class, 'scheduleTraktHistorySync'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/trakt-ratings-sync',
        [\Movary\HttpController\Web\JobController::class, 'scheduleTraktRatingsSync'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jobs/schedule/letterboxd-diary-sync',
        [\Movary\HttpController\Web\JobController::class, 'scheduleLetterboxdDiaryImport'],
    );
    $routeCollector->addRoute(
        'POST',
        '/jobs/schedule/letterboxd-ratings-sync',
        [\Movary\HttpController\Web\JobController::class, 'scheduleLetterboxdRatingsImport'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/plex-watchlist-sync',
        [\Movary\HttpController\Web\JobController::class, 'schedulePlexWatchlistImport'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/jellyfin-import-history',
        [\Movary\HttpController\Web\JobController::class, 'scheduleJellyfinImportHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/jellyfin-export-history',
        [\Movary\HttpController\Web\JobController::class, 'scheduleJellyfinExportHistory'],
    );

    ############
    # Settings #
    ############
    $routeCollector->addRoute(
        'GET',
        '/settings/account/general',
        [\Movary\HttpController\Web\SettingsController::class, 'renderGeneralAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/general/api-token',
        [\Movary\HttpController\Web\SettingsController::class, 'getApiToken'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/account/general/api-token',
        [\Movary\HttpController\Web\SettingsController::class, 'deleteApiToken'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/account/general/api-token',
        [\Movary\HttpController\Web\SettingsController::class, 'regenerateApiToken'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/dashboard',
        [\Movary\HttpController\Web\SettingsController::class, 'renderDashboardAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/security',
        [\Movary\HttpController\Web\SettingsController::class, 'renderSecurityAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/data',
        [\Movary\HttpController\Web\SettingsController::class, 'renderDataAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/general',
        [\Movary\HttpController\Web\SettingsController::class, 'renderServerGeneralPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/jobs',
        [\Movary\HttpController\Web\SettingsController::class, 'renderServerJobsPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/general',
        [\Movary\HttpController\Web\SettingsController::class, 'updateServerGeneral'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/users',
        [\Movary\HttpController\Web\SettingsController::class, 'renderServerUsersPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/email',
        handler: [\Movary\HttpController\Web\SettingsController::class, 'renderServerEmailPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/email',
        [\Movary\HttpController\Web\SettingsController::class, 'updateServerEmail'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/email-test',
        [\Movary\HttpController\Web\SettingsController::class, 'sendTestEmail'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account',
        [\Movary\HttpController\Web\SettingsController::class, 'updateGeneral'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/update-password',
        [\Movary\HttpController\Web\SettingsController::class, 'updatePassword'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/create-totp-uri',
        [\Movary\HttpController\Web\TwoFactorAuthenticationController::class, 'createTotpUri'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/disable-totp',
        [\Movary\HttpController\Web\TwoFactorAuthenticationController::class, 'disableTotp'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/enable-totp',
        [\Movary\HttpController\Web\TwoFactorAuthenticationController::class, 'enableTotp'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/export/csv/{exportType:.+}',
        [\Movary\HttpController\Web\ExportController::class, 'getCsvExport'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/import/csv/{exportType:.+}',
        [\Movary\HttpController\Web\ImportController::class, 'handleCsvImport'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-ratings',
        [\Movary\HttpController\Web\SettingsController::class, 'deleteRatings'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-history',
        [\Movary\HttpController\Web\SettingsController::class, 'deleteHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/delete-account',
        [\Movary\HttpController\Web\SettingsController::class, 'deleteAccount'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/update-dashboard-rows',
        [\Movary\HttpController\Web\SettingsController::class, 'updateDashboardRows'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/reset-dashboard-rows',
        [\Movary\HttpController\Web\SettingsController::class, 'resetDashboardRows'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/trakt',
        [\Movary\HttpController\Web\SettingsController::class, 'renderTraktPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/trakt',
        [\Movary\HttpController\Web\SettingsController::class, 'updateTrakt'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/trakt/verify-credentials',
        [\Movary\HttpController\Web\SettingsController::class, 'traktVerifyCredentials'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/letterboxd',
        [\Movary\HttpController\Web\SettingsController::class, 'renderLetterboxdPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/letterboxd-export',
        [\Movary\HttpController\Web\SettingsController::class, 'generateLetterboxdExportData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/plex',
        [\Movary\HttpController\Web\SettingsController::class, 'renderPlexPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/logout',
        [\Movary\HttpController\Web\PlexController::class, 'removePlexAccessTokens'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex/server-url-save',
        [\Movary\HttpController\Web\PlexController::class, 'savePlexServerUrl'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex/server-url-verify',
        [\Movary\HttpController\Web\PlexController::class, 'verifyPlexServerUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/authentication-url',
        [\Movary\HttpController\Web\PlexController::class, 'generatePlexAuthenticationUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/callback',
        [\Movary\HttpController\Web\PlexController::class, 'processPlexCallback'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex',
        [\Movary\HttpController\Web\SettingsController::class, 'updatePlex'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/plex/webhook',
        [\Movary\HttpController\Web\PlexController::class, 'regeneratePlexWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/plex/webhook',
        [\Movary\HttpController\Web\PlexController::class, 'deletePlexWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/jellyfin',
        [\Movary\HttpController\Web\SettingsController::class, 'renderJellyfinPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin',
        [\Movary\HttpController\Web\SettingsController::class, 'updateJellyfin'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/sync',
        [\Movary\HttpController\Web\JellyfinController::class, 'saveJellyfinSyncOptions'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/authenticate',
        [\Movary\HttpController\Web\JellyfinController::class, 'authenticateJellyfinAccount'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/remove-authentication',
        [\Movary\HttpController\Web\JellyfinController::class, 'removeJellyfinAuthentication'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/server-url-save',
        [\Movary\HttpController\Web\JellyfinController::class, 'saveJellyfinServerUrl'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/server-url-verify',
        [\Movary\HttpController\Web\JellyfinController::class, 'verifyJellyfinServerUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\Web\JellyfinController::class, 'getJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\Web\JellyfinController::class, 'regenerateJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\Web\JellyfinController::class, 'deleteJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/emby',
        [\Movary\HttpController\Web\SettingsController::class, 'renderEmbyPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/emby',
        [\Movary\HttpController\Web\SettingsController::class, 'updateEmby'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/emby/webhook',
        [\Movary\HttpController\Web\EmbyController::class, 'getEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/emby/webhook',
        [\Movary\HttpController\Web\EmbyController::class, 'regenerateEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/emby/webhook',
        [\Movary\HttpController\Web\EmbyController::class, 'deleteEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/app',
        [\Movary\HttpController\Web\SettingsController::class, 'renderAppPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/netflix',
        [\Movary\HttpController\Web\SettingsController::class, 'renderNetflixPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix',
        [\Movary\HttpController\Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix/import',
        [\Movary\HttpController\Web\NetflixController::class, 'importNetflixData'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/netflix/search',
        [\Movary\HttpController\Web\NetflixController::class, 'searchTmbd'],
    );

    ##########
    # Movies #
    ##########
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/refresh-tmdb',
        [\Movary\HttpController\Web\Movie\MovieController::class, 'refreshTmdbData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/refresh-imdb',
        [\Movary\HttpController\Web\Movie\MovieController::class, 'refreshImdbRating'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/watch-providers',
        [\Movary\HttpController\Web\Movie\MovieWatchProviderController::class, 'getWatchProviders'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/add-watchlist',
        [\Movary\HttpController\Web\Movie\MovieWatchlistController::class, 'addToWatchlist'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/remove-watchlist',
        [\Movary\HttpController\Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist'],
    );

    ##############
    # User media #
    ##############
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/dashboard',
        [\Movary\HttpController\Web\DashboardController::class, 'render'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/history',
        [\Movary\HttpController\Web\HistoryController::class, 'renderHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/watchlist',
        [\Movary\HttpController\Web\WatchlistController::class, 'renderWatchlist'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/movies',
        [\Movary\HttpController\Web\MoviesController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/actors',
        [\Movary\HttpController\Web\ActorsController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/directors',
        [\Movary\HttpController\Web\DirectorsController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}',
        [\Movary\HttpController\Web\Movie\MovieController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}',
        [\Movary\HttpController\Web\PersonController::class, 'renderPage'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history',
        [\Movary\HttpController\Web\HistoryController::class, 'deleteHistoryEntry'],
    );
    $routeCollector->addRoute(
        'POST',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history',
        [\Movary\HttpController\Web\HistoryController::class, 'createHistoryEntry'],
    );
    $routeCollector->addRoute(
        'POST',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating',
        [\Movary\HttpController\Web\Movie\MovieRatingController::class, 'updateRating'],
    );
    $routeCollector->addRoute(
        'POST',
        '/log-movie',
        [\Movary\HttpController\Web\HistoryController::class, 'logMovie'],
    );
    $routeCollector->addRoute(
        'POST',
        '/add-movie-to-watchlist',
        [\Movary\HttpController\Web\WatchlistController::class, 'addMovieToWatchlist'],
    );
    $routeCollector->addRoute(
        'GET',
        '/fetchMovieRatingByTmdbdId',
        [\Movary\HttpController\Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId'],
    );

    // Added last, so that more specific routes can be defined (possible username vs route collisions here!)
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}[/]',
        [\Movary\HttpController\Web\DashboardController::class, 'redirectToDashboard'],
    );

    ############
    # REST Api #
    ############
    $routeCollector->addRoute(
        'GET',
        '/api/users',
        [\Movary\HttpController\Web\UserController::class, 'fetchUsers'],
    );
    $routeCollector->addRoute(
        'POST',
        '/api/users',
        [\Movary\HttpController\Web\UserController::class, 'createUser'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/api/users/{userId:\d+}',
        [\Movary\HttpController\Web\UserController::class, 'updateUser'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/api/users/{userId:\d+}',
        [\Movary\HttpController\Web\UserController::class, 'deleteUser'],
    );
};
