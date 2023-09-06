<?php declare(strict_types=1);


use Movary\HttpController\Api;
use Movary\HttpController\Web;
use Movary\HttpController\Middleware;
use Movary\Service\Router\RouterService;

return function (FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addGroup('', fn($routeCollector) => addWebRoutes($routeCollector));
    $routeCollector->addGroup('/api', fn($routeCollector) => addApiRoutes($routeCollector));
};

function addWebRoutes(FastRoute\RouteCollector $routeCollector) : void
{
    $routerService = new RouterService();
    $routes = $routerService->createRouteList();
    $routes->createNewRoute('GET', '/', [Web\LandingPageController::class, 'render'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\DoesNotHaveUsers::class);
    $routes->createNewRoute('GET', '/login', [Web\AuthenticationController::class, 'renderLoginPage'])->addMiddleware(Middleware\isUnauthenticated::class);
    $routes->createNewRoute('POST', '/login', [Web\AuthenticationController::class, 'login']);
    $routes->createNewRoute('POST', '/verify-totp', [Web\TwoFactorAuthenticationController::class, 'verifyTotp'])->addMiddleware(Middleware\isUnauthenticated::class);
    $routes->createNewRoute('GET', '/logout', [Web\AuthenticationController::class, 'logout']);
    $routes->createNewRoute('POST', '/create-user', [Web\CreateUserController::class, 'createUser'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\HasUsersCheck::class, Middleware\RegistrationEnabledCheck::class);
    $routes->createNewRoute('GET', '/create-user', [Web\CreateUserController::class, 'renderPage'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\HasUsersCheck::class, Middleware\RegistrationEnabledCheck::class);
    $routes->createNewRoute('GET', '/docs/api', [Web\OpenApiController::class, 'renderPage']);

    #####################
    # Webhook listeners #
    #####################
    $routes->createNewRoute('POST', '/plex/{id:.+}', [Web\PlexController::class, 'handlePlexWebhook']);
    $routes->createNewRoute('POST', '/jellyfin/{id:.+}', [Web\JellyfinController::class, 'handleJellyfinWebhook']);
    $routes->createNewRoute('POST', '/emby/{id:.+}', [Web\EmbyController::class, 'handleEmbyWebhook']);

    #############
    # Job Queue #
    #############
    $routes->createNewRoute('GET', '/jobs', [Web\JobController::class, 'getJobs'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/job-queue/purge-processed', [Web\JobController::class, 'purgeProcessedJobs'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/job-queue/purge-all', [Web\JobController::class, 'purgeAllJobs'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/jobs/schedule/trakt-history-sync', [Web\JobController::class, 'scheduleTraktHistorySync'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/jobs/schedule/trakt-ratings-sync', [Web\JobController::class, 'scheduleTraktRatingsSync'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/jobs/schedule/letterboxd-diary-sync', [Web\JobController::class, 'scheduleLetterboxdDiaryImport'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/jobs/schedule/letterboxd-ratings-sync', [Web\JobController::class, 'scheduleLetterboxdRatingsImport'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/jobs/schedule/plex-watchlist-sync', [Web\JobController::class, 'schedulePlexWatchlistImport'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\HasPlexAccessToken::class);
    $routes->createNewRoute('GET', '/jobs/schedule/jellyfin-import-history', [Web\JobController::class, 'scheduleJellyfinImportHistory'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\HasJellyfinToken::class);
    $routes->createNewRoute('GET', '/jobs/schedule/jellyfin-export-history', [Web\JobController::class, 'scheduleJellyfinExportHistory'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\HasJellyfinToken::class);

    ############
    # Settings #
    ############
    $routes->createNewRoute('GET', '/settings/account/general', [Web\SettingsController::class, 'renderGeneralAccountPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/general/api-token', [Web\SettingsController::class, 'getApiToken'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('DELETE', '/settings/account/general/api-token', [Web\SettingsController::class, 'deleteApiToken'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('PUT', '/settings/account/general/api-token', [Web\SettingsController::class, 'regenerateApiToken'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/dashboard', [Web\SettingsController::class, 'renderDashboardAccountPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/security', [Web\SettingsController::class, 'renderSecurityAccountPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/data', [Web\SettingsController::class, 'renderDataAccountPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/server/general', [Web\SettingsController::class, 'renderServerGeneralPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/server/jobs', [Web\SettingsController::class, 'renderServerJobsPage'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('POST', '/settings/server/general', [Web\SettingsController::class, 'updateServerGeneral'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('GET', '/settings/server/users', [Web\SettingsController::class, 'renderServerUsersPage'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('GET', '/settings/server/email', [Web\SettingsController::class, 'renderServerEmailPage'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('POST', '/settings/server/email', [Web\SettingsController::class, 'updateServerEmail'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('POST', '/settings/server/email-test', [Web\SettingsController::class, 'sendTestEmail'])->addMiddleware(Middleware\isAuthenticated::class, Middleware\IsAdmin::class);
    $routes->createNewRoute('POST', '/settings/account', [Web\SettingsController::class, 'updateGeneral'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/security/update-password', [Web\SettingsController::class, 'updatePassword'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/security/create-totp-uri', [Web\TwoFactorAuthenticationController::class, 'createTotpUri'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/security/disable-totp', [Web\TwoFactorAuthenticationController::class, 'disableTotp'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/security/enable-totp', [Web\TwoFactorAuthenticationController::class, 'enableTotp'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/export/csv/{exportType:.+}', [Web\ExportController::class, 'getCsvExport'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/import/csv/{exportType:.+}', [Web\ImportController::class, 'handleCsvImport'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/delete-ratings', [Web\SettingsController::class, 'deleteRatings'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/delete-history', [Web\SettingsController::class, 'deleteHistory'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/account/delete-account', [Web\SettingsController::class, 'deleteAccount'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/update-dashboard-rows', [Web\SettingsController::class, 'updateDashboardRows'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/account/reset-dashboard-rows', [Web\SettingsController::class, 'resetDashboardRows'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/trakt', [Web\SettingsController::class, 'renderTraktPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/trakt', [Web\SettingsController::class, 'updateTrakt'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/trakt/verify-credentials', [Web\SettingsController::class, 'traktVerifyCredentials'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/letterboxd', [Web\SettingsController::class, 'renderLetterboxdPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/letterboxd-export', [Web\SettingsController::class, 'generateLetterboxdExportData'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/plex', [Web\SettingsController::class, 'renderPlexPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/plex/logout', [Web\PlexController::class, 'removePlexAccessTokens'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/plex/server-url-save', [Web\PlexController::class, 'savePlexServerUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/plex/server-url-verify', [Web\PlexController::class, 'verifyPlexServerUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/plex/authentication-url', [Web\PlexController::class, 'generatePlexAuthenticationUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/plex/callback', [Web\PlexController::class, 'processPlexCallback'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/plex', [Web\SettingsController::class, 'updatePlex'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('PUT', '/settings/plex/webhook', [Web\PlexController::class, 'regeneratePlexWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('DELETE', '/settings/plex/webhook', [Web\PlexController::class, 'deletePlexWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/jellyfin', [Web\SettingsController::class, 'renderJellyfinPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin', [Web\SettingsController::class, 'updateJellyfin'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin/sync', [Web\JellyfinController::class, 'saveJellyfinSyncOptions'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin/authenticate', [Web\JellyfinController::class, 'authenticateJellyfinAccount'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin/remove-authentication', [Web\JellyfinController::class, 'removeJellyfinAuthentication'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin/server-url-save', [Web\JellyfinController::class, 'saveJellyfinServerUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/jellyfin/server-url-verify', [Web\JellyfinController::class, 'verifyJellyfinServerUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'getJellyfinWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('PUT', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'regenerateJellyfinWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('DELETE', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'deleteJellyfinWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/emby', [Web\SettingsController::class, 'renderEmbyPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/emby', [Web\SettingsController::class, 'updateEmby'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/emby/webhook', [Web\EmbyController::class, 'getEmbyWebhookUrl']);
    $routes->createNewRoute('PUT', '/settings/emby/webhook', [Web\EmbyController::class, 'regenerateEmbyWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('DELETE', '/settings/emby/webhook', [Web\EmbyController::class, 'deleteEmbyWebhookUrl'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/app', [Web\SettingsController::class, 'renderAppPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/integrations/netflix', [Web\SettingsController::class, 'renderNetflixPage'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/netflix', [Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/netflix/import', [Web\NetflixController::class, 'importNetflixData'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/settings/netflix/search', [Web\NetflixController::class, 'searchTmbd'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/settings/users', [Web\UserController::class, 'fetchUsers']);
    $routes->createNewRoute('POST', '/settings/users', [Web\UserController::class, 'createUser']);
    $routes->createNewRoute('PUT', '/settings/users/{userId:\d+}', [Web\UserController::class, 'updateUser'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('DELETE', '/settings/users/{userId:\d+}', [Web\UserController::class, 'deleteUser'])->addMiddleware(Middleware\isAuthenticated::class);

    ##########
    # Movies #
    ##########
    $routes->createNewRoute('GET', '/movies/{id:[0-9]+}/refresh-tmdb', [Web\Movie\MovieController::class, 'refreshTmdbData'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/movies/{id:[0-9]+}/refresh-imdb', [Web\Movie\MovieController::class, 'refreshImdbRating'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/movies/{id:[0-9]+}/watch-providers', [Web\Movie\MovieWatchProviderController::class, 'getWatchProviders']);
    $routes->createNewRoute('GET', '/movies/{id:[0-9]+}/add-watchlist', [Web\Movie\MovieWatchlistController::class, 'addToWatchlist'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/movies/{id:[0-9]+}/remove-watchlist', [Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist'])->addMiddleware(Middleware\isAuthenticated::class);

    ##############
    # User media #
    ##############
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard', [Web\DashboardController::class, 'render'])->addMiddleware(Middleware\CanUserBeViewed::class);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Web\HistoryController::class, 'renderHistory']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Web\WatchlistController::class, 'renderWatchlist']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies', [Web\MoviesController::class, 'renderPage']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/actors', [Web\ActorsController::class, 'renderPage']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/directors', [Web\DirectorsController::class, 'renderPage']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}', [Web\Movie\MovieController::class, 'renderPage']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}', [Web\PersonController::class, 'renderPage']);
    $routes->createNewRoute('DELETE', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'deleteHistoryEntry'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'createHistoryEntry'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating', [Web\Movie\MovieRatingController::class, 'updateRating'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/log-movie', [Web\HistoryController::class, 'logMovie'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('POST', '/add-movie-to-watchlist', [Web\WatchlistController::class, 'addMovieToWatchlist'])->addMiddleware(Middleware\isAuthenticated::class);
    $routes->createNewRoute('GET', '/fetchMovieRatingByTmdbdId', [Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId'])->addMiddleware(Middleware\isAuthenticated::class);
    $routerService->generateRouteCallback($routeCollector, $routes);
}

function addApiRoutes(FastRoute\RouteCollector $routeCollector) : void
{
    $routerService = new RouterService();
    $routes = $routerService->createRouteList();
    $routes->createNewRoute('GET', '/openapi.json', [Api\OpenApiController::class, 'getSchema']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Api\HistoryController::class, 'getHistory']);
    $routes->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Api\WatchlistController::class, 'getWatchlist']);
    $routerService->generateRouteCallback($routeCollector, $routes);
}
