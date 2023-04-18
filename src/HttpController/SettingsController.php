<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Github\GithubApi;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Plex\PlexApi;
use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexAccount;
use Movary\Domain\Movie;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Letterboxd\LetterboxdExporter;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
use Twig\Environment;
use ZipStream;

class SettingsController
{
    private const VERSION_PLACEHOLDER = 'dev';

    public function __construct(
        private readonly Environment $twig,
        private readonly JobQueueApi $workerService,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly Movie\MovieApi $movieApi,
        private readonly GithubApi $githubApi,
        private readonly SessionWrapper $sessionWrapper,
        private readonly LetterboxdExporter $letterboxdExporter,
        private readonly TraktApi $traktApi,
        private readonly PlexApi $plexApi,
        private readonly ?string $currentApplicationVersion = null,
    ) {
    }

    public function deleteAccount() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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

    public function deleteHistory() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $options = new ZipStream\Option\Archive();
        $options->setSendHttpHeaders(true);

        $zip = new ZipStream\ZipStream('export-for-letterboxd.zip', $options);

        foreach ($this->letterboxdExporter->generateCsvFiles($userId) as $index => $csvFile) {
            $zip->addFileFromPath('export-' . $index . '.csv', $csvFile);

            unlink($csvFile);
        }

        $zip->finish();

        return Response::createOk();
    }

    public function renderAppPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-app.html.twig', [
                'currentApplicationVersion' => $this->currentApplicationVersion ?? self::VERSION_PLACEHOLDER,
                'latestRelease' => $this->githubApi->fetchLatestMovaryRelease(),
                'timeZone' => date_default_timezone_get(),
            ]),
        );
    }

    public function renderDataAccountPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $importHistorySuccessful = $this->sessionWrapper->find('importHistorySuccessful');
        $importRatingsSuccessful = $this->sessionWrapper->find('importRatingsSuccessful');
        $importHistoryError = $this->sessionWrapper->find('importHistoryError');
        $deletedUserHistory = $this->sessionWrapper->find('deletedUserHistory');
        $deletedUserRatings = $this->sessionWrapper->find('deletedUserRatings');

        $this->sessionWrapper->unset(
            'importHistorySuccessful',
            'importRatingsSuccessful',
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
                'importHistoryError' => $importHistoryError,
                'deletedUserHistory' => $deletedUserHistory,
                'deletedUserRatings' => $deletedUserRatings,
            ]),
        );
    }

    public function renderGeneralAccountPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $user = $this->authenticationService->getCurrentUser();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-general.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
                'dateFormats' => DateFormat::getFormats(),
                'dateFormatSelected' => $user->getDateFormatId(),
                'privacyLevel' => $user->getPrivacyLevel(),
                'username' => $user->getName(),
            ]),
        );
    }

    public function renderJellyfinPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $jellyfinScrobblerOptionsUpdated = $this->sessionWrapper->find('jellyfinScrobblerOptionsUpdated');
        $this->sessionWrapper->unset('jellyfinScrobblerOptionsUpdated');

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-jellyfin.html.twig', [
                'jellyfinWebhookUrl' => $user->getJellyfinWebhookId() ?? '-',
                'scrobbleWatches' => $user->hasJellyfinScrobbleWatchesEnabled(),
                'jellyfinScrobblerOptionsUpdated' => $jellyfinScrobblerOptionsUpdated,
            ]),
        );
    }

    public function renderLetterboxdPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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

    public function renderNetflixPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-netflix.html.twig'),
        );
    }

    public function renderPasswordAccountPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $user = $this->authenticationService->getCurrentUser();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account-password.html.twig', [
                'coreAccountChangesDisabled' => $user->hasCoreAccountChangesDisabled(),
            ]),
        );
    }

    public function renderPlexPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $plexAccessToken = $this->userApi->findPlexAccessToken($this->authenticationService->getCurrentUserId());
        if($plexAccessToken === null) {
            $plexAuth = $this->plexApi->generatePlexAuthenticationUrl();
        } else {
            $plexAccount = $this->plexApi->fetchPlexAccount(PlexAccessToken::createPlexAccessToken($plexAccessToken));
            if($plexAccount instanceof PlexAccount) {
                $plexUsername = $plexAccount->getPlexUsername();
                if(($plexServerUrl = $this->userApi->findPlexServerUrl($this->authenticationService->getCurrentUserId())) == null) {
                    $plexServerUrl = "";
                }
            } else {
                $plexAuth = $this->plexApi->generatePlexAuthenticationUrl();
            }
        }

        $plexScrobblerOptionsUpdated = $this->sessionWrapper->find('plexScrobblerOptionsUpdated');
        $this->sessionWrapper->unset('plexScrobblerOptionsUpdated');

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $serverUrlStatus = $this->sessionWrapper->find('serverUrlStatus');
        if($serverUrlStatus) {
            $this->sessionWrapper->unset('serverUrlStatus');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-integration-plex.html.twig', [
                'plexWebhookUrl' => $user->getPlexWebhookId() ?? '-',
                'scrobbleWatches' => $user->hasPlexScrobbleWatchesEnabled(),
                'scrobbleRatings' => $user->hasPlexScrobbleRatingsEnabled(),
                'plexScrobblerOptionsUpdated' => $plexScrobblerOptionsUpdated,
                'plexAuth' => $plexAuth ?? "",
                'plexServerUrl' => $plexServerUrl ?? "",
                'plexUsername' => $plexUsername ?? "",
                'serverUrlStatus' => $serverUrlStatus
            ]),
        );
    }

    public function renderTraktPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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
                'lastSyncTrakt' => $this->workerService->findLastTraktSync($user->getId()) ?? '-',
            ]),
        );
    }

    public function renderUsersPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        if ($this->authenticationService->getCurrentUser()->isAdmin() === false) {
            return Response::createSeeOther('/');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-users.html.twig'),
        );
    }

    public function traktVerifyCredentials(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $requestData = Json::decode($request->getBody());

        $clientId = $requestData['clientId'] ?? '';
        $username = $requestData['username'] ?? '';

        if ($this->traktApi->verifyCredentials($clientId, $username) === false) {
            return Response::createBadRequest();
        }

        return Response::createOk();
    }

    public function updateGeneral(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $requestData = Json::decode($request->getBody());

        $privacyLevel = isset($requestData['privacyLevel']) === false ? 1 : (int)$requestData['privacyLevel'];
        $dateFormat = empty($requestData['dateFormat']) === true ? 0 : (int)$requestData['dateFormat'];
        $name = $requestData['username'] ?? '';

        try {
            $this->userApi->updatePrivacyLevel($this->authenticationService->getCurrentUserId(), $privacyLevel);
            $this->userApi->updateDateFormatId($this->authenticationService->getCurrentUserId(), $dateFormat);
            $this->userApi->updateName($this->authenticationService->getCurrentUserId(), (string)$name);
        } catch (User\Exception\UsernameInvalidFormat) {
            return Response::createBadRequest('Username not meeting requirements');
        } catch (User\Exception\UsernameNotUnique) {
            return Response::createBadRequest('Username is not unique');
        }

        return Response::createOk();
    }

    public function updateJellyfin(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = $request->getPostParameters();

        $this->userApi->updateJellyfinScrobblerOptions($userId, (bool)$postParameters['scrobbleWatches']);

        $this->sessionWrapper->set('jellyfinScrobblerOptionsUpdated', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function updatePassword(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = $request->getPostParameters();

        $scrobbleWatches = (bool)$postParameters['scrobbleWatches'];
        $scrobbleRatings = (bool)$postParameters['scrobbleRatings'];

        $this->userApi->updatePlexScrobblerOptions($userId, $scrobbleWatches, $scrobbleRatings);

        $this->sessionWrapper->set('plexScrobblerOptionsUpdated', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function updateTrakt(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

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
