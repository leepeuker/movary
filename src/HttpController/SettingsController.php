<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\User;
use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\DateFormat;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\Worker\Service;
use Twig\Environment;

class SettingsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Service $workerService,
        private readonly Authentication $authenticationService,
        private readonly User\Api $userApi,
        private readonly Movie\Api $movieApi,
        private readonly ?string $applicationVersion = null,
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
            throw new \RuntimeException('Account deletion is disabled for user: ' . $userId);
        }

        $this->userApi->deleteUser($userId);

        $this->authenticationService->logout();

        $_SESSION['deletedAccount'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function deleteHistory() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->movieApi->deleteHistoryByUserId($this->authenticationService->getCurrentUserId());

        $_SESSION['deletedUserHistory'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function deleteRatings() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->movieApi->deleteRatingsByUserId($this->authenticationService->getCurrentUserId());

        $_SESSION['deletedUserRatings'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function renderAccountPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $passwordErrorNotEqual = empty($_SESSION['passwordErrorNotEqual']) === false ? true : null;
        $passwordErrorMinLength = empty($_SESSION['passwordErrorMinLength']) === false ? $_SESSION['passwordErrorMinLength'] : null;
        $passwordErrorCurrentInvalid = empty($_SESSION['passwordErrorCurrentInvalid']) === false ? $_SESSION['passwordErrorCurrentInvalid'] : null;
        $passwordUpdated = empty($_SESSION['passwordUpdated']) === false ? $_SESSION['passwordUpdated'] : null;
        $importHistorySuccessful = empty($_SESSION['importHistorySuccessful']) === false ? $_SESSION['importHistorySuccessful'] : null;
        $importRatingsSuccessful = empty($_SESSION['importRatingsSuccessful']) === false ? $_SESSION['importRatingsSuccessful'] : null;
        $importHistoryError = empty($_SESSION['importHistoryError']) === false ? $_SESSION['importHistoryError'] : null;
        $deletedUserHistory = empty($_SESSION['deletedUserHistory']) === false ? $_SESSION['deletedUserHistory'] : null;
        $deletedUserRatings = empty($_SESSION['deletedUserRatings']) === false ? $_SESSION['deletedUserRatings'] : null;
        $generalUpdated = empty($_SESSION['generalUpdated']) === false ? $_SESSION['generalUpdated'] : null;
        $generalErrorUsernameInvalidFormat = empty($_SESSION['generalErrorUsernameInvalidFormat']) === false ? $_SESSION['generalErrorUsernameInvalidFormat'] : null;
        $generalErrorUsernameNotUnique = empty($_SESSION['generalErrorUsernameNotUnique']) === false ? $_SESSION['generalErrorUsernameNotUnique'] : null;
        unset(
            $_SESSION['passwordUpdated'],
            $_SESSION['passwordErrorCurrentInvalid'],
            $_SESSION['passwordErrorMinLength'],
            $_SESSION['passwordErrorNotEqual'],
            $_SESSION['importHistorySuccessful'],
            $_SESSION['importRatingsSuccessful'],
            $_SESSION['importHistoryError'],
            $_SESSION['deletedUserHistory'],
            $_SESSION['deletedUserRatings'],
            $_SESSION['generalUpdated'],
            $_SESSION['generalErrorUsernameInvalidFormat'],
            $_SESSION['generalErrorUsernameNotUnique'],
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
                'applicationVersion' => $this->applicationVersion ?? '-',
                'lastSyncTmdb' => $this->workerService->findLastTmdbSync() ?? '-',
            ]),
        );
    }

    public function renderLetterboxdPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        $letterboxdHistorySyncSuccessful = empty($_SESSION['letterboxdHistorySyncSuccessful']) === false ? $_SESSION['letterboxdHistorySyncSuccessful'] : null;
        $letterboxdRatingsSyncSuccessful = empty($_SESSION['letterboxdRatingsSyncSuccessful']) === false ? $_SESSION['letterboxdRatingsSyncSuccessful'] : null;
        $letterboxdRatingsImportFileInvalid = empty($_SESSION['letterboxdRatingsImportFileInvalid']) === false ? $_SESSION['letterboxdRatingsImportFileInvalid'] : null;
        $letterboxdHistoryImportFileInvalid = empty($_SESSION['letterboxdHistoryImportFileInvalid']) === false ? $_SESSION['letterboxdHistoryImportFileInvalid'] : null;
        unset(
            $_SESSION['letterboxdHistorySyncSuccessful'],
            $_SESSION['letterboxdRatingsSyncSuccessful'],
            $_SESSION['letterboxdRatingsImportFileInvalid'],
            $_SESSION['letterboxdHistoryImportFileInvalid'],
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

        $user = $this->userApi->fetchUser($this->authenticationService->getCurrentUserId());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings-plex.html.twig', [
                'plexWebhookUrl' => $user->getPlexWebhookId() ?? '-',
            ]),
        );
    }

    public function renderTraktPage() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $traktCredentialsUpdated = empty($_SESSION['traktCredentialsUpdated']) === false ? $_SESSION['traktCredentialsUpdated'] : null;
        $scheduledTraktHistoryImport = empty($_SESSION['scheduledTraktHistoryImport']) === false ? $_SESSION['scheduledTraktHistoryImport'] : null;
        $scheduledTraktRatingsImport = empty($_SESSION['scheduledTraktRatingsImport']) === false ? $_SESSION['scheduledTraktRatingsImport'] : null;
        unset(
            $_SESSION['traktCredentialsUpdated'],
            $_SESSION['scheduledTraktHistoryImport'],
            $_SESSION['scheduledTraktRatingsImport'],
        );

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

            $_SESSION['generalUpdated'] = true;
        } catch (User\Exception\UsernameInvalidFormat $e) {
            $_SESSION['generalErrorUsernameInvalidFormat'] = true;
        } catch (User\Exception\UsernameNotUnique $e) {
            $_SESSION['generalErrorUsernameNotUnique'] = true;
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
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
            $_SESSION['passwordErrorCurrentInvalid'] = true;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])]
            );
        }

        if ($newPassword !== $newPasswordRepeat) {
            $_SESSION['passwordErrorNotEqual'] = true;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])]
            );
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['passwordErrorMinLength'] = 8;

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])]
            );
        }

        if ($user->areCoreAccountChangesDisabled() === true) {
            throw new \RuntimeException('Password changes are disabled for user: ' . $userId);
        }

        $this->userApi->updatePassword($userId, $newPassword);

        $_SESSION['passwordUpdated'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
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

        $_SESSION['traktCredentialsUpdated'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
