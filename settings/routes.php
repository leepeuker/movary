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
    $routes->addRoutes(
        $routerService->createNewRoute('GET', '/', [Web\LandingPageController::class, 'render'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\DoesNotHaveUsers::class),
        $routerService->createNewRoute('GET', '/login', [Web\AuthenticationController::class, 'renderLoginPage'])->addMiddleware(Middleware\isUnauthenticated::class),
        $routerService->createNewRoute('POST', '/login', [Web\AuthenticationController::class, 'login']),
        $routerService->createNewRoute('POST', '/verify-totp', [Web\TwoFactorAuthenticationController::class, 'verifyTotp'])->addMiddleware(Middleware\isUnauthenticated::class),
        $routerService->createNewRoute('GET', '/logout', [Web\AuthenticationController::class, 'logout']),
        $routerService->createNewRoute('POST', '/create-user', [Web\CreateUserController::class, 'createUser'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\HasUsersCheck::class, Middleware\RegistrationEnabledCheck::class),
        $routerService->createNewRoute('GET', '/create-user', [Web\CreateUserController::class, 'renderPage'])->addMiddleware(Middleware\isUnauthenticated::class, Middleware\HasUsersCheck::class, Middleware\RegistrationEnabledCheck::class),
        $routerService->createNewRoute('GET', '/docs/api', [Web\OpenApiController::class, 'renderPage']),

        #####################
        # Webhook listeners #
        #####################
        $routerService->createNewRoute('POST', '/plex/{id:.+}', [Web\PlexController::class, 'handlePlexWebhook']),
        $routerService->createNewRoute('POST', '/jellyfin/{id:.+}', [Web\JellyfinController::class, 'handleJellyfinWebhook']),
        $routerService->createNewRoute('POST', '/emby/{id:.+}', [Web\EmbyController::class, 'handleEmbyWebhook']),

        #############
        # Job Queue #
        #############
        $routerService->createNewRoute('GET', '/jobs', [[Web\JobController::class, 'getJobs'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('GET', '/job-queue/purge-processed', [[Web\JobController::class, 'purgeProcessedJobs'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('GET', '/job-queue/purge-all', [[Web\JobController::class, 'purgeAllJobs'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('GET', '/jobs/schedule/trakt-history-sync', [[Web\JobController::class, 'scheduleTraktHistorySync'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('GET', '/jobs/schedule/trakt-ratings-sync', [[Web\JobController::class, 'scheduleTraktRatingsSync'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('POST', '/jobs/schedule/letterboxd-diary-sync', [[Web\JobController::class, 'scheduleLetterboxdDiaryImport'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('POST', '/jobs/schedule/letterboxd-ratings-sync', [[Web\JobController::class, 'scheduleLetterboxdRatingsImport'], 'middleware' => [Middleware\isAuthenticated::class]]),
        $routerService->createNewRoute('GET', '/jobs/schedule/plex-watchlist-sync', [[Web\JobController::class, 'schedulePlexWatchlistImport'], 'middleware' => [Middleware\isAuthenticated::class, Middleware\HasPlexAccessToken::class]]),
        $routerService->createNewRoute('GET', '/jobs/schedule/jellyfin-import-history', [[Web\JobController::class, 'scheduleJellyfinImportHistory'], 'middleware' => [Middleware\isAuthenticated::class, Middleware\HasJellyfinToken::class]]),
        $routerService->createNewRoute('GET', '/jobs/schedule/jellyfin-export-history', [[Web\JobController::class, 'scheduleJellyfinExportHistory'], 'middleware' => [Middleware\isAuthenticated::class, Middleware\HasJellyfinToken::class]]),

        ############
        # Settings #
        ############
        $routerService->createNewRoute('GET', '/settings/account/general', [Web\SettingsController::class, 'renderGeneralAccountPage'])->addMiddleware(Middleware\isAuthenticated::class),
        $routerService->createNewRoute('GET', '/settings/account/general/api-token', [Web\SettingsController::class, 'getApiToken']),
        $routerService->createNewRoute('DELETE', '/settings/account/general/api-token', [Web\SettingsController::class, 'deleteApiToken']),
        $routerService->createNewRoute('PUT', '/settings/account/general/api-token', [Web\SettingsController::class, 'regenerateApiToken']),
        $routerService->createNewRoute('GET', '/settings/account/dashboard', [Web\SettingsController::class, 'renderDashboardAccountPage']),
        $routerService->createNewRoute('GET', '/settings/account/security', [Web\SettingsController::class, 'renderSecurityAccountPage']),
        $routerService->createNewRoute('GET', '/settings/account/data', [Web\SettingsController::class, 'renderDataAccountPage']),
        $routerService->createNewRoute('GET', '/settings/server/general', [Web\SettingsController::class, 'renderServerGeneralPage']),
        $routerService->createNewRoute('GET', '/settings/server/jobs', [Web\SettingsController::class, 'renderServerJobsPage']),
        $routerService->createNewRoute('POST', '/settings/server/general', [Web\SettingsController::class, 'updateServerGeneral']),
        $routerService->createNewRoute('GET', '/settings/server/users', [Web\SettingsController::class, 'renderServerUsersPage']),
        $routerService->createNewRoute('GET', '/settings/server/email', [Web\SettingsController::class, 'renderServerEmailPage']),
        $routerService->createNewRoute('POST', '/settings/server/email', [Web\SettingsController::class, 'updateServerEmail']),
        $routerService->createNewRoute('POST', '/settings/server/email-test', [Web\SettingsController::class, 'sendTestEmail']),
        $routerService->createNewRoute('POST', '/settings/account', [Web\SettingsController::class, 'updateGeneral']),
        $routerService->createNewRoute('POST', '/settings/account/security/update-password', [Web\SettingsController::class, 'updatePassword']),
        $routerService->createNewRoute('POST', '/settings/account/security/create-totp-uri', [Web\TwoFactorAuthenticationController::class, 'createTotpUri']),
        $routerService->createNewRoute('POST', '/settings/account/security/disable-totp', [Web\TwoFactorAuthenticationController::class, 'disableTotp']),
        $routerService->createNewRoute('POST', '/settings/account/security/enable-totp', [Web\TwoFactorAuthenticationController::class, 'enableTotp']),
        $routerService->createNewRoute('GET', '/settings/account/export/csv/{exportType:.+}', [Web\ExportController::class, 'getCsvExport']),
        $routerService->createNewRoute('POST', '/settings/account/import/csv/{exportType:.+}', [Web\ImportController::class, 'handleCsvImport']),
        $routerService->createNewRoute('GET', '/settings/account/delete-ratings', [Web\SettingsController::class, 'deleteRatings']),
        $routerService->createNewRoute('GET', '/settings/account/delete-history', [Web\SettingsController::class, 'deleteHistory']),
        $routerService->createNewRoute('GET', '/settings/account/delete-account', [Web\SettingsController::class, 'deleteAccount']),
        $routerService->createNewRoute('POST', '/settings/account/update-dashboard-rows', [Web\SettingsController::class, 'updateDashboardRows']),
        $routerService->createNewRoute('POST', '/settings/account/reset-dashboard-rows', [Web\SettingsController::class, 'resetDashboardRows']),
        $routerService->createNewRoute('GET', '/settings/integrations/trakt', [Web\SettingsController::class, 'renderTraktPage']),
        $routerService->createNewRoute('POST', '/settings/trakt', [Web\SettingsController::class, 'updateTrakt']),
        $routerService->createNewRoute('POST', '/settings/trakt/verify-credentials', [Web\SettingsController::class, 'traktVerifyCredentials']),
        $routerService->createNewRoute('GET', '/settings/integrations/letterboxd', [Web\SettingsController::class, 'renderLetterboxdPage']),
        $routerService->createNewRoute('GET', '/settings/letterboxd-export', [Web\SettingsController::class, 'generateLetterboxdExportData']),
        $routerService->createNewRoute('GET', '/settings/integrations/plex', [Web\SettingsController::class, 'renderPlexPage']),
        $routerService->createNewRoute('GET', '/settings/plex/logout', [Web\PlexController::class, 'removePlexAccessTokens']),
        $routerService->createNewRoute('POST', '/settings/plex/server-url-save', [Web\PlexController::class, 'savePlexServerUrl']),
        $routerService->createNewRoute('POST', '/settings/plex/server-url-verify', [Web\PlexController::class, 'verifyPlexServerUrl']),
        $routerService->createNewRoute('GET', '/settings/plex/authentication-url', [Web\PlexController::class, 'generatePlexAuthenticationUrl']),
        $routerService->createNewRoute('GET', '/settings/plex/callback', [Web\PlexController::class, 'processPlexCallback']),
        $routerService->createNewRoute('POST', '/settings/plex', [Web\SettingsController::class, 'updatePlex']),
        $routerService->createNewRoute('PUT', '/settings/plex/webhook', [Web\PlexController::class, 'regeneratePlexWebhookUrl']),
        $routerService->createNewRoute('DELETE', '/settings/plex/webhook', [Web\PlexController::class, 'deletePlexWebhookUrl']),
        $routerService->createNewRoute('GET', '/settings/integrations/jellyfin', [Web\SettingsController::class, 'renderJellyfinPage']),
        $routerService->createNewRoute('POST', '/settings/jellyfin', [Web\SettingsController::class, 'updateJellyfin']),
        $routerService->createNewRoute('POST', '/settings/jellyfin/sync', [Web\JellyfinController::class, 'saveJellyfinSyncOptions']),
        $routerService->createNewRoute('POST', '/settings/jellyfin/authenticate', [Web\JellyfinController::class, 'authenticateJellyfinAccount']),
        $routerService->createNewRoute('POST', '/settings/jellyfin/remove-authentication', [Web\JellyfinController::class, 'removeJellyfinAuthentication']),
        $routerService->createNewRoute('POST', '/settings/jellyfin/server-url-save', [Web\JellyfinController::class, 'saveJellyfinServerUrl']),
        $routerService->createNewRoute('POST', '/settings/jellyfin/server-url-verify', [Web\JellyfinController::class, 'verifyJellyfinServerUrl']),
        $routerService->createNewRoute('GET', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'getJellyfinWebhookUrl']),
        $routerService->createNewRoute('PUT', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'regenerateJellyfinWebhookUrl']),
        $routerService->createNewRoute('DELETE', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'deleteJellyfinWebhookUrl']),
        $routerService->createNewRoute('GET', '/settings/integrations/emby', [Web\SettingsController::class, 'renderEmbyPage']),
        $routerService->createNewRoute('POST', '/settings/emby', [Web\SettingsController::class, 'updateEmby']),
        $routerService->createNewRoute('GET', '/settings/emby/webhook', [Web\EmbyController::class, 'getEmbyWebhookUrl']),
        $routerService->createNewRoute('PUT', '/settings/emby/webhook', [Web\EmbyController::class, 'regenerateEmbyWebhookUrl']),
        $routerService->createNewRoute('DELETE', '/settings/emby/webhook', [Web\EmbyController::class, 'deleteEmbyWebhookUrl']),
        $routerService->createNewRoute('GET', '/settings/app', [Web\SettingsController::class, 'renderAppPage']),
        $routerService->createNewRoute('GET', '/settings/integrations/netflix', [Web\SettingsController::class, 'renderNetflixPage']),
        $routerService->createNewRoute('POST', '/settings/netflix', [Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies']),
        $routerService->createNewRoute('POST', '/settings/netflix/import', [Web\NetflixController::class, 'importNetflixData']),
        $routerService->createNewRoute('POST', '/settings/netflix/search', [Web\NetflixController::class, 'searchTmbd']),
        $routerService->createNewRoute('GET', '/settings/users', [Web\UserController::class, 'fetchUsers']),
        $routerService->createNewRoute('POST', '/settings/users', [Web\UserController::class, 'createUser']),
        $routerService->createNewRoute('PUT', '/settings/users/{userId:\d+}', [Web\UserController::class, 'updateUser']),
        $routerService->createNewRoute('DELETE', '/settings/users/{userId:\d+}', [Web\UserController::class, 'deleteUser']),

        ##########
        # Movies #
        ##########
        $routerService->createNewRoute('GET', '/movies/{id:[0-9]+}/refresh-tmdb', [Web\Movie\MovieController::class, 'refreshTmdbData']),
        $routerService->createNewRoute('GET', '/movies/{id:[0-9]+}/refresh-imdb', [Web\Movie\MovieController::class, 'refreshImdbRating']),
        $routerService->createNewRoute('GET', '/movies/{id:[0-9]+}/watch-providers', [Web\Movie\MovieWatchProviderController::class, 'getWatchProviders']),
        $routerService->createNewRoute('GET', '/movies/{id:[0-9]+}/add-watchlist', [Web\Movie\MovieWatchlistController::class, 'addToWatchlist']),
        $routerService->createNewRoute('GET', '/movies/{id:[0-9]+}/remove-watchlist', [Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist']),

        ##############
        # User media #
        ##############
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard', [Web\DashboardController::class, 'render']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Web\HistoryController::class, 'renderHistory']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Web\WatchlistController::class, 'renderWatchlist']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies', [Web\MoviesController::class, 'renderPage']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/actors', [Web\ActorsController::class, 'renderPage']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/directors', [Web\DirectorsController::class, 'renderPage']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}', [Web\Movie\MovieController::class, 'renderPage']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}', [Web\PersonController::class, 'renderPage']),
        $routerService->createNewRoute('DELETE', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'deleteHistoryEntry']),
        $routerService->createNewRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [Web\HistoryController::class, 'createHistoryEntry']),
        $routerService->createNewRoute('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating', [Web\Movie\MovieRatingController::class, 'updateRating']),
        $routerService->createNewRoute('POST', '/log-movie', [Web\HistoryController::class, 'logMovie']),
        $routerService->createNewRoute('POST', '/add-movie-to-watchlist', [Web\WatchlistController::class, 'addMovieToWatchlist']),
        $routerService->createNewRoute('GET', '/fetchMovieRatingByTmdbdId', [Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId']),
    );
    $routerService->generateRouteCallback($routeCollector, $routes);
}

function addApiRoutes(FastRoute\RouteCollector $routeCollector) : void
{
    $routerService = new RouterService();
    $routes = $routerService->createRouteList();
    $routes->addRoutes(
        $routerService->createNewRoute('GET', '/openapi.jsn', [Api\OpenApiController::class, 'getSchema']),
        $routerService->createNewRoute('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Api\HistoryController::class, 'getHistory']),
    );
    $routerService->generateRouteCallback($routeCollector, $routes);
}