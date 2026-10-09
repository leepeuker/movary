<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Api\Github\GithubApi;
use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Api\Plex\PlexApi;
use Movary\Api\Trakt\TraktApi;
use Movary\Domain\Country\CountryApi;
use Movary\Domain\Movie;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\HttpController\Web\Mapper\JobQueueFilterRequestMapper;
use Movary\HttpController\Web\Mapper\PaginationRequestMapper;
use Movary\HttpController\Web\Mapper\UserFilterRequestMapper;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\EmailSupport;
use Movary\Service\Email\InvalidSmtpConfigException;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\Service\Email\TestEmailRenderer;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\Letterboxd\LetterboxdExporter;
use Movary\Service\PaginationElementsCalculator;
use Movary\Service\Radarr\RadarrFeedUrlGenerator;
use Movary\Service\ServerSettings;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;
use Twig\Environment;

class SettingsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationService,
        private readonly UserApi $userApi,
        private readonly Movie\MovieApi $movieApi,
        private readonly GithubApi $githubApi,
        private readonly PlexApi $plexApi,
        private readonly FlashMessageService $flashMessageService,
        private readonly LetterboxdExporter $letterboxdExporter,
        private readonly TraktApi $traktApi,
        private readonly JellyfinApi $jellyfinApi,
        private readonly ServerSettings $serverSettings,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
        private readonly JobQueueApi $jobQueueApi,
        private readonly DashboardFactory $dashboardFactory,
        private readonly EmailService $emailService,
        private readonly TestEmailRenderer $testEmailRenderer,
        private readonly SmtpConfigFactory $smtpConfigFactory,
        private readonly EmailSupport $emailSupport,
        private readonly PasswordResetTokenService $passwordResetTokenService,
        private readonly CountryApi $countryApi,
        private readonly RadarrFeedUrlGenerator $radarrFeedUrlGenerator,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly PaginationRequestMapper $paginationRequestMapper,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly JobQueueFilterRequestMapper $jobQueueFilterRequestMapper,
        private readonly Movie\History\Location\MovieHistoryLocationApi $locationApi,
        private readonly UserFilterRequestMapper $userFilterRequestMapper,
    ) {
    }

    public function deleteAccount() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $user = $this->userApi->fetchUser($userId);

        if ($user->hasCoreAccountChangesDisabled() === true) {
            throw new RuntimeException('Account deletion is disabled for user: ' . $userId);
        }

        $this->userApi->deleteUser($userId);

        $this->authenticationService->logout();

        return Response::create(StatusCode::createNoContent());
    }

    public function deleteApiToken() : Response
    {
        $this->userApi->deleteApiToken($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function deleteHistory() : Response
    {
        $this->movieApi->deleteHistoryByUserId($this->authenticationService->getCurrentUserId());

        return Response::create(StatusCode::createNoContent());
    }

    public function deleteRatings() : Response
    {
        $this->movieApi->deleteRatingsByUserId($this->authenticationService->getCurrentUserId());

        return Response::create(StatusCode::createNoContent());
    }

    public function generateLetterboxdExportData() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        return Response::createZipDownload(
            $this->letterboxdExporter->generateZip($userId),
            'export-for-letterboxd.zip',
        );
    }

    public function getApiToken() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        return Response::createJson(Json::encode(['token' => $this->userApi->findApiTokenByUserId($userId)]));
    }

    public function regenerateApiToken() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $this->userApi->deleteApiToken($userId);
        $this->userApi->generateApiToken($userId);

        return Response::createJson(Json::encode(['token' => $this->userApi->findApiTokenByUserId($userId)]));
    }

    public function renderAppPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-app.html.twig', [
                'currentApplicationVersion' => $this->serverSettings->getApplicationVersion(),
                'latestRelease' => $this->githubApi->fetchLatestMovaryRelease(),
            ]),
        );
    }

    public function renderDashboardAccountPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($user);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-dashboard.html.twig', [
                'dashboardRows' => $dashboardRows,
                'dashboardRowsSuccessfullyReset' => $this->flashMessageService->consume(
                    FlashMessage::DASHBOARD_ROWS_RESET,
                ),
            ]),
        );
    }

    public function renderDataAccountPage() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $importHistoryError = match (true) {
            $this->flashMessageService->consume(FlashMessage::IMPORT_HISTORY_FAILED) => 'history',
            $this->flashMessageService->consume(FlashMessage::IMPORT_RATINGS_FAILED) => 'ratings',
            $this->flashMessageService->consume(FlashMessage::IMPORT_WATCHLIST_FAILED) => 'watchlist',
            default => null,
        };

        $user = $this->userApi->fetchUser($userId);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-data.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'importHistorySuccessful' => $this->flashMessageService->consume(
                    FlashMessage::IMPORT_HISTORY_SUCCESSFUL,
                ),
                'importRatingsSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::IMPORT_RATINGS_SUCCESSFUL,
                ),
                'importWatchlistSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::IMPORT_WATCHLIST_SUCCESSFUL,
                ),
                'importHistoryError' => $importHistoryError,
            ]),
        );
    }

    public function renderEmbyPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $hasApplicationUrl = $this->applicationUrlService->hasApplicationUrl();
        $webhookId = $user->getEmbyWebhookId();

        if ($hasApplicationUrl === true && $webhookId !== null) {
            $webhookUrl = $this->webhookUrlBuilder->buildEmbyWebhookUrl($webhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-emby.html.twig', [
                'isActive' => $hasApplicationUrl,
                'embyWebhookUrl' => $webhookUrl ?? '-',
                'scrobbleWatches' => $user->hasEmbyScrobbleWatchesEnabled(),
            ]),
        );
    }

    public function renderGeneralAccountPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-general.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'dateFormats' => DateFormat::getFormats(),
                'dateFormatSelected' => $user->getDateFormatId(),
                'privacyLevel' => $user->getPrivacyLevel(),
                'username' => $user->getName(),
                'enableAutomaticWatchlistRemoval' => $user->hasWatchlistAutomaticRemovalEnabled(),
                'countries' => $this->countryApi->getIso31661ToNameMap(),
                'userCountry' => $user->getCountry(),
                'apiToken' => $this->userApi->findApiTokenByUserId($user->getId()),
                'displayCharacterNamesInput' => $user->getDisplayCharacterNames(),
                'displayTmdbRatingsInput' => $user->getDisplayTmdbRating(),
                'displayImdbRatingsInput' => $user->getDisplayImdbRating(),
            ]),
        );
    }

    public function renderJellyfinPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $hasApplicationUrl = $this->applicationUrlService->hasApplicationUrl();
        $webhookId = $user->getJellyfinWebhookId();

        $jellyfinDeviceId = $this->serverSettings->getJellyfinDeviceId();
        $jellyfinServerUrl = $this->userApi->findJellyfinServerUrl($user->getId());
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($user->getId());
        $jellyfinUsername = null;

        if ($jellyfinDeviceId !== null && $jellyfinServerUrl !== null && $jellyfinAuthentication !== null) {
            $jellyfinUsername = $this->jellyfinApi->findJellyfinUser($jellyfinAuthentication);
        }

        if ($hasApplicationUrl === true && $webhookId !== null) {
            $webhookUrl = $this->webhookUrlBuilder->buildJellyfinWebhookUrl($webhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-jellyfin.html.twig', [
                'isActive' => $hasApplicationUrl,
                'jellyfinWebhookUrl' => $webhookUrl ?? '-',
                'jellyfinServerUrl' => $jellyfinServerUrl,
                'jellyfinIsAuthenticated' => $jellyfinAuthentication !== null,
                'jellyfinUsername' => $jellyfinUsername?->getUsername(),
                'jellyfinDeviceId' => $jellyfinDeviceId,
                'scrobbleWatches' => $user->hasJellyfinScrobbleWatchesEnabled(),
                'jellyfinSyncEnabled' => $user->hasJellyfinSyncEnabled(),
            ]),
        );
    }

    public function renderKodiPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $hasApplicationUrl = $this->applicationUrlService->hasApplicationUrl();
        $webhookId = $user->getKodiWebhookId();

        if ($hasApplicationUrl === true && $webhookId !== null) {
            $webhookUrl = $this->webhookUrlBuilder->buildKodiWebhookUrl($webhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-kodi.html.twig', [
                'isActive' => $hasApplicationUrl,
                'kodiWebhookUrl' => $webhookUrl ?? '-',
                'scrobbleWatches' => $user->hasKodiScrobbleWatchesEnabled(),
            ]),
        );
    }

    public function renderLetterboxdPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-letterboxd.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'letterboxdDiarySyncSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::LETTERBOXD_DIARY_SYNC_SCHEDULED,
                ),
                'letterboxdRatingsSyncSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::LETTERBOXD_RATINGS_SYNC_SCHEDULED,
                ),
                'letterboxdRatingsImportFileInvalid' => $this->flashMessageService->consume(
                    FlashMessage::LETTERBOXD_RATINGS_FILE_INVALID,
                ),
                'letterboxdDiaryImportFileInvalid' => $this->flashMessageService->consume(
                    FlashMessage::LETTERBOXD_DIARY_FILE_INVALID,
                ),
            ]),
        );
    }

    public function renderLocationsAccountPage(Request $request) : Response
    {
        $user = $this->authenticationService->getCurrentUser();
        $paginationRequest = $this->paginationRequestMapper->map($request, 20, [20, 50, 100, 250]);
        $locationsEnabled = $user->hasLocationsEnabled();
        $locationCount = $locationsEnabled ? $this->locationApi->countLocationsByUserId($user->getId()) : 0;
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $locationCount,
            $paginationRequest->getPerPage(),
            $paginationRequest->getPage(),
        );
        $locations = $locationsEnabled
            ? $this->locationApi->findLocationsByUserIdPaginated(
                $user->getId(),
                $paginationRequest->getPerPage(),
                $paginationElements->getOffset(),
            )
            : Movie\History\Location\MovieHistoryLocationEntityList::create();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-locations.html.twig', [
                'locationsEnabled' => $locationsEnabled,
                'locations' => $locations,
                'locationsPerPage' => $paginationRequest->getPerPage(),
                'paginationElements' => $paginationElements,
                'paginationQuery' => ['perPage' => $paginationRequest->getPerPage()],
            ]),
        );
    }

    public function renderMastodonPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/settings-integration-mastodon.html.twig',
                [
                    'mastodonCredentialsUpdated' => $this->flashMessageService->consume(
                        FlashMessage::MASTODON_CREDENTIALS_UPDATED,
                    ),
                    'mastodonEnable' => $user->isMastodonEnabled(),
                    'mastodonOnByDefault' => $user->isMastodonPostAutomatic(),
                    'mastodonUsername' => $user->getMastodonUsername(),
                    'mastodonVisibility' => $user->getMastodonPostVisibility(),
                    'mastodonAccessToken' => $user->getMastodonAccessToken(),
                ],
            ),
        );
    }

    public function renderNetflixPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-netflix.html.twig'),
        );
    }

    public function renderPlexPage() : Response
    {
        $plexAccessToken = null;
        $plexIdentifier = $this->serverSettings->getPlexIdentifier();

        if ($plexIdentifier !== null) {
            $plexAccessToken = $this->userApi->findPlexAccessToken($this->authenticationService->getCurrentUserId());

            if ($plexAccessToken !== null) {
                $plexAccount = $this->plexApi->findPlexAccount($plexAccessToken);

                if ($plexAccount !== null) {
                    $plexUsername = $plexAccount->getPlexUsername();
                    $plexServerUrl = $this->userApi->findPlexServerUrl($this->authenticationService->getCurrentUserId());
                }
            }
        }

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $hasApplicationUrl = $this->applicationUrlService->hasApplicationUrl();
        $plexWebhookId = $user->getPlexWebhookId();

        if ($hasApplicationUrl === true && $plexWebhookId !== null) {
            $plexWebhookUrl = $this->webhookUrlBuilder->buildPlexWebhookUrl($plexWebhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-plex.html.twig', [
                'isActive' => $hasApplicationUrl,
                'plexWebhookUrl' => $plexWebhookUrl ?? '-',
                'scrobbleWatches' => $user->hasPlexScrobbleWatchesEnabled(),
                'scrobbleRatings' => $user->hasPlexScrobbleRatingsEnabled(),
                'plexTokenExists' => $plexAccessToken !== null,
                'plexServerUrl' => $plexServerUrl ?? '',
                'plexUsername' => $plexUsername ?? '',
                'hasServerPlexIdentifier' => $plexIdentifier !== null,
            ]),
        );
    }

    public function renderRadarrPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        $radarrFeedId = $user->getRadarrFeedId();
        $hasApplicationUrl = $this->applicationUrlService->hasApplicationUrl();

        if ($hasApplicationUrl === true && $radarrFeedId !== null) {
            $radarrFeedUrl = $this->radarrFeedUrlGenerator->generateUrl($radarrFeedId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-radarr.html.twig', [
                'radarrFeedUrl' => $radarrFeedUrl ?? '-',
                'isActive' => $hasApplicationUrl
            ]),
        );
    }

    public function renderSecurityAccountPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        $totpEnabled = $this->twoFactorAuthenticationService->findTotpUri($user->getId()) === null ? false : true;

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-security.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'totpEnabled' => $totpEnabled,
                'twoFactorAuthenticationEnabled' => $this->flashMessageService->consume(
                    FlashMessage::TWO_FACTOR_AUTHENTICATION_ENABLED,
                ),
                'twoFactorAuthenticationDisabled' => $this->flashMessageService->consume(
                    FlashMessage::TWO_FACTOR_AUTHENTICATION_DISABLED,
                ),
            ]),
        );
    }

    public function renderServerEmailPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-email.html.twig', [
                'emailEnabled' => $this->emailSupport->isEnabled(),
                'emailEnabledSetInEnv' => $this->serverSettings->isEmailEnabledSetInEnvironment(),
                'smtpConfigured' => $this->emailSupport->isSmtpConfigured(),
                'hasApplicationUrl' => $this->applicationUrlService->hasApplicationUrl(),
                'smtpHost' => $this->serverSettings->getSmtpHost(),
                'smtpHostSetInEnv' => $this->serverSettings->isSmtpHostSetInEnvironment(),
                'smtpPort' => $this->serverSettings->getSmtpPort(),
                'smtpPortSetInEnv' => $this->serverSettings->isSmtpPortSetInEnvironment(),
                'smtpFromAddress' => $this->serverSettings->getFromAddress(),
                'smtpFromAddressSetInEnv' => $this->serverSettings->isSmtpFromAddressSetInEnvironment(),
                'smtpEncryption' => $this->serverSettings->getSmtpEncryption(),
                'smtpEncryptionSetInEnv' => $this->serverSettings->isSmtpEncryptionSetInEnvironment(),
                'smtpWithAuthentication' => $this->serverSettings->getSmtpWithAuthentication(),
                'smtpWithAuthenticationSetInEnv' => $this->serverSettings->isSmtpWithAuthenticationSetInEnvironment(),
                'smtpUser' => $this->serverSettings->getSmtpUser(),
                'smtpUserSetInEnv' => $this->serverSettings->isSmtpUserSetInEnvironment(),
                'smtpPasswordConfigured' => strlen((string)$this->serverSettings->getSmtpPassword()) > 0,
                'smtpPasswordSetInEnv' => $this->serverSettings->isSmtpPasswordSetInEnvironment(),
            ]),
        );
    }

    public function renderServerGeneralPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-general.html.twig', [
                'applicationUrlRaw' => $this->serverSettings->getApplicationUrl(),
                'applicationName' => $this->serverSettings->getApplicationName(),
                'applicationTimezone' => $this->serverSettings->getApplicationTimezone(),
                'applicationTimezoneDefault' => DateTime::DEFAULT_TIME_ZONE,
                'applicationTimezonesAvailable' => timezone_identifiers_list(),
                'tmdbApiKey' => $this->serverSettings->getTmdbApiKey(),
                'tmdbApiKeySetInEnv' => $this->serverSettings->isTmdbApiKeySetInEnvironment(),
                'applicationUrlSetInEnv' => $this->serverSettings->isApplicationUrlSetInEnvironment(),
                'applicationNameSetInEnv' => $this->serverSettings->isApplicationNameSetInEnvironment(),
                'applicationTimezoneSetInEnv' => $this->serverSettings->isApplicationTimezoneSetInEnvironment(),
            ]),
        );
    }

    public function renderServerJobsPage(Request $request) : Response
    {
        $paginationRequest = $this->paginationRequestMapper->map($request, 20, [20, 50, 100, 250], ['jpp']);
        $jobFilter = $this->jobQueueFilterRequestMapper->map($request);
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $this->jobQueueApi->countJobs($jobFilter),
            $paginationRequest->getPerPage(),
            $paginationRequest->getPage(),
        );

        $jobs = $this->jobQueueApi->fetchJobsForStatusPage(
            $paginationRequest->getPerPage(),
            $paginationElements->getOffset(),
            $jobFilter,
        );

        $paginationQuery = ['perPage' => $paginationRequest->getPerPage()];
        if ($jobFilter->isWithoutUser() === true) {
            $paginationQuery['user'] = 'none';
        } elseif ($jobFilter->getUserId() !== null) {
            $paginationQuery['user'] = $jobFilter->getUserId();
        }
        if ($jobFilter->getType() !== null) {
            $paginationQuery['type'] = (string)$jobFilter->getType();
        }
        if ($jobFilter->getStatus() !== null) {
            $paginationQuery['status'] = (string)$jobFilter->getStatus();
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/settings-server-jobs.html.twig',
                [
                    'jobs' => $jobs,
                    'jobsPerPage' => $paginationRequest->getPerPage(),
                    'paginationElements' => $paginationElements,
                    'paginationQuery' => $paginationQuery,
                    'jobFilterUser' => $jobFilter->isWithoutUser() ? 'none' : $jobFilter->getUserId(),
                    'jobFilterType' => $jobFilter->getType(),
                    'jobFilterStatus' => $jobFilter->getStatus(),
                    'jobFiltersActive' => $jobFilter->hasFilters(),
                    'jobTypes' => JobType::getSupportedTypes(),
                    'jobStatuses' => JobStatus::getSupportedStatuses(),
                    'users' => $this->userApi->fetchAll(),
                ],
            ),
        );
    }

    public function renderServerUsersPage(Request $request) : Response
    {
        $paginationRequest = $this->paginationRequestMapper->map($request, 20, [20, 50, 100, 250]);
        $isAdminFilter = $this->userFilterRequestMapper->map($request);
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $this->userApi->countUsers($isAdminFilter),
            $paginationRequest->getPerPage(),
            $paginationRequest->getPage(),
        );
        $paginationQuery = ['perPage' => $paginationRequest->getPerPage()];
        if ($isAdminFilter !== null) {
            $paginationQuery['role'] = $isAdminFilter ? 'admin' : 'user';
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-users.html.twig', [
                'users' => $this->userApi->fetchAllPaginated(
                    $paginationRequest->getPerPage(),
                    $paginationElements->getOffset(),
                    $isAdminFilter,
                ),
                'usersPerPage' => $paginationRequest->getPerPage(),
                'paginationElements' => $paginationElements,
                'paginationQuery' => $paginationQuery,
                'userFilterRole' => $isAdminFilter === null
                    ? null
                    : ($isAdminFilter ? 'admin' : 'user'),
                'userFiltersActive' => $isAdminFilter !== null,
            ]),
        );
    }

    public function renderServerPasswordResetsPage(Request $request) : Response
    {
        $paginationRequest = $this->paginationRequestMapper->map($request, 20, [20, 50, 100, 250]);
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $this->passwordResetTokenService->countPendingTokens(),
            $paginationRequest->getPerPage(),
            $paginationRequest->getPage(),
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-password-resets.html.twig', [
                'passwordResets' => $this->passwordResetTokenService->fetchPendingTokensPaginated(
                    $paginationRequest->getPerPage(),
                    $paginationElements->getOffset(),
                ),
                'passwordResetsPerPage' => $paginationRequest->getPerPage(),
                'paginationElements' => $paginationElements,
                'paginationQuery' => ['perPage' => $paginationRequest->getPerPage()],
            ]),
        );
    }

    public function renderTraktPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-trakt.html.twig', [
                'traktClientId' => $user->getTraktClientId(),
                'traktUserName' => $user->getTraktUserName(),
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'traktCredentialsUpdated' => $this->flashMessageService->consume(
                    FlashMessage::TRAKT_CREDENTIALS_UPDATED,
                ),
                'traktScheduleHistorySyncSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED,
                ),
                'traktScheduleRatingsSyncSuccessful' => $this->flashMessageService->consume(
                    FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED,
                ),
            ]),
        );
    }

    public function resetDashboardRows() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $this->userApi->updateVisibleDashboardRows($userId, null);
        $this->userApi->updateExtendedDashboardRows($userId, null);
        $this->userApi->updateOrderDashboardRows($userId, null);

        $this->flashMessageService->add(FlashMessage::DASHBOARD_ROWS_RESET);

        return Response::createOk();
    }

    public function sendTestEmail(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());
        if ($this->resolveEmailEnabled($requestData) === false) {
            return Response::createBadRequest('Email support is disabled.');
        }
        $recipient = (string)($requestData['recipient'] ?? '');

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return Response::createBadRequest('Recipient must be a valid email address.');
        }

        try {
            $smtpConfig = $this->smtpConfigFactory->create($requestData);
            $this->emailService->sendEmail(
                $recipient,
                'Movary: Test Email',
                $this->testEmailRenderer->render(),
                $smtpConfig,
            );
        } catch (InvalidSmtpConfigException|CannotSendEmailException $e) {
            return Response::createBadRequest($e->getMessage());
        }

        return Response::createOk();
    }

    public function traktVerifyCredentials(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());

        $clientId = $requestData['clientId'] ?? '';
        $username = $requestData['username'] ?? '';

        if ($this->traktApi->verifyCredentials($clientId, $username) === false) {
            return Response::createBadRequest();
        }

        return Response::createOk();
    }

    public function updateDashboardRows(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $bodyData = Json::decode($request->getBody());

        $visibleRows = $bodyData['visibleRows'];
        $extendedRows = $bodyData['extendedRows'];
        $orderRows = $bodyData['orderRows'];

        $visibleRowsString = implode(';', $visibleRows);
        $extendedRowsString = implode(';', $extendedRows);
        $orderRowsString = implode(';', $orderRows);

        $this->userApi->updateVisibleDashboardRows($userId, $visibleRowsString);
        $this->userApi->updateExtendedDashboardRows($userId, $extendedRowsString);
        $this->userApi->updateOrderDashboardRows($userId, $orderRowsString);

        return Response::createOk();
    }

    public function updateEmby(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $postParameters = Json::decode($request->getBody());

        $scrobbleWatches = (bool)$postParameters['scrobbleWatches'];

        $this->userApi->updateEmbyScrobblerOptions($userId, $scrobbleWatches);

        return Response::create(StatusCode::createNoContent());
    }

    public function updateGeneral(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());

        $privacyLevel = isset($requestData['privacyLevel']) === false ? 1 : (int)$requestData['privacyLevel'];
        $dateFormat = empty($requestData['dateFormat']) === true ? 0 : (int)$requestData['dateFormat'];
        $name = $requestData['username'] ?? '';
        $country = $requestData['country'] ?? null;
        $enableAutomaticWatchlistRemoval = isset($requestData['enableAutomaticWatchlistRemoval']) === false ? false : (bool)$requestData['enableAutomaticWatchlistRemoval'];
        $displayCharacterNames = isset($requestData['displayCharacterNames']) === false ? false : (bool)$requestData['displayCharacterNames'];
        $displayTmdbRatings = isset($requestData['displayTmdbRatings']) === false ? false : (bool)$requestData['displayTmdbRatings'];
        $displayImdbRatings = isset($requestData['displayImdbRatings']) === false ? false : (bool)$requestData['displayImdbRatings'];

        $userId = $this->authenticationService->getCurrentUserId();

        try {
            $this->userApi->updatePrivacyLevel($userId, $privacyLevel);
            $this->userApi->updateDateFormatId($userId, $dateFormat);
            $this->userApi->updateCountry($userId, $country);
            $this->userApi->updateName($userId, (string)$name);
            $this->userApi->updateWatchlistAutomaticRemovalEnabled($userId, $enableAutomaticWatchlistRemoval);
            $this->userApi->updateDisplayCharacterNames($userId, $displayCharacterNames);
            $this->userApi->updateDisplayTmdbRating($userId, $displayTmdbRatings);
            $this->userApi->updateDisplayImdbRating($userId, $displayImdbRatings);
        } catch (User\Exception\UsernameInvalidFormat) {
            return Response::createBadRequest('Username not meeting requirements');
        } catch (User\Exception\UsernameNotUnique) {
            return Response::createBadRequest('Username is not unique');
        }

        return Response::createOk();
    }

    public function updateJellyfin(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $postParameters = Json::decode($request->getBody());

        $scrobbleWatches = (bool)$postParameters['scrobbleWatches'];

        $this->userApi->updateJellyfinScrobblerOptions($userId, $scrobbleWatches);

        return Response::create(StatusCode::createNoContent());
    }

    public function updateKodi(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $postParameters = Json::decode($request->getBody());

        $scrobbleWatches = (bool)$postParameters['scrobbleWatches'];

        $this->userApi->updateKodiScrobblerOptions($userId, $scrobbleWatches);

        return Response::create(StatusCode::createNoContent());
    }

    public function updateMastodon(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = $request->getPostParameters();

        $mastodonEnable = $postParameters['mastodonEnable'];
        $mastodonEnable = $mastodonEnable == 'on';

        $mastodonOnByDefault = $postParameters['mastodonOnByDefault'];
        $mastodonOnByDefault = $mastodonOnByDefault == 'on';

        $mastodonUsername = trim($postParameters['mastodonUsername'] ?? '');

        $mastodonAccessToken = trim($postParameters['mastodonAccessToken'] ?? '');

        $mastodonPostVisibility = $postParameters['mastodonVisibility'];
        if (!in_array($mastodonPostVisibility, ['public', 'unlisted', 'private', 'direct'])) {
            $mastodonPostVisibility = 'public';
        }

        $this->userApi->updateMastodonPostEnabled($userId, $mastodonEnable);
        $this->userApi->updateMastodonUsername($userId, $mastodonUsername);
        $this->userApi->updateMastodonAccessToken($userId, $mastodonAccessToken);
        $this->userApi->updateMastodonPostAutomatic($userId, $mastodonOnByDefault);
        $this->userApi->updateMastodonPostVisibility($userId, $mastodonPostVisibility);

        $this->flashMessageService->add(FlashMessage::MASTODON_CREDENTIALS_UPDATED);

        $redirectUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/settings/integrations/mastodon')
        );

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($redirectUrl)],
        );
    }

    public function updatePassword(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $user = $this->userApi->fetchUser($userId);

        $responseData = Json::decode($request->getBody());

        $newPassword = $responseData['newPassword'];
        $currentPassword = $responseData['currentPassword'];

        if ($this->userApi->isValidPassword($userId, $currentPassword) === false) {
            return Response::createBadRequest('Current password wrong'); // Error message is referenced in JS!!!
        }

        if (strlen($newPassword) < 8) {
            return Response::createBadRequest('New password not meeting requirements'); // Error message is referenced in JS!!!
        }

        if ($user->hasCoreAccountChangesDisabled() === true) {
            return Response::createForbidden();
        }

        $this->userApi->updatePassword($userId, $newPassword);
        $this->authenticationService->logout();

        return Response::create(StatusCode::createOk());
    }

    public function updatePlex(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = Json::decode($request->getBody());

        $scrobbleWatches = (bool)$postParameters['scrobbleWatches'];
        $scrobbleRatings = (bool)$postParameters['scrobbleRatings'];

        $this->userApi->updatePlexScrobblerOptions($userId, $scrobbleWatches, $scrobbleRatings);

        return Response::create(StatusCode::createNoContent());
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function updateServerEmail(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());
        $emailEnabled = $this->resolveEmailEnabled($requestData);

        if ($emailEnabled === false) {
            $this->serverSettings->setEmailEnabled(false);
            $this->passwordResetTokenService->deleteAllTokens();

            return Response::createOk();
        }

        try {
            $this->smtpConfigFactory->create($requestData);
        } catch (InvalidSmtpConfigException $e) {
            return Response::createBadRequest($e->getMessage());
        }

        $smtpHost = isset($requestData['smtpHost']) === false ? null : $requestData['smtpHost'];
        $smtpPort = isset($requestData['smtpPort']) === false ? null : $requestData['smtpPort'];
        $smtpFromAddress = isset($requestData['smtpFromAddress']) === false ? null : $requestData['smtpFromAddress'];
        $smtpEncryption = isset($requestData['smtpEncryption']) === false ? null : $requestData['smtpEncryption'];
        $smtpWithAuthentication = isset($requestData['smtpWithAuthentication']) === false ? null : (bool)$requestData['smtpWithAuthentication'];
        $smtpUser = isset($requestData['smtpUser']) === false ? null : $requestData['smtpUser'];
        $smtpPassword = isset($requestData['smtpPassword']) === false ? null : $requestData['smtpPassword'];

        if ($smtpHost !== null) {
            $this->serverSettings->setSmtpHost($smtpHost);
        }
        if ($smtpPort !== null) {
            $this->serverSettings->setSmtpPort((int)$smtpPort);
        }
        if ($smtpFromAddress !== null) {
            $this->serverSettings->setSmtpFromAddress($smtpFromAddress);
        }
        if ($smtpEncryption !== null) {
            $this->serverSettings->setSmtpEncryption($smtpEncryption);
        }
        if ($smtpWithAuthentication !== null) {
            $this->serverSettings->setSmtpFromWithAuthentication($smtpWithAuthentication);
        }

        $smtpAuthenticationEnabled = $this->serverSettings->getSmtpWithAuthentication() === true;
        if ($smtpWithAuthentication === false
            && $this->serverSettings->isSmtpWithAuthenticationSetInEnvironment() === false
        ) {
            $this->serverSettings->clearSmtpAuthenticationCredentials();
        } elseif ($smtpAuthenticationEnabled === true) {
            if ($smtpUser !== null) {
                $this->serverSettings->setSmtpUser($smtpUser);
            }
            if ($smtpPassword !== null) {
                $this->serverSettings->setSmtpPassword($smtpPassword);
            }
        }

        $this->serverSettings->setEmailEnabled(true);

        return Response::createOk();
    }

    public function updateServerGeneral(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());

        $tmdbApiKey = isset($requestData['tmdbApiKey']) === false ? null : $requestData['tmdbApiKey'];
        $applicationUrl = isset($requestData['applicationUrl']) === false ? null : $requestData['applicationUrl'];
        $applicationName = isset($requestData['applicationName']) === false ? null : $requestData['applicationName'];
        $applicationTimezone = isset($requestData['applicationTimezone']) === false ? null : $requestData['applicationTimezone'];

        if ($tmdbApiKey !== null) {
            $this->serverSettings->setTmdbApiKey($tmdbApiKey);
        }
        if ($applicationUrl !== null) {
            $this->serverSettings->setApplicationUrl($applicationUrl);
        }
        if ($applicationName !== null) {
            $this->serverSettings->setApplicationName($applicationName);
        }
        if ($applicationTimezone !== null) {
            $this->serverSettings->setApplicationTimezone($applicationTimezone);
        }

        return Response::createOk();
    }

    public function updateTrakt(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = $request->getPostParameters();

        $traktClientId = $postParameters['traktClientId'];
        if (empty($traktClientId) === true) {
            $traktClientId = null;
        }

        $traktUserName = $postParameters['traktUserName'];
        if (empty($traktUserName) === true) {
            $traktUserName = null;
        }

        $this->userApi->updateTraktClientId($userId, $traktClientId);
        $this->userApi->updateTraktUserName($userId, $traktUserName);

        $this->flashMessageService->add(FlashMessage::TRAKT_CREDENTIALS_UPDATED);

        $redirectUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/settings/integrations/trakt')
        );

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($redirectUrl)],
        );
    }

    /** @param array<string, mixed> $requestData */
    private function resolveEmailEnabled(array $requestData) : bool
    {
        if ($this->serverSettings->isEmailEnabledSetInEnvironment() === true) {
            return $this->serverSettings->isEmailEnabled();
        }

        return array_key_exists('emailEnabled', $requestData)
            ? (bool)$requestData['emailEnabled']
            : $this->serverSettings->isEmailEnabled();
    }
}
