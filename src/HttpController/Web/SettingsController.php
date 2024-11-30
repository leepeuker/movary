<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Api\Github\GithubApi;
use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Api\Plex\PlexApi;
use Movary\Api\Tmdb\Cache\TmdbIsoCountryCache;
use Movary\Api\Trakt\TraktApi;
use Movary\Domain\Movie;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\SmtpConfig;
use Movary\Service\Letterboxd\LetterboxdExporter;
use Movary\Service\ServerSettings;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
use Twig\Environment;
use ZipStream;

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
        private readonly SessionWrapper $sessionWrapper,
        private readonly LetterboxdExporter $letterboxdExporter,
        private readonly TraktApi $traktApi,
        private readonly JellyfinApi $jellyfinApi,
        private readonly ServerSettings $serverSettings,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
        private readonly JobQueueApi $jobQueueApi,
        private readonly DashboardFactory $dashboardFactory,
        private readonly EmailService $emailService,
        private readonly TmdbIsoCountryCache $countryCache,
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

        $this->sessionWrapper->set('deletedAccount', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function deleteApiToken() : Response
    {
        $this->userApi->deleteApiToken($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function deleteHistory() : Response
    {
        $this->movieApi->deleteHistoryByUserId($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('deletedUserHistory', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function deleteRatings() : Response
    {
        $this->movieApi->deleteRatingsByUserId($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('deletedUserRatings', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function generateLetterboxdExportData() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $zip = new ZipStream\ZipStream(
            outputName: 'export-for-letterboxd.zip',
            sendHttpHeaders: true,
        );

        foreach ($this->letterboxdExporter->generateCsvFiles($userId) as $index => $csvFile) {
            $zip->addFileFromPath('export-' . $index . '.csv', $csvFile);

            unlink($csvFile);
        }

        $zip->finish();

        return Response::createOk();
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

        $dashboardRowsSuccessfullyReset = $this->sessionWrapper->find('dashboardRowsSuccessfullyReset');
        $this->sessionWrapper->unset('dashboardRowsSuccessfullyReset');

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-dashboard.html.twig', [
                'dashboardRows' => $dashboardRows,
                'dashboardRowsSuccessfullyReset' => $dashboardRowsSuccessfullyReset,
            ]),
        );
    }

    public function renderDataAccountPage() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $importHistorySuccessful = $this->sessionWrapper->find('importHistorySuccessful');
        $importRatingsSuccessful = $this->sessionWrapper->find('importRatingsSuccessful');
        $importWatchlistSuccessful = $this->sessionWrapper->find('importWatchlistSuccessful');
        $importHistoryError = $this->sessionWrapper->find('importHistoryError');
        $deletedUserHistory = $this->sessionWrapper->find('deletedUserHistory');
        $deletedUserRatings = $this->sessionWrapper->find('deletedUserRatings');

        $this->sessionWrapper->unset(
            'importHistorySuccessful',
            'importRatingsSuccessful',
            'importWatchlistSuccessful',
            'importHistoryError',
            'deletedUserHistory',
            'deletedUserRatings',
        );

        $user = $this->userApi->fetchUser($userId);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-data.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'importHistorySuccessful' => $importHistorySuccessful,
                'importRatingsSuccessful' => $importRatingsSuccessful,
                'importWatchlistSuccessful' => $importWatchlistSuccessful,
                'importHistoryError' => $importHistoryError,
                'deletedUserHistory' => $deletedUserHistory,
                'deletedUserRatings' => $deletedUserRatings,
            ]),
        );
    }

    public function renderEmbyPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        $webhookId = $user->getEmbyWebhookId();

        if ($applicationUrl !== null && $webhookId !== null) {
            $webhookUrl = $this->webhookUrlBuilder->buildEmbyWebhookUrl($webhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-emby.html.twig', [
                'isActive' => $applicationUrl !== null,
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
                'countries' => $this->countryCache->fetchAll(),
                'userCountry' => $user->getCountry(),
                'apiToken' => $this->userApi->findApiTokenByUserId($user->getId()),
                'displayCharacterNamesInput' => $user->getDisplayCharacterNames(),
            ]),
        );
    }

    public function renderJellyfinPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        $webhookId = $user->getJellyfinWebhookId();

        $jellyfinDeviceId = $this->serverSettings->getJellyfinDeviceId();
        $jellyfinServerUrl = $this->userApi->findJellyfinServerUrl($user->getId());
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($user->getId());
        $jellyfinUsername = null;

        if ($jellyfinDeviceId !== null && $jellyfinServerUrl !== null && $jellyfinAuthentication !== null) {
            $jellyfinUsername = $this->jellyfinApi->findJellyfinUser($jellyfinAuthentication);
        }

        if ($applicationUrl !== null && $webhookId !== null) {
            $webhookUrl = $this->webhookUrlBuilder->buildJellyfinWebhookUrl($webhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-jellyfin.html.twig', [
                'isActive' => $applicationUrl !== null,
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

    public function renderLetterboxdPage() : Response
    {
        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $letterboxdDiarySyncSuccessful = $this->sessionWrapper->find('letterboxdDiarySyncSuccessful');
        $letterboxdRatingsSyncSuccessful = $this->sessionWrapper->find('letterboxdRatingsSyncSuccessful');
        $letterboxdRatingsImportFileInvalid = $this->sessionWrapper->find('letterboxdRatingsImportFileInvalid');
        $letterboxdDiaryImportFileInvalid = $this->sessionWrapper->find('letterboxdDiaryImportFileInvalid');

        $this->sessionWrapper->unset(
            'letterboxdDiarySyncSuccessful',
            'letterboxdRatingsSyncSuccessful',
            'letterboxdRatingsImportFileInvalid',
            'letterboxdDiaryImportFileInvalid',
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-letterboxd.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'letterboxdDiarySyncSuccessful' => $letterboxdDiarySyncSuccessful,
                'letterboxdRatingsSyncSuccessful' => $letterboxdRatingsSyncSuccessful,
                'letterboxdRatingsImportFileInvalid' => $letterboxdRatingsImportFileInvalid,
                'letterboxdDiaryImportFileInvalid' => $letterboxdDiaryImportFileInvalid,
            ]),
        );
    }

    public function renderLocationsAccountPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-locations.html.twig', [
                'locationsEnabled' => $user->hasLocationsEnabled(),
            ]),
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

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        $plexWebhookId = $user->getPlexWebhookId();

        if ($applicationUrl !== null && $plexWebhookId !== null) {
            $plexWebhookUrl = $this->webhookUrlBuilder->buildPlexWebhookUrl($plexWebhookId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-plex.html.twig', [
                'isActive' => $applicationUrl !== null,
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
        $applicationUrl = $this->serverSettings->getApplicationUrl();

        if ($applicationUrl !== null && $radarrFeedId !== null) {
            $radarrFeedUrl = $this->webhookUrlBuilder->buildRadarrFeedUrl($radarrFeedId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-radarr.html.twig', [
                'radarrFeedUrl' => $radarrFeedUrl ?? '-',
                'isActive' => $applicationUrl !== null
            ]),
        );
    }

    public function renderSecurityAccountPage() : Response
    {
        $user = $this->authenticationService->getCurrentUser();

        $totpEnabled = $this->twoFactorAuthenticationService->findTotpUri($user->getId()) === null ? false : true;

        $twoFactorAuthenticationEnabled = $this->sessionWrapper->find('twoFactorAuthenticationEnabled');
        $twoFactorAuthenticationDisabled = $this->sessionWrapper->find('twoFactorAuthenticationDisabled');

        $this->sessionWrapper->unset(
            'twoFactorAuthenticationDisabled',
            'twoFactorAuthenticationEnabled',
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-security.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'totpEnabled' => $totpEnabled,
                'twoFactorAuthenticationEnabled' => $twoFactorAuthenticationEnabled,
                'twoFactorAuthenticationDisabled' => $twoFactorAuthenticationDisabled
            ]),
        );
    }

    public function renderServerEmailPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-email.html.twig', [
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
                'smtpPassword' => $this->serverSettings->getSmtpPassword(),
                'smtpPasswordSetInEnv' => $this->serverSettings->isSmtpPasswordSetInEnvironment(),
            ]),
        );
    }

    public function renderServerGeneralPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-general.html.twig', [
                'applicationUrl' => $this->serverSettings->getApplicationUrl(),
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
        $jobsPerPage = $request->getGetParameters()['jpp'] ?? 30;

        $jobs = $this->jobQueueApi->fetchJobsForStatusPage((int)$jobsPerPage);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/settings-server-jobs.html.twig',
                ['jobs' => $jobs],
            ),
        );
    }

    public function renderServerUsersPage() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-server-users.html.twig'),
        );
    }

    public function renderTraktPage() : Response
    {
        $traktCredentialsUpdated = $this->sessionWrapper->find('traktCredentialsUpdated');
        $scheduledTraktHistoryImport = $this->sessionWrapper->find('scheduledTraktHistoryImport');
        $scheduledTraktRatingsImport = $this->sessionWrapper->find('scheduledTraktRatingsImport');

        $this->sessionWrapper->unset('traktCredentialsUpdated', 'scheduledTraktHistoryImport', 'scheduledTraktRatingsImport');

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-trakt.html.twig', [
                'traktClientId' => $user->getTraktClientId(),
                'traktUserName' => $user->getTraktUserName(),
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'traktCredentialsUpdated' => $traktCredentialsUpdated,
                'traktScheduleHistorySyncSuccessful' => $scheduledTraktHistoryImport,
                'traktScheduleRatingsSyncSuccessful' => $scheduledTraktRatingsImport,
            ]),
        );
    }

    public function resetDashboardRows() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $this->userApi->updateVisibleDashboardRows($userId, null);
        $this->userApi->updateExtendedDashboardRows($userId, null);
        $this->userApi->updateOrderDashboardRows($userId, null);

        $this->sessionWrapper->set('dashboardRowsSuccessfullyReset', true);

        return Response::createOk();
    }

    public function sendTestEmail(Request $request) : Response
    {
        $requestData = Json::decode($request->getBody());

        $smtpConfig = SmtpConfig::create(
            (string)$requestData['smtpHost'],
            (int)$requestData['smtpPort'],
            (string)$requestData['smtpFromAddress'],
            (string)$requestData['smtpEncryption'],
            (bool)$requestData['smtpWithAuthentication'],
            isset($requestData['smtpUser']) === false ? null : $requestData['smtpUser'],
            isset($requestData['smtpPassword']) === false ? null : $requestData['smtpPassword'],
        );

        try {
            $this->emailService->sendEmail(
                $requestData['recipient'],
                'Movary: Test Email',
                'This is a test email sent to check the currently set email settings. It seems to work!',
                $smtpConfig,
            );
        } catch (CannotSendEmailException $e) {
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

        $userId = $this->authenticationService->getCurrentUserId();

        try {
            $this->userApi->updatePrivacyLevel($userId, $privacyLevel);
            $this->userApi->updateDateFormatId($userId, $dateFormat);
            $this->userApi->updateCountry($userId, $country);
            $this->userApi->updateName($userId, (string)$name);
            $this->userApi->updateWatchlistAutomaticRemovalEnabled($userId, $enableAutomaticWatchlistRemoval);
            $this->userApi->updateDisplayCharacterNames($userId, $displayCharacterNames);
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
        if ($smtpUser !== null) {
            $this->serverSettings->setSmtpUser($smtpUser);
        }
        if ($smtpPassword !== null) {
            $this->serverSettings->setSmtpPassword($smtpPassword);
        }

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

        $this->sessionWrapper->set('traktCredentialsUpdated', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
