<?php declare(strict_types=1);

use Movary\HttpController\Api;
use Movary\HttpController\Web;
use Movary\HttpController\Web\RadarrController;
use Movary\Service\Router\Dto\RouteList;
use Movary\Service\Router\RouterService;

return function (FastRoute\RouteCollector $routeCollector) {
    $routerService = new RouterService();

    $routeCollector->addGroup('', fn($routeCollector) => addWebRoutes($routerService, $routeCollector));
    $routeCollector->addGroup('/api/v1', fn($routeCollector) => addApiRoutes($routerService, $routeCollector));
    // Deprecated: remove the unversioned API aliases in Movary 1.0.
    $routeCollector->addGroup('/api', fn($routeCollector) => addApiRoutes($routerService, $routeCollector));
    addIntegrationRoutes($routerService, $routeCollector, '/webhooks', '/feeds');
    // Deprecated: remove the integration aliases under /api in Movary 1.0.
    addIntegrationRoutes($routerService, $routeCollector, '/api/webhook', '/api/feed');
};

function addWebRoutes(RouterService $routerService, FastRoute\RouteCollector $routeCollector) : void
{
    $routes = RouteList::create();

    $routes->add('GET', '/', [Web\AuthenticationController::class, 'renderLoginPage'], [Web\Middleware\UserIsUnauthenticated::class, Web\Middleware\ServerHasNoUsers::class]);
    $routes->add('GET', '/login', [Web\AuthenticationController::class, 'renderLoginPage'], [Web\Middleware\UserIsUnauthenticated::class]);
    $routes->add('POST', '/login', [Web\AuthenticationController::class, 'login'], [Web\Middleware\UserIsUnauthenticated::class]);
    $routes->add('DELETE', '/logout', [Web\LogoutController::class, 'logout'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/forgot-password', [Web\PasswordResetController::class, 'renderRequestPage'], [
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('POST', '/forgot-password', [Web\PasswordResetController::class, 'requestReset'], [
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('GET', '/reset-password', [Web\PasswordResetController::class, 'renderResetPage'], [
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('POST', '/reset-password', [Web\PasswordResetController::class, 'resetPassword'], [
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('GET', '/verify-email', [Web\EmailVerificationController::class, 'renderVerificationPage']);
    $routes->add('POST', '/verify-email', [Web\EmailVerificationController::class, 'verifyEmail']);
    $routes->add('POST', '/verify-email/resend', [Web\EmailVerificationController::class, 'resend'], [
        Web\Middleware\EmailVerificationIsAvailable::class,
    ]);
    $routes->add('POST', '/create-user', [Web\CreateUserController::class, 'createUser'], [
        Web\Middleware\UserIsUnauthenticated::class,
        Web\Middleware\ServerHasRegistrationEnabled::class,
    ]);
    $routes->add('GET', '/create-user', [Web\CreateUserController::class, 'renderPage'], [
        Web\Middleware\UserIsUnauthenticated::class,
        Web\Middleware\ServerHasRegistrationEnabled::class
    ]);
    $routes->add('GET', '/docs/api', [Web\OpenApiController::class, 'renderPage']);
    $routes->add('GET', '/manifest.webmanifest', [Web\ManifestController::class, 'render']);
    // placeholder image generator
    $routes->add('GET', '/images/placeholder/{imageNameBase64Encoded:.+}', [Web\PlaceholderImageController::class, 'renderPlaceholderImage']);

    #####################
    # Webhook listeners # Deprecated: remove in Movary 1.0; use /webhooks routes.
    #####################
    $routes->add('POST', '/plex/{id:.+}', [Web\PlexController::class, 'handlePlexWebhook'], csrfProtectionEnabled: false);
    $routes->add('POST', '/jellyfin/{id:.+}', [Web\JellyfinController::class, 'handleJellyfinWebhook'], csrfProtectionEnabled: false);
    $routes->add('POST', '/emby/{id:.+}', [Web\EmbyController::class, 'handleEmbyWebhook'], csrfProtectionEnabled: false);

    #############
    # Job Queue #
    #############
    $routes->add('GET', '/jobs', [Web\JobController::class, 'getJobs'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/job-queue/{jobId:\d+}', [Web\JobController::class, 'deleteJob'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('DELETE', '/job-schedules/{scheduleId:\d+}', [Web\JobController::class, 'deleteJobSchedule'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/job-schedules/{scheduleId:\d+}', [Web\JobController::class, 'updateJobSchedule'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('DELETE', '/job-schedules', [Web\JobController::class, 'deleteAllJobSchedules'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/job-queue/purge-processed', [Web\JobController::class, 'purgeProcessedJobs'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/job-queue/purge-all', [Web\JobController::class, 'purgeAllJobs'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/jobs/schedule/trakt-history-sync', [Web\JobController::class, 'scheduleTraktHistorySync'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/trakt-ratings-sync', [Web\JobController::class, 'scheduleTraktRatingsSync'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/letterboxd-diary-sync', [Web\JobController::class, 'scheduleLetterboxdDiaryImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/letterboxd-ratings-sync', [Web\JobController::class, 'scheduleLetterboxdRatingsImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/plex-watchlist-sync', [Web\JobController::class, 'schedulePlexWatchlistImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/jellyfin-import-history', [Web\JobController::class, 'scheduleJellyfinImportHistory'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/jobs/schedule/jellyfin-export-history', [Web\JobController::class, 'scheduleJellyfinExportHistory'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserHasJellyfinToken::class,
    ]);
    $routes->add('POST', '/jobs/schedule/tmdb-movie-sync', [Web\JobController::class, 'scheduleTmdbMovieSync'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/jobs/schedule/tmdb-person-sync', [Web\JobController::class, 'scheduleTmdbPersonSync'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/jobs/schedule/imdb-ratings-sync', [Web\JobController::class, 'scheduleImdbRatingsSync'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/jobs/schedule/tmdb-image-cache-refresh', [Web\JobController::class, 'scheduleTmdbImageCacheRefresh'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);

    ############
    # Settings #
    ############
    $routes->add('GET', '/settings/account/general', [Web\SettingsController::class, 'renderGeneralAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/api-tokens', [Web\SettingsController::class, 'renderApiTokensAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/api-tokens', [Web\SettingsController::class, 'createApiToken'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/api-tokens', [Web\SettingsController::class, 'revokeAllApiTokens'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/api-tokens/{tokenId:\d+}', [Web\SettingsController::class, 'revokeApiToken'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/dashboard', [Web\SettingsController::class, 'renderDashboardAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/locations', [Web\SettingsController::class, 'renderLocationsAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/security', [Web\SettingsController::class, 'renderSecurityAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/data', [Web\SettingsController::class, 'renderDataAccountPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/server/general', [Web\SettingsController::class, 'renderServerGeneralPage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('GET', '/settings/server/job-queue', [Web\SettingsController::class, 'renderServerJobQueuePage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('GET', '/settings/server/job-schedules', [Web\SettingsController::class, 'renderServerJobSchedulesPage'], [
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
    $routes->add('GET', '/settings/server/password-resets', [Web\SettingsController::class, 'renderServerPasswordResetsPage'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
        Web\Middleware\PasswordResetIsAvailable::class,
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
    $routes->add('PUT', '/settings/account/security', [Web\UserController::class, 'updateOwnCredentials'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/account/email', [Web\UserController::class, 'updateOwnEmail'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/email-change/resend', [Web\UserController::class, 'resendOwnPendingEmailVerification'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/email-change', [Web\UserController::class, 'cancelOwnPendingEmailChange'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/update-password', [Web\SettingsController::class, 'updatePassword'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/create-totp-uri', [
        Web\TwoFactorAuthenticationController::class,
        'createTotpUri'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/disable-totp', [Web\TwoFactorAuthenticationController::class, 'disableTotp'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/security/enable-totp', [Web\TwoFactorAuthenticationController::class, 'enableTotp'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/account/export/csv/{exportType:.+}', [Web\ExportController::class, 'getCsvExport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/import/csv/{exportType:.+}', [Web\ImportController::class, 'handleCsvImport'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/delete-ratings', [Web\SettingsController::class, 'deleteRatings'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/delete-reviews', [Web\SettingsController::class, 'deleteReviews'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/delete-history', [Web\SettingsController::class, 'deleteHistory'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/account/delete-account', [Web\SettingsController::class, 'deleteAccount'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/update-dashboard-rows', [Web\SettingsController::class, 'updateDashboardRows'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/account/reset-dashboard-rows', [Web\SettingsController::class, 'resetDashboardRows'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/trakt', [Web\SettingsController::class, 'renderTraktPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/trakt', [Web\SettingsController::class, 'updateTrakt'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/trakt/verify-credentials', [Web\SettingsController::class, 'traktVerifyCredentials'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/letterboxd', [Web\SettingsController::class, 'renderLetterboxdPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/letterboxd-export', [Web\SettingsController::class, 'generateLetterboxdExportData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/plex', [Web\SettingsController::class, 'renderPlexPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/plex/logout', [Web\PlexController::class, 'removePlexAccessTokens'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex/server-url-save', [Web\PlexController::class, 'savePlexServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex/server-url-verify', [Web\PlexController::class, 'verifyPlexServerUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/plex/authentication-url', [Web\PlexController::class, 'generatePlexAuthenticationUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/plex/callback', [Web\PlexController::class, 'processPlexCallback'], [Web\Middleware\UserIsAuthenticated::class], csrfProtectionEnabled: false);
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
    $routes->add('PUT', '/settings/emby/webhook', [Web\EmbyController::class, 'regenerateEmbyWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/emby/webhook', [Web\EmbyController::class, 'deleteEmbyWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/kodi', [Web\SettingsController::class, 'renderKodiPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/kodi', [Web\SettingsController::class, 'updateKodi'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('PUT', '/settings/kodi/webhook', [Web\KodiController::class, 'regenerateKodiWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/settings/kodi/webhook', [Web\KodiController::class, 'deleteKodiWebhookUrl'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/app', [Web\SettingsController::class, 'renderAppPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/netflix', [Web\SettingsController::class, 'renderNetflixPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/netflix', [Web\NetflixController::class, 'matchNetflixActivityCsvWithTmdbMovies'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/netflix/import', [Web\NetflixController::class, 'importNetflixData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/integrations/mastodon', [Web\SettingsController::class, 'renderMastodonPage'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/settings/integrations/mastodon', [Web\SettingsController::class, 'updateMastodon'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/settings/users', [Web\UserController::class, 'fetchUsers'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/users', [Web\UserController::class, 'createUser'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('PUT', '/settings/users/{userId:\d+}', [Web\UserController::class, 'updateUser'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('DELETE', '/settings/users/{userId:\d+}', [Web\UserController::class, 'deleteUser'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class
    ]);
    $routes->add('POST', '/settings/users/{userId:\d+}/email-change/resend', [Web\UserController::class, 'resendPendingEmailVerification'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('DELETE', '/settings/users/{userId:\d+}/email-change', [Web\UserController::class, 'cancelPendingEmailChange'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('POST', '/settings/users/{userId:\d+}/email-change/approve', [Web\UserController::class, 'approvePendingEmailChange'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('GET', '/settings/password-resets', [Web\PasswordResetAdminController::class, 'fetchPendingResets'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('DELETE', '/settings/password-resets', [Web\PasswordResetAdminController::class, 'revokeAllResets'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('POST', '/settings/users/{userId:\d+}/password-reset', [Web\PasswordResetAdminController::class, 'createReset'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);
    $routes->add('DELETE', '/settings/users/{userId:\d+}/password-reset', [Web\PasswordResetAdminController::class, 'revokeReset'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
        Web\Middleware\PasswordResetIsAvailable::class,
    ]);

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
    $routes->add('GET', '/movies/search', [Web\MovieSearchController::class, 'search'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/movies/{id:[0-9]+}/refresh-tmdb', [Web\Movie\MovieController::class, 'refreshTmdbData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/movies/{id:[0-9]+}/refresh-imdb', [Web\Movie\MovieController::class, 'refreshImdbRating'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/movies/{id:[0-9]+}/watch-providers', [Web\Movie\MovieWatchProviderController::class, 'getWatchProviders']);
    $routes->add('POST', '/movies/{id:[0-9]+}/add-watchlist', [Web\Movie\MovieWatchlistController::class, 'addToWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/movies/{id:[0-9]+}/remove-watchlist', [Web\Movie\MovieWatchlistController::class, 'removeFromWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/movies/{id:[0-9]+}/posters', [Web\Movie\MoviePosterController::class, 'fetchPosters'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('PUT', '/movies/{id:[0-9]+}/poster', [Web\Movie\MoviePosterController::class, 'selectPoster'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);
    $routes->add('DELETE', '/movies/{id:[0-9]+}/poster', [Web\Movie\MoviePosterController::class, 'restoreDefault'], [
        Web\Middleware\UserIsAuthenticated::class,
        Web\Middleware\UserIsAdmin::class,
    ]);

    ##########
    # Person #
    ##########
    $routes->add('POST', '/persons/{id:[0-9]+}/refresh-tmdb', [Web\PersonController::class, 'refreshTmdbData'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/persons/{id:[0-9]+}/hide-in-top-lists', [Web\PersonController::class, 'hideInTopLists'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/persons/{id:[0-9]+}/show-in-top-lists', [Web\PersonController::class, 'showInTopLists'], [Web\Middleware\UserIsAuthenticated::class]);

    ##############
    # User media #
    ##############
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard', [Web\DashboardController::class, 'redirectToDashboard'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/dashboard/rows/{rowId:\d+}', [Web\DashboardController::class, 'renderRow'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}', [Web\DashboardController::class, 'render'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/history', [Web\HistoryController::class, 'renderHistory'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/watchlist', [Web\WatchlistController::class, 'renderWatchlist'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/movies', [Web\MoviesController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/actors', [Web\ActorsController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/directors', [Web\DirectorsController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class]);
    // the following routes (/movies/ and /persons/) can have any non-slash characters following the URL after a -
    //   e.g., http://movary.test/users/alifeee/movies/14-freakier-friday which is identical to
    //         http://movary.test/users/alifeee/movies/14
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}[-{nameSlugSuffix:[^/]*}]', [Web\Movie\MovieController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class, Web\Middleware\MovieSlugRedirector::class]);
    $routes->add('GET', '/users/{username:[a-zA-Z0-9]+}/persons/{id:\d+}[-{nameSlugSuffix:[^/]*}]', [Web\PersonController::class, 'renderPage'], [Web\Middleware\IsAuthorizedToReadUserData::class, Web\Middleware\PersonSlugRedirector::class]);
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
    $routes->add('POST', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/review', [
        Web\Movie\MovieReviewController::class,
        'save'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('DELETE', '/users/{username:[a-zA-Z0-9]+}/movies/{id:\d+}/review', [
        Web\Movie\MovieReviewController::class,
        'delete'
    ], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/log-movie', [Web\HistoryController::class, 'logMovie'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('POST', '/add-movie-to-watchlist', [Web\WatchlistController::class, 'addMovieToWatchlist'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/fetchMovieRatingByTmdbdId', [Web\Movie\MovieRatingController::class, 'fetchMovieRatingByTmdbdId'], [Web\Middleware\UserIsAuthenticated::class]);
    $routes->add('GET', '/fetchMovieReviewByTmdbId', [Web\Movie\MovieReviewController::class, 'fetchByTmdbId'], [Web\Middleware\UserIsAuthenticated::class]);

    $routerService->addRoutesToRouteCollector($routeCollector, $routes, true);
}

function addApiRoutes(RouterService $routerService, FastRoute\RouteCollector $routeCollector) : void
{
    $routes = RouteList::create();

    $routes->add('GET', '/openapi', [Api\OpenApiController::class, 'getSchema']);
    $routes->add('GET', '/me', [Api\AuthenticationController::class, 'getTokenData']);

    $routeUserDashboardRows = '/users/{username:[a-zA-Z0-9]+}/dashboard/rows';
    $routes->add('GET', $routeUserDashboardRows, [Api\DashboardController::class, 'getRows'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('GET', $routeUserDashboardRows . '/{rowKey:[a-z0-9-]+}', [Api\DashboardController::class, 'getRow'], [Api\Middleware\IsAuthorizedToReadUserData::class]);

    $routeUserPlays = '/users/{username:[a-zA-Z0-9]+}/plays';
    $routes->add('GET', $routeUserPlays, [Api\HistoryController::class, 'getPlays'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('POST', $routeUserPlays, [Api\HistoryController::class, 'createPlay'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routeUserPlay = $routeUserPlays . '/{playId:[0-9]+}';
    $routes->add('GET', $routeUserPlay, [Api\HistoryController::class, 'getPlay'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('PUT', $routeUserPlay, [Api\HistoryController::class, 'updatePlay'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserPlay, [Api\HistoryController::class, 'deletePlay'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routeUserWatchlist = '/users/{username:[a-zA-Z0-9]+}/watchlist';
    $routes->add('GET', $routeUserWatchlist, [Api\WatchlistController::class, 'getWatchlist'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('POST', $routeUserWatchlist, [Api\WatchlistController::class, 'addToWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserWatchlist, [Api\WatchlistController::class, 'deleteFromWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routeUserWatchlistMovie = $routeUserWatchlist . '/{movaryId:[0-9]+}';
    $routes->add('PUT', $routeUserWatchlistMovie, [Api\WatchlistController::class, 'addMovieToWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserWatchlistMovie, [Api\WatchlistController::class, 'deleteMovieFromWatchlist'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routeUserPlayMovies = $routeUserPlays . '/movies';
    $routes->add('GET', $routeUserPlayMovies, [Api\PlayedController::class, 'getPlayed'], [Api\Middleware\IsAuthorizedToReadUserData::class]);

    $routeUserMovieReview = '/users/{username:[a-zA-Z0-9]+}/movies/{movaryId:[0-9]+}/review';
    $routes->add('GET', $routeUserMovieReview, [Api\MovieReviewController::class, 'getReview'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('PUT', $routeUserMovieReview, [Api\MovieReviewController::class, 'saveReview'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserMovieReview, [Api\MovieReviewController::class, 'deleteReview'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routeUserMovieRating = '/users/{username:[a-zA-Z0-9]+}/movies/{movaryId:[0-9]+}/rating';
    $routes->add('GET', $routeUserMovieRating, [Api\MovieRatingController::class, 'getRating'], [Api\Middleware\IsAuthorizedToReadUserData::class]);
    $routes->add('PUT', $routeUserMovieRating, [Api\MovieRatingController::class, 'saveRating'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);
    $routes->add('DELETE', $routeUserMovieRating, [Api\MovieRatingController::class, 'deleteRating'], [Api\Middleware\IsAuthorizedToWriteUserData::class]);

    $routes->add('POST', '/movies/add', [Api\MovieAddController::class, 'addMovie'], [Api\Middleware\IsAuthenticated::class]);
    $routes->add('GET', '/movies/search', [Api\MovieSearchController::class, 'search'], [Api\Middleware\IsAuthenticated::class]);
    $routes->add('GET', '/movies/{movaryId:[0-9]+}/posters', [Api\MoviePosterController::class, 'fetchPosters'], [
        Api\Middleware\IsAuthenticated::class,
        Api\Middleware\IsAdmin::class,
    ]);
    $routes->add('PUT', '/movies/{movaryId:[0-9]+}/poster', [Api\MoviePosterController::class, 'selectPoster'], [
        Api\Middleware\IsAuthenticated::class,
        Api\Middleware\IsAdmin::class,
    ]);
    $routes->add('DELETE', '/movies/{movaryId:[0-9]+}/poster', [Api\MoviePosterController::class, 'restoreDefault'], [
        Api\Middleware\IsAuthenticated::class,
        Api\Middleware\IsAdmin::class,
    ]);

    $routerService->addRoutesToRouteCollector($routeCollector, $routes);
}

function addIntegrationRoutes(
    RouterService $routerService,
    FastRoute\RouteCollector $routeCollector,
    string $webhookPrefix,
    string $feedPrefix,
) : void {
    $routes = RouteList::create();

    $routes->add('POST', $webhookPrefix . '/plex/{token:.+}', [Api\PlexController::class, 'handlePlexWebhook']);
    $routes->add('POST', $webhookPrefix . '/jellyfin/{token:.+}', [Api\JellyfinController::class, 'handleJellyfinWebhook']);
    $routes->add('POST', $webhookPrefix . '/emby/{token:.+}', [Api\EmbyController::class, 'handleEmbyWebhook']);
    $routes->add('POST', $webhookPrefix . '/kodi/{token:.+}', [Api\KodiController::class, 'handleKodiWebhook']);
    $routes->add('GET', $feedPrefix . '/radarr/{token:.+}', [Api\RadarrController::class, 'renderRadarrFeed']);

    $routerService->addRoutesToRouteCollector($routeCollector, $routes);
}
