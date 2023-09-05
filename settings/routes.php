<?php declare(strict_types=1);

use Movary\HttpController\Api;
use Movary\HttpController\Web;

return static function (FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addGroup('', fn(FastRoute\RouteCollector $routeCollector) => addWebRoutes($routeCollector));
    $routeCollector->addGroup('/api', fn(FastRoute\RouteCollector $routeCollector) => addApiRoutes($routeCollector));
};

function addWebRoutes(FastRoute\RouteCollector $routeCollector) : void
{
    $routeCollector->addRoute('GET', '/', [Web\LandingPageController::class, 'render']);
    $routeCollector->addRoute('POST', '/login', [Web\AuthenticationController::class, 'login']);
    $routeCollector->addRoute('GET', '/login', [Web\AuthenticationController::class, 'renderLoginPage']);
    $routeCollector->addRoute('POST', '/verify-totp', [Web\TwoFactorAuthenticationController::class, 'verifyTotp']);
    $routeCollector->addRoute('GET', '/logout', [Web\AuthenticationController::class, 'logout']);
    $routeCollector->addRoute('POST', '/create-user', [Web\CreateUserController::class, 'createUser']);
    $routeCollector->addRoute('GET', '/create-user', [Web\CreateUserController::class, 'renderPage']);
    $routeCollector->addRoute('GET', '/docs/api', [Web\OpenApiController::class, 'renderPage']);

    #####################
    # Webhook listeners #
    #####################
    $routeCollector->addRoute('POST', '/plex/{id:.+}', [Web\PlexController::class, 'handlePlexWebhook']);
    $routeCollector->addRoute('POST', '/jellyfin/{id:.+}', [Web\JellyfinController::class, 'handleJellyfinWebhook']);
    $routeCollector->addRoute('POST', '/emby/{id:.+}', [Web\EmbyController::class, 'handleEmbyWebhook']);

    #############
    # Job Queue #
    #############
    $routeCollector->addRoute('GET', '/jobs', [Web\JobController::class, 'getJobs']);
    $routeCollector->addRoute('GET', '/job-queue/purge-processed', [Web\JobController::class, 'purgeProcessedJobs']);
    $routeCollector->addRoute('GET', '/job-queue/purge-all', [Web\JobController::class, 'purgeAllJobs']);
    $routeCollector->addRoute('GET', '/jobs/schedule/trakt-history-sync', [Web\JobController::class, 'scheduleTraktHistorySync']);
    $routeCollector->addRoute('GET', '/jobs/schedule/trakt-ratings-sync', [Web\JobController::class, 'scheduleTraktRatingsSync']);
    $routeCollector->addRoute('POST', '/jobs/schedule/letterboxd-diary-sync', [Web\JobController::class, 'scheduleLetterboxdDiaryImport']);
    $routeCollector->addRoute('POST', '/jobs/schedule/letterboxd-ratings-sync', [Web\JobController::class, 'scheduleLetterboxdRatingsImport']);
    $routeCollector->addRoute('GET', '/jobs/schedule/plex-watchlist-sync', [Web\JobController::class, 'schedulePlexWatchlistImport']);
    $routeCollector->addRoute('GET', '/jobs/schedule/jellyfin-import-history', [Web\JobController::class, 'scheduleJellyfinImportHistory']);
    $routeCollector->addRoute('GET', '/jobs/schedule/jellyfin-export-history', [Web\JobController::class, 'scheduleJellyfinExportHistory']);

    ############
    # Settings #
    ############
    $routeCollector->addRoute('GET', '/settings/account/general', [Web\SettingsController::class, 'renderGeneralAccountPage']);
    $routeCollector->addRoute('GET', '/settings/account/general/api-token', [Web\SettingsController::class, 'getApiToken'],);
    $routeCollector->addRoute('DELETE', '/settings/account/general/api-token', [Web\SettingsController::class, 'deleteApiToken'],);
    $routeCollector->addRoute('PUT', '/settings/account/general/api-token', [Web\SettingsController::class, 'regenerateApiToken'],);
    $routeCollector->addRoute('GET', '/settings/account/dashboard', [Web\SettingsController::class, 'renderDashboardAccountPage']);
    $routeCollector->addRoute('GET', '/settings/account/security', [Web\SettingsController::class, 'renderSecurityAccountPage']);
    $routeCollector->addRoute('GET', '/settings/account/data', [Web\SettingsController::class, 'renderDataAccountPage']);
    $routeCollector->addRoute('GET', '/settings/server/general', [Web\SettingsController::class, 'renderServerGeneralPage']);
    $routeCollector->addRoute('GET', '/settings/server/jobs', [Web\SettingsController::class, 'renderServerJobsPage']);
    $routeCollector->addRoute('POST', '/settings/server/general', [Web\SettingsController::class, 'updateServerGeneral']);
    $routeCollector->addRoute('GET', '/settings/server/users', [Web\SettingsController::class, 'renderServerUsersPage']);
    $routeCollector->addRoute('GET', '/settings/server/email', [Web\SettingsController::class, 'renderServerEmailPage']);
    $routeCollector->addRoute('POST', '/settings/server/email', [Web\SettingsController::class, 'updateServerEmail']);
    $routeCollector->addRoute('POST', '/settings/server/email-test', [Web\SettingsController::class, 'sendTestEmail']);
    $routeCollector->addRoute('POST', '/settings/account', [Web\SettingsController::class, 'updateGeneral']);
    $routeCollector->addRoute('POST', '/settings/account/security/update-password', [Web\SettingsController::class, 'updatePassword']);
    $routeCollector->addRoute('POST', '/settings/account/security/create-totp-uri', [Web\TwoFactorAuthenticationController::class, 'createTotpUri']);
    $routeCollector->addRoute('POST', '/settings/account/security/disable-totp', [Web\TwoFactorAuthenticationController::class, 'disableTotp']);
    $routeCollector->addRoute('POST', '/settings/account/security/enable-totp', [Web\TwoFactorAuthenticationController::class, 'enableTotp']);
    $routeCollector->addRoute('GET', '/settings/account/export/csv/{exportType:.+}', [Web\ExportController::class, 'getCsvExport']);
    $routeCollector->addRoute('POST', '/settings/account/import/csv/{exportType:.+}', [Web\ImportController::class, 'handleCsvImport']);
    $routeCollector->addRoute('GET', '/settings/account/delete-ratings', [Web\SettingsController::class, 'deleteRatings']);
    $routeCollector->addRoute('GET', '/settings/account/delete-history', [Web\SettingsController::class, 'deleteHistory']);
    $routeCollector->addRoute('GET', '/settings/account/delete-account', [Web\SettingsController::class, 'deleteAccount']);
    $routeCollector->addRoute('POST', '/settings/account/update-dashboard-rows', [Web\SettingsController::class, 'updateDashboardRows']);
    $routeCollector->addRoute('POST', '/settings/account/reset-dashboard-rows', [Web\SettingsController::class, 'resetDashboardRows']);
    $routeCollector->addRoute('GET', '/settings/integrations/trakt', [Web\SettingsController::class, 'renderTraktPage']);
    $routeCollector->addRoute('POST', '/settings/trakt', [Web\SettingsController::class, 'updateTrakt']);
    $routeCollector->addRoute('POST', '/settings/trakt/verify-credentials', [Web\SettingsController::class, 'traktVerifyCredentials']);
    $routeCollector->addRoute('GET', '/settings/integrations/letterboxd', [Web\SettingsController::class, 'renderLetterboxdPage']);
    $routeCollector->addRoute('GET', '/settings/letterboxd-export', [Web\SettingsController::class, 'generateLetterboxdExportData']);
    $routeCollector->addRoute('GET', '/settings/integrations/plex', [Web\SettingsController::class, 'renderPlexPage']);
    $routeCollector->addRoute('GET', '/settings/plex/logout', [Web\PlexController::class, 'removePlexAccessTokens']);
    $routeCollector->addRoute('POST', '/settings/plex/server-url-save', [Web\PlexController::class, 'savePlexServerUrl']);
    $routeCollector->addRoute('POST', '/settings/plex/server-url-verify', [Web\PlexController::class, 'verifyPlexServerUrl']);
    $routeCollector->addRoute('GET', '/settings/plex/authentication-url', [Web\PlexController::class, 'generatePlexAuthenticationUrl']);
    $routeCollector->addRoute('GET', '/settings/plex/callback', [Web\PlexController::class, 'processPlexCallback']);
    $routeCollector->addRoute('POST', '/settings/plex', [Web\SettingsController::class, 'updatePlex']);
    $routeCollector->addRoute('PUT', '/settings/plex/webhook', [Web\PlexController::class, 'regeneratePlexWebhookUrl']);
    $routeCollector->addRoute('DELETE', '/settings/plex/webhook', [Web\PlexController::class, 'deletePlexWebhookUrl']);
    $routeCollector->addRoute('GET', '/settings/integrations/jellyfin', [Web\SettingsController::class, 'renderJellyfinPage']);
    $routeCollector->addRoute('POST', '/settings/jellyfin', [Web\SettingsController::class, 'updateJellyfin']);
    $routeCollector->addRoute('POST', '/settings/jellyfin/sync', [Web\JellyfinController::class, 'saveJellyfinSyncOptions']);
    $routeCollector->addRoute('POST', '/settings/jellyfin/authenticate', [Web\JellyfinController::class, 'authenticateJellyfinAccount']);
    $routeCollector->addRoute('POST', '/settings/jellyfin/remove-authentication', [Web\JellyfinController::class, 'removeJellyfinAuthentication']);
    $routeCollector->addRoute('POST', '/settings/jellyfin/server-url-save', [Web\JellyfinController::class, 'saveJellyfinServerUrl']);
    $routeCollector->addRoute('POST', '/settings/jellyfin/server-url-verify', [Web\JellyfinController::class, 'verifyJellyfinServerUrl']);
    $routeCollector->addRoute('GET', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'getJellyfinWebhookUrl']);
    $routeCollector->addRoute('PUT', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'regenerateJellyfinWebhookUrl']);
    $routeCollector->addRoute('DELETE', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'deleteJellyfinWebhookUrl']);
    $routeCollector->addRoute('GET', '/settings/integrations/emby', [Web\SettingsController::class, 'renderEmbyPage']);
    $routeCollector->addRoute('POST', '/settings/emby', [Web\SettingsController::class, 'updateEmby']);
    $routeCollector->addRoute('GET', '/settings/emby/webhook', [Web\EmbyController::class, 'getEmbyWebhookUrl']);
    $routeCollector->addRoute('PUT', '/settings/emby/webhook', [Web\EmbyController::class, 'regenerateEmbyWebhookUrl']);
    $routeCollector->addRoute('DELETE', '/settings/emby/webhook', [Web\EmbyController::class, 'deleteEmbyWebhookUrl']);
    $routeCollector->addRoute('GET', '/settings/app', [Web\SettingsController::class, 'renderAppPage']);
    $routeCollector->addRoute('GET', '/settings/integrations/netflix', [Web\SettingsController::class, 'renderNetflixPage']);
    $routeCollector->addRoute('POST', '/settings/netflix', [Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies']);
    $routeCollector->addRoute('POST', '/settings/netflix/import', [Web\NetflixController::class, 'importNetflixData']);
    $routeCollector->addRoute('POST', '/settings/netflix/search', [Web\NetflixController::class, 'searchTmbd']);
    $routeCollector->addRoute('GET', '/settings/users', [Web\UserController::class, 'fetchUsers']);
    $routeCollector->addRoute('POST', '/settings/users', [Web\UserController::class, 'createUser']);
    $routeCollector->addRoute('PUT', '/settings/users/{userId:\d+}', [Web\UserController::class, 'updateUser']);
    $routeCollector->addRoute('DELETE', '/settings/users/{userId:\d+}', [Web\UserController::class, 'deleteUser']);

    ##########
    # Movies #
    ##########
    $routeCollector->addRoute('GET', '/movies/{id:[0-9]+}/refresh-tmdb', [Web\Movie\MovieController::class, 'refreshTmdbData']);
    $routeCollector->addRoute('GET', '/movies/{id:[0-9]+}/refresh-imdb', [Web\Movie\MovieController::class, 'refreshImdbRating']);
    $routeCollector->addRoute('GET', '/movies/{id:[0-9]+}/watch-providers', [Web\Movie\MovieWatchProviderController::class, 'getWatchProviders']);
    $routeCollector->addRoute('GET', '/movies/{id:[0-9]+}/add-watchlist', [Web\Movie\MovieWatchlistController::class, 'addToWatchlist']);
    $routeCollector->addRoute('GET', '/movies/{id:[0-9]+}/remove-watchlist', [Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist']);

    ##############
    # User media #
    ##############
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard', [Web\DashboardController::class, 'render']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Web\HistoryController::class, 'renderHistory']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Web\WatchlistController::class, 'renderWatchlist']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies', [Web\MoviesController::class, 'renderPage']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/actors', [Web\ActorsController::class, 'renderPage']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/directors', [Web\DirectorsController::class, 'renderPage']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}', [Web\Movie\MovieController::class, 'renderPage']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}', [Web\PersonController::class, 'renderPage']);
    $routeCollector->addRoute('DELETE', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'deleteHistoryEntry']);
    $routeCollector->addRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'createHistoryEntry']);
    $routeCollector->addRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating', [Web\Movie\MovieRatingController::class, 'updateRating']);
    $routeCollector->addRoute('POST', '/log-movie', [Web\HistoryController::class, 'logMovie']);
    $routeCollector->addRoute('POST', '/add-movie-to-watchlist', [Web\WatchlistController::class, 'addMovieToWatchlist']);
    $routeCollector->addRoute('GET', '/fetchMovieRatingByTmdbdId', [Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId']);

    // Added last, so that more specific routes can be defined (possible username vs route collisions here!)
    $routeCollector->addRoute('GET', '/{username:[a-zA-Z0-9]+}[/]', [Web\DashboardController::class, 'redirectToDashboard']);
}

function addApiRoutes(FastRoute\RouteCollector $routeCollector) : void
{
    $routeCollector->addRoute('GET', '/openapi.json', [Api\OpenApiController::class, 'getSchema']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Api\HistoryController::class, 'getHistory']);
    $routeCollector->addRoute('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Api\WatchlistController::class, 'getWatchlist']);
}
