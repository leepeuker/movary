<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Github\GithubApi;
use Movary\Domain\Movie;
use Movary\Domain\User;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
use Twig\Environment;

class SettingsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly JobQueueApi $workerService,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly Movie\MovieApi $movieApi,
        private readonly GithubApi $githubApi,
        private readonly SessionWrapper $sessionWrapper,
        private readonly ?string $currentApplicationVersion = null,
    ) {
    }

    public function deleteAccount() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $user = $this->userApi->fetchUser($userId);

        if ($user->areCoreAccountChangesDisabled() === true) {
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
            return Response::createFoundRedirect('/');
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
            return Response::createFoundRedirect('/');
        }

        $this->movieApi->deleteRatingsByUserId($this->authenticationService->getCurrentUserId());

        $this->sessionWrapper->set('deletedUserRatings', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function renderAccountPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $passwordErrorNotEqual = $this->sessionWrapper->find('passwordErrorNotEqual');
        $passwordErrorMinLength = $this->sessionWrapper->find('passwordErrorMinLength');
        $passwordErrorCurrentInvalid = $this->sessionWrapper->find('passwordErrorCurrentInvalid');
        $passwordUpdated = $this->sessionWrapper->find('passwordUpdated');
        $importHistorySuccessful = $this->sessionWrapper->find('importHistorySuccessful');
        $importRatingsSuccessful = $this->sessionWrapper->find('importRatingsSuccessful');
        $importHistoryError = $this->sessionWrapper->find('importHistoryError');
        $deletedUserHistory = $this->sessionWrapper->find('deletedUserHistory');
        $deletedUserRatings = $this->sessionWrapper->find('deletedUserRatings');
        $generalUpdated = $this->sessionWrapper->find('generalUpdated');
        $generalErrorUsernameInvalidFormat = $this->sessionWrapper->find('generalErrorUsernameInvalidFormat');
        $generalErrorUsernameNotUnique = $this->sessionWrapper->find('generalErrorUsernameNotUnique');

        $this->sessionWrapper->unset(
            'passwordUpdated',
            'passwordErrorCurrentInvalid',
            'passwordErrorMinLength',
            'passwordErrorNotEqual',
            'importHistorySuccessful',
            'importRatingsSuccessful',
            'importHistoryError',
            'deletedUserHistory',
            'deletedUserRatings',
            'generalUpdated',
            'generalErrorUsernameInvalidFormat',
            'generalErrorUsernameNotUnique',
        );

        $user = $this->userApi->fetchUser($userId);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-account.html.twig', [
                'coreAccountChangesDisabled' => $user->areCoreAccountChangesDisabled(),
                'dateFormats' => DateFormat::getFormats(),
                'dateFormatSelected' => $user->getDateFormatId(),
                'privacyLevel' => $user->getPrivacyLevel(),
                'generalUpdated' => $generalUpdated,
                'generalErrorUsernameInvalidFormat' => $generalErrorUsernameInvalidFormat,
                'generalErrorUsernameNotUnique' => $generalErrorUsernameNotUnique,
                'plexWebhookUrl' => $user->getPlexWebhookId() ?? '-',
                'passwordErrorNotEqual' => $passwordErrorNotEqual,
                'passwordErrorMinLength' => $passwordErrorMinLength,
                'passwordErrorCurrentInvalid' => $passwordErrorCurrentInvalid,
                'importHistorySuccessful' => $importHistorySuccessful,
                'importRatingsSuccessful' => $importRatingsSuccessful,
                'passwordUpdated' => $passwordUpdated,
                'importHistoryError' => $importHistoryError,
                'deletedUserHistory' => $deletedUserHistory,
                'deletedUserRatings' => $deletedUserRatings,
                'username' => $user->getName(),
            ]),
        );
    }

    public function renderAppPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-app.html.twig', [
                'currentApplicationVersion' => $this->currentApplicationVersion ?? '-',
                'latestApplicationVersion' => $this->githubApi->findLatestApplicationLatestVersion(),
                'lastSyncTmdb' => $this->workerService->findLastTmdbSync() ?? '-',
                'lastSyncImdb' => $this->workerService->findLastImdbSync() ?? '-',
            ]),
        );
    }

    public function renderLetterboxdPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $letterboxdHistorySyncSuccessful = $this->sessionWrapper->find('letterboxdHistorySyncSuccessful');
        $letterboxdRatingsSyncSuccessful = $this->sessionWrapper->find('letterboxdRatingsSyncSuccessful');
        $letterboxdRatingsImportFileInvalid = $this->sessionWrapper->find('letterboxdRatingsImportFileInvalid');
        $letterboxdHistoryImportFileInvalid = $this->sessionWrapper->find('letterboxdHistoryImportFileInvalid');

        $this->sessionWrapper->unset(
            'letterboxdHistorySyncSuccessful',
            'letterboxdRatingsSyncSuccessful',
            'letterboxdRatingsImportFileInvalid',
            'letterboxdHistoryImportFileInvalid',
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-letterboxd.html.twig', [
                'coreAccountChangesDisabled' => $user->areCoreAccountChangesDisabled(),
                'letterboxdHistorySyncSuccessful' => $letterboxdHistorySyncSuccessful,
                'letterboxdRatingsSyncSuccessful' => $letterboxdRatingsSyncSuccessful,
                'letterboxdRatingsImportFileInvalid' => $letterboxdRatingsImportFileInvalid,
                'letterboxdHistoryImportFileInvalid' => $letterboxdHistoryImportFileInvalid,
            ]),
        );
    }

    public function renderPlexPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $plexScrobblerOptionsUpdated = $this->sessionWrapper->find('plexScrobblerOptionsUpdated');
        $this->sessionWrapper->unset('plexScrobblerOptionsUpdated');

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-plex.html.twig', [
                'plexWebhookUrl' => $user->getPlexWebhookId() ?? '-',
                'scrobbleViews' => $user->getPlexScrobbleViews(),
                'scrobbleRatings' => $user->getPlexScrobbleRating(),
                'plexScrobblerOptionsUpdated' => $plexScrobblerOptionsUpdated,
            ]),
        );
    }

    public function renderTraktPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $traktCredentialsUpdated = $this->sessionWrapper->find('traktCredentialsUpdated');
        $scheduledTraktHistoryImport = $this->sessionWrapper->find('scheduledTraktHistoryImport');
        $scheduledTraktRatingsImport = $this->sessionWrapper->find('scheduledTraktRatingsImport');

        $this->sessionWrapper->unset('traktCredentialsUpdated', 'scheduledTraktHistoryImport', 'scheduledTraktRatingsImport');

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-trakt.html.twig', [
                'traktClientId' => $user->getTraktClientId(),
                'traktUserName' => $user->getTraktUserName(),
                'coreAccountChangesDisabled' => $user->areCoreAccountChangesDisabled(),
                'traktCredentialsUpdated' => $traktCredentialsUpdated,
                'traktScheduleHistorySyncSuccessful' => $scheduledTraktHistoryImport,
                'traktScheduleRatingsSyncSuccessful' => $scheduledTraktRatingsImport,
                'lastSyncTrakt' => $this->workerService->findLastTraktSync($user->getId()) ?? '-',
            ]),
        );
    }

    public function updateGeneral(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $postParameters = $request->getPostParameters();

        $privacyLevel = isset($postParameters['privacyLevel']) === false ? 1 : (int)$postParameters['privacyLevel'];
        $dateFormat = empty($postParameters['dateFormat']) === true ? 0 : (int)$postParameters['dateFormat'];
        $name = $postParameters['username'] ?? '';

        try {
            $this->userApi->updatePrivacyLevel($this->authenticationService->getCurrentUserId(), $privacyLevel);
            $this->userApi->updateDateFormatId($this->authenticationService->getCurrentUserId(), $dateFormat);
            $this->userApi->updateName($this->authenticationService->getCurrentUserId(), (string)$name);

            $this->sessionWrapper->set('generalUpdated', true);
        } catch (User\Exception\UsernameInvalidFormat $e) {
            $this->sessionWrapper->set('generalErrorUsernameInvalidFormat', true);
        } catch (User\Exception\UsernameNotUnique $e) {
            $this->sessionWrapper->set('generalErrorUsernameNotUnique', true);
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function updatePassword(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $user = $this->userApi->fetchUser($userId);

        $newPassword = $request->getPostParameters()['newPassword'];
        $newPasswordRepeat = $request->getPostParameters()['newPasswordRepeat'];
        $currentPassword = $request->getPostParameters()['currentPassword'];

        if ($this->userApi->isValidPassword($userId, $currentPassword) === false) {
            $this->sessionWrapper->set('passwordErrorCurrentInvalid', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        if ($newPassword !== $newPasswordRepeat) {
            $this->sessionWrapper->set('passwordErrorNotEqual', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        if (strlen($newPassword) < 8) {
            $this->sessionWrapper->set('passwordErrorMinLength', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        if ($user->areCoreAccountChangesDisabled() === true) {
            throw new RuntimeException('Password changes are disabled for user: ' . $userId);
        }

        $this->userApi->updatePassword($userId, $newPassword);

        $this->sessionWrapper->set('passwordUpdated', true);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }

    public function updatePlex(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $postParameters = $request->getPostParameters();

        $scrobbleViews = (bool)$postParameters['scrobbleViews'];
        $scrobbleRatings = (bool)$postParameters['scrobbleRatings'];

        $this->userApi->updatePlexScrobblerOptions($userId, $scrobbleViews, $scrobbleRatings);

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
            return Response::createFoundRedirect('/');
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
