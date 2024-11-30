<?php declare(strict_types=1);

use Movary\HttpController\Api;
use Movary\HttpController\Web;
use Movary\HttpController\Web\RadarrController;
use Movary\Service\Router\Dto\RouteList;
use Movary\Service\Router\RouterService;

return function (FastRoute\RouteCollector $routeCollector) {
    $routerService = new RouterService();

    $routeCollector->addGroup('', fn($routeCollector) => addWebRoutes($routerService, $routeCollector));
    $routeCollector->addGroup('/api', fn($routeCollector) => addApiRoutes($routerService, $routeCollector));
};

function addWebRoutes(RouterService $routerService, FastRoute\RouteCollector $routeCollector) : void
{
    $routes = RouteList::create();

    $routes->add('GET', '/', [Web\LandingPageController::class, 'render'], [Web\Middleware\UserIsUnauthenticated::class, Web\Middleware\ServerHasNoUsers::class]);
    $routes->add('GET', '/login', [Web\AuthenticationController::class, 'renderLoginPage'], [Web\Middleware\UserIsUnauthenticated::class]);
    $routes->add('POST', '/create-user', [Web\CreateUserController::class, 'createUser'], [
        Web\Middleware\UserIsUnauthenticated::class,
        Web\Middleware\ServerHasUsers::class,
        Web\Middleware\ServerHasRegistrationEnabled::class
    ]);
    $routes->add('GET', '/create-user', [Web\CreateUserController::class, 'renderPage'], [
        Web\Middleware\UserIsUnauthenticated::class,
        Web\Middleware\ServerHasUsers::class,
        Web\Middleware\ServerHasRegistrationEnabled::class
    ]);
    $routes->add('GET', '/docs/api', [Web\OpenApiController::class, 'renderPage']);

    #####################
    # Webhook listeners # !!! Deprecated use new api routes
    #####################
    $routes->add('POST', '/plex/{id:.+}', [Web\PlexController::class, 'handlePlexWebhook']);
    $routes->add('POST', '/jellyfin/{id:.+}', [Web\JellyfinController::class, 'handleJellyfinWebhook']);
    $routes->add('POST', '/emby/{id:.+}', [Web\EmbyController::class, 'handleEmbyWebhook']);

    #############
    # Job Queue #
    #############
    $routes->add('GET', '/jobs', [Web\JobController::class, 'getJobs'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/job-queue/purge-processed', [Web\JobController::class, 'purgeProcessedJobs'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/job-queue/purge-all', [Web\JobController::class, 'purgeAllJobs'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/jobs/schedule/trakt-history-sync', [Web\JobController::class, 'scheduleTraktHistorySync'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/jobs/schedule/trakt-ratings-sync', [Web\JobController::class, 'scheduleTraktRatingsSync'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/letterboxd-diary-sync', [Web\JobController::class, 'scheduleLetterboxdDiaryImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/letterboxd-ratings-sync', [Web\JobController::class, 'scheduleLetterboxdRatingsImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/jobs/schedule/plex-watchlist-sync', [Web\JobController::class, 'schedulePlexWatchlistImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/jobs/schedule/jellyfin-import-history', [Web\JobController::class, 'scheduleJellyfinImportHistory'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/jobs/schedule/jellyfin-export-history', [Web\JobController::class, 'scheduleJellyfinExportHistory'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserHasJellyfinToken::class
    ]);

    ############
    # Settings #
    ############
    $routes->add('GET', '/settings/account/general', [Web\SettingsController::class, 'renderGeneralAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/general/api-token', [Web\SettingsController::class, 'getApiToken'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/general/api-token', [Web\SettingsController::class, 'deleteApiToken'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/account/general/api-token', [Web\SettingsController::class, 'regenerateApiToken'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/dashboard', [Web\SettingsController::class, 'renderDashboardAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/locations', [Web\SettingsController::class, 'renderLocationsAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/security', [Web\SettingsController::class, 'renderSecurityAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/data', [Web\SettingsController::class, 'renderDataAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/server/general', [Web\SettingsController::class, 'renderServerGeneralPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/server/jobs', [Web\SettingsController::class, 'renderServerJobsPage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/server/general', [Web\SettingsController::class, 'updateServerGeneral'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('GET', '/settings/server/users', [Web\SettingsController::class, 'renderServerUsersPage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('GET', '/settings/server/email', [Web\SettingsController::class, 'renderServerEmailPage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/server/email', [Web\SettingsController::class, 'updateServerEmail'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/server/email-test', [Web\SettingsController::class, 'sendTestEmail'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/account', [Web\SettingsController::class, 'updateGeneral'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/update-password', [Web\SettingsController::class, 'updatePassword'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/create-totp-uri', [
        Web\TwoFactorAuthenticationController::class,
        'createTotpUri'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/disable-totp', [Web\TwoFactorAuthenticationController::class, 'disableTotp'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/enable-totp', [Web\TwoFactorAuthenticationController::class, 'enableTotp'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/export/csv/{exportType:.+}', [Web\ExportController::class, 'getCsvExport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/import/csv/{exportType:.+}', [Web\ImportController::class, 'handleCsvImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/delete-ratings', [Web\SettingsController::class, 'deleteRatings'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/delete-history', [Web\SettingsController::class, 'deleteHistory'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/delete-account', [Web\SettingsController::class, 'deleteAccount'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/update-dashboard-rows', [Web\SettingsController::class, 'updateDashboardRows'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/reset-dashboard-rows', [Web\SettingsController::class, 'resetDashboardRows'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/trakt', [Web\SettingsController::class, 'renderTraktPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/trakt', [Web\SettingsController::class, 'updateTrakt'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/trakt/verify-credentials', [Web\SettingsController::class, 'traktVerifyCredentials'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/letterboxd', [Web\SettingsController::class, 'renderLetterboxdPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/letterboxd-export', [Web\SettingsController::class, 'generateLetterboxdExportData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/plex', [Web\SettingsController::class, 'renderPlexPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/plex/logout', [Web\PlexController::class, 'removePlexAccessTokens'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex/server-url-save', [Web\PlexController::class, 'savePlexServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex/server-url-verify', [Web\PlexController::class, 'verifyPlexServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/plex/authentication-url', [Web\PlexController::class, 'generatePlexAuthenticationUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/plex/callback', [Web\PlexController::class, 'processPlexCallback'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex', [Web\SettingsController::class, 'updatePlex'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/plex/webhook', [Web\PlexController::class, 'regeneratePlexWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/plex/webhook', [Web\PlexController::class, 'deletePlexWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/jellyfin', [Web\SettingsController::class, 'renderJellyfinPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin', [Web\SettingsController::class, 'updateJellyfin'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin/sync', [Web\JellyfinController::class, 'saveJellyfinSyncOptions'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin/authenticate', [Web\JellyfinController::class, 'authenticateJellyfinAccount'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin/remove-authentication', [Web\JellyfinController::class, 'removeJellyfinAuthentication'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin/server-url-save', [Web\JellyfinController::class, 'saveJellyfinServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/jellyfin/server-url-verify', [Web\JellyfinController::class, 'verifyJellyfinServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'getJellyfinWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'regenerateJellyfinWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/jellyfin/webhook', [Web\JellyfinController::class, 'deleteJellyfinWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/emby', [Web\SettingsController::class, 'renderEmbyPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/emby', [Web\SettingsController::class, 'updateEmby'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/emby/webhook', [Web\EmbyController::class, 'getEmbyWebhookUrl']);
    $routes->add('PUT', '/settings/emby/webhook', [Web\EmbyController::class, 'regenerateEmbyWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/emby/webhook', [Web\EmbyController::class, 'deleteEmbyWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/app', [Web\SettingsController::class, 'renderAppPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/netflix', [Web\SettingsController::class, 'renderNetflixPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/netflix', [Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/netflix/import', [Web\NetflixController::class, 'importNetflixData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/netflix/search', [Web\NetflixController::class, 'searchTmbd'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/users', [Web\UserController::class, 'fetchUsers']);
    $routes->add('POST', '/settings/users', [Web\UserController::class, 'createUser']);
    $routes->add('PUT', '/settings/users/{userId:\d+}', [Web\UserController::class, 'updateUser'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/users/{userId:\d+}', [Web\UserController::class, 'deleteUser'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/locations', [Web\LocationController::class, 'fetchLocations'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/locations', [Web\LocationController::class, 'createLocation'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/locations/{locationId:\d+}', [Web\LocationController::class, 'updateLocation'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/locations/{locationId:\d+}', [Web\LocationController::class, 'deleteLocation'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/locations/toggle-feature', [Web\LocationController::class, 'fetchToggleFeature'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/locations/toggle-feature', [Web\LocationController::class, 'updateToggleFeature'], [Web\Middleware\UserIsAuthenticated::class]);

    $routes->add('GET', '/settings/integrations/radarr', [Web\SettingsController::class, 'renderRadarrPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/radarr/feed', [RadarrController::class, 'regenerateRadarrFeedUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/radarr/feed', [RadarrController::class, 'deleteRadarrFeedUrl'], [Web\Middleware\UserIsAuthenticated::class]);


    ##########
    # Movies #
    ##########
    $routes->add('GET', '/movies/{id:[0-9]+}/refresh-tmdb', [Web\Movie\MovieController::class, 'refreshTmdbData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/movies/{id:[0-9]+}/refresh-imdb', [Web\Movie\MovieController::class, 'refreshImdbRating'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/movies/{id:[0-9]+}/watch-providers', [Web\Movie\MovieWatchProviderController::class, 'getWatchProviders']);
    $routes->add('GET', '/movies/{id:[0-9]+}/add-watchlist', [Web\Movie\MovieWatchlistController::class, 'addToWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/movies/{id:[0-9]+}/remove-watchlist', [Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);

    ##########
    # Person #
    ##########
    $routes->add('GET', '/persons/{id:[0-9]+}/refresh-tmdb', [Web\PersonController::class, 'refreshTmdbData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/persons/{id:[0-9]+}/hide-in-top-lists', [Web\PersonController::class, 'hideInTopLists'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/persons/{id:[0-9]+}/show-in-top-lists', [Web\PersonController::class, 'showInTopLists'], [Web\Middleware\UserIsAuthenticated::class]);

    ##############
    # User media #
    ##############
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard', [Web\DashboardController::class, 'render'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Web\HistoryController::class, 'renderHistory'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Web\WatchlistController::class, 'renderWatchlist'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/movies', [Web\MoviesController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/actors', [Web\ActorsController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/directors', [Web\DirectorsController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}', [Web\Movie\MovieController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}', [Web\PersonController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('DELETE', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [
        Web\HistoryController::class,
        'deleteHistoryEntry'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/history', [
        Web\HistoryController::class,
        'createHistoryEntry'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/rating', [
        Web\Movie\MovieRatingController::class,
        'updateRating'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/log-movie', [Web\HistoryController::class, 'logMovie'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/add-movie-to-watchlist', [Web\WatchlistController::class, 'addMovieToWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/fetchMovieRatingByTmdbdId', [Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId'], [Web\Middleware\UserIsAuthenticated::class]);

    $routerService->addRoutesToRouteCollector($routeCollector, $routes, true);
}

function addApiRoutes(RouterService $routerService, FastRoute\RouteCollector $routeCollector) : void
{
    $routes = RouteList::create();

    $routes->add('GET', '/openapi', [Api\OpenApiController::class, 'getSchema']);
    $routes->add('POST', '/authentication/token', [Api\AuthenticationController::class, 'createToken']);
    $routes->add('DELETE', '/authentication/token', [Api\AuthenticationController::class, 'destroyToken']);
    $routes->add('GET', '/authentication/token', [Api\AuthenticationController::class, 'getTokenData']);

    $routeUserHistory = '/users/{username:[a-zA-Z0-9]+}/history/movies';
    $routes->add('GET', $routeUserHistory, [Api\HistoryController::class, 'getHistory'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('POST', $routeUserHistory, [Api\HistoryController::class, 'addToHistory'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserHistory, [Api\HistoryController::class, 'deleteFromHistory'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('PUT', $routeUserHistory, [Api\HistoryController::class, 'updateHistory'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routeUserWatchlist = '/users/{username:[a-zA-Z0-9]+}/watchlist/movies';
    $routes->add('GET', $routeUserWatchlist, [Api\WatchlistController::class, 'getWatchlist'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('POST', $routeUserWatchlist, [Api\WatchlistController::class, 'addToWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserWatchlist, [Api\WatchlistController::class, 'deleteFromWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routeUserPlayed = '/users/{username:[a-zA-Z0-9]+}/played/movies';
    $routes->add('GET', $routeUserPlayed, [Api\PlayedController::class, 'getPlayed'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('POST', $routeUserPlayed, [Api\PlayedController::class, 'addToPlayed'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserPlayed, [Api\PlayedController::class, 'deleteFromPlayed'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('PUT', $routeUserPlayed, [Api\PlayedController::class, 'updatePlayed'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routes->add('GET', '/movies/search', [Api\MovieSearchController::class, 'search'], [Api\Middleware\IsAuthenticated::class]);

    $routes->add('POST', '/webhook/plex/{id:.+}', [Api\PlexController::class, 'handlePlexWebhook']);
    $routes->add('POST', '/webhook/jellyfin/{id:.+}', [Api\JellyfinController::class, 'handleJellyfinWebhook']);
    $routes->add('POST', '/webhook/emby/{id:.+}', [Api\EmbyController::class, 'handleEmbyWebhook']);

    $routes->add('GET', '/feed/radarr/{id:.+}', [Api\RadarrController::class, 'renderRadarrFeed']);

    $routerService->addRoutesToRouteCollector($routeCollector, $routes);
}
