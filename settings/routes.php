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
        'POST',
        '/verify-totp',
        [\Movary\HttpController\TwoFactorAuthenticationController::class, 'verifyTotp'],
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
    $routeCollector->addRoute(
        'GET',
        '/create-user',
        [\Movary\HttpController\CreateUserController::class, 'renderPage'],
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
    $routeCollector->addRoute(
        'POST',
        '/emby/{id:.+}',
        [\Movary\HttpController\EmbyController::class, 'handleEmbyWebhook'],
    );

    #############
    # Job Queue #
    #############
    $routeCollector->addRoute(
        'GET',
        '/jobs',
        [\Movary\HttpController\JobController::class, 'getJobs'],
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
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/plex-watchlist-sync',
        [\Movary\HttpController\JobController::class, 'schedulePlexWatchlistImport'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/jellyfin-import-history',
        [\Movary\HttpController\JobController::class, 'scheduleJellyfinImportHistory'],
    );
    $routeCollector->addRoute(
        'GET',
        '/jobs/schedule/jellyfin-export-history',
        [\Movary\HttpController\JobController::class, 'scheduleJellyfinExportHistory'],
    );

    ############
    # Settings #
    ############
    $routeCollector->addRoute(
        'GET',
        '/settings/account/general',
        [\Movary\HttpController\SettingsController::class, 'renderGeneralAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/dashboard',
        [\Movary\HttpController\SettingsController::class, 'renderDashboardAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/security',
        [\Movary\HttpController\SettingsController::class, 'renderSecurityAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/account/data',
        [\Movary\HttpController\SettingsController::class, 'renderDataAccountPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/general',
        [\Movary\HttpController\SettingsController::class, 'renderServerGeneralPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/jobs',
        [\Movary\HttpController\SettingsController::class, 'renderServerJobsPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/general',
        [\Movary\HttpController\SettingsController::class, 'updateServerGeneral'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/users',
        [\Movary\HttpController\SettingsController::class, 'renderServerUsersPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/users/password-reset',
        [\Movary\HttpController\SettingsController::class, 'fetchAllPasswordResets'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/users/password-reset',
        [\Movary\HttpController\SettingsController::class, 'createPasswordReset'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/server/users/password-reset/{token:.+}',
        [\Movary\HttpController\SettingsController::class, 'deletePasswordReset'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/users/password-reset/{token:.+}/send-email',
        [\Movary\HttpController\SettingsController::class, 'sendPasswordResetEmail'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/server/email',
        handler: [\Movary\HttpController\SettingsController::class, 'renderServerEmailPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/email',
        [\Movary\HttpController\SettingsController::class, 'updateServerEmail'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/server/email-test',
        [\Movary\HttpController\SettingsController::class, 'sendTestEmail'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account',
        [\Movary\HttpController\SettingsController::class, 'updateGeneral'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/update-password',
        [\Movary\HttpController\SettingsController::class, 'updatePassword'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/create-totp-uri',
        [\Movary\HttpController\TwoFactorAuthenticationController::class, 'createTotpUri'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/disable-totp',
        [\Movary\HttpController\TwoFactorAuthenticationController::class, 'disableTotp'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/security/enable-totp',
        [\Movary\HttpController\TwoFactorAuthenticationController::class, 'enableTotp'],
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
        'POST',
        '/settings/account/update-dashboard-rows',
        [\Movary\HttpController\SettingsController::class, 'updateDashboardRows'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/account/reset-dashboard-rows',
        [\Movary\HttpController\SettingsController::class, 'resetDashboardRows'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/trakt',
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
        '/settings/integrations/letterboxd',
        [\Movary\HttpController\SettingsController::class, 'renderLetterboxdPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/letterboxd-export',
        [\Movary\HttpController\SettingsController::class, 'generateLetterboxdExportData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/plex',
        [\Movary\HttpController\SettingsController::class, 'renderPlexPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/logout',
        [\Movary\HttpController\PlexController::class, 'removePlexAccessTokens'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex/server-url-save',
        [\Movary\HttpController\PlexController::class, 'savePlexServerUrl'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex/server-url-verify',
        [\Movary\HttpController\PlexController::class, 'verifyPlexServerUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/authentication-url',
        [\Movary\HttpController\PlexController::class, 'generatePlexAuthenticationUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/plex/callback',
        [\Movary\HttpController\PlexController::class, 'processPlexCallback'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/plex',
        [\Movary\HttpController\SettingsController::class, 'updatePlex'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/plex/webhook',
        [\Movary\HttpController\PlexController::class, 'regeneratePlexWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/plex/webhook',
        [\Movary\HttpController\PlexController::class, 'deletePlexWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/jellyfin',
        [\Movary\HttpController\SettingsController::class, 'renderJellyfinPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin',
        [\Movary\HttpController\SettingsController::class, 'updateJellyfin'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/sync',
        [\Movary\HttpController\JellyfinController::class, 'saveJellyfinSyncOptions'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/authenticate',
        [\Movary\HttpController\JellyfinController::class, 'authenticateJellyfinAccount'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/remove-authentication',
        [\Movary\HttpController\JellyfinController::class, 'removeJellyfinAuthentication'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/server-url-save',
        [\Movary\HttpController\JellyfinController::class, 'saveJellyfinServerUrl'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/jellyfin/server-url-verify',
        [\Movary\HttpController\JellyfinController::class, 'verifyJellyfinServerUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\JellyfinController::class, 'getJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\JellyfinController::class, 'regenerateJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/jellyfin/webhook',
        [\Movary\HttpController\JellyfinController::class, 'deleteJellyfinWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/emby',
        [\Movary\HttpController\SettingsController::class, 'renderEmbyPage'],
    );
    $routeCollector->addRoute(
        'POST',
        '/settings/emby',
        [\Movary\HttpController\SettingsController::class, 'updateEmby'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/emby/webhook',
        [\Movary\HttpController\EmbyController::class, 'getEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/settings/emby/webhook',
        [\Movary\HttpController\EmbyController::class, 'regenerateEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/settings/emby/webhook',
        [\Movary\HttpController\EmbyController::class, 'deleteEmbyWebhookUrl'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/app',
        [\Movary\HttpController\SettingsController::class, 'renderAppPage'],
    );
    $routeCollector->addRoute(
        'GET',
        '/settings/integrations/netflix',
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
        [\Movary\HttpController\Movie\MovieController::class, 'refreshTmdbData'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/refresh-imdb',
        [\Movary\HttpController\Movie\MovieController::class, 'refreshImdbRating'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/watch-providers',
        [\Movary\HttpController\Movie\MovieWatchProviderController::class, 'getWatchProviders'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/add-watchlist',
        [\Movary\HttpController\Movie\MovieWatchlistController::class, 'addToWatchlist'],
    );
    $routeCollector->addRoute(
        'GET',
        '/movies/{id:[0-9]+}/remove-watchlist',
        [\Movary\HttpController\Movie\MovieWatchlistController::class, 'removeFromWatchlist'],
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
        '/users/{username:[a-zA-Z0-9]+}/watchlist',
        [\Movary\HttpController\WatchlistController::class, 'renderWatchlist'],
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
        [\Movary\HttpController\Movie\MovieController::class, 'renderPage'],
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
        'POST',
        '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating',
        [\Movary\HttpController\Movie\MovieRatingController::class, 'updateRating'],
    );
    $routeCollector->addRoute(
        'POST',
        '/log-movie',
        [\Movary\HttpController\HistoryController::class, 'logMovie'],
    );
    $routeCollector->addRoute(
        'POST',
        '/add-movie-to-watchlist',
        [\Movary\HttpController\WatchlistController::class, 'addMovieToWatchlist'],
    );
    $routeCollector->addRoute(
        'GET',
        '/fetchMovieRatingByTmdbdId',
        [\Movary\HttpController\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId'],
    );

    // Added last, so that more specific routes can be defined (possible username vs route collisions here!)
    $routeCollector->addRoute(
        'GET',
        '/{username:[a-zA-Z0-9]+}[/]',
        [\Movary\HttpController\DashboardController::class, 'redirectToDashboard'],
    );

    ############
    # REST Api #
    ############
    $routeCollector->addRoute(
        'GET',
        '/api/users',
        [\Movary\HttpController\Rest\UserController::class, 'fetchUsers'],
    );
    $routeCollector->addRoute(
        'POST',
        '/api/users',
        [\Movary\HttpController\Rest\UserController::class, 'createUser'],
    );
    $routeCollector->addRoute(
        'PUT',
        '/api/users/{userId:\d+}',
        [\Movary\HttpController\Rest\UserController::class, 'updateUser'],
    );
    $routeCollector->addRoute(
        'DELETE',
        '/api/users/{userId:\d+}',
        [\Movary\HttpController\Rest\UserController::class, 'deleteUser'],
    );
};
