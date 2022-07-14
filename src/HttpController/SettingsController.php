<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\SyncLog\Repository;
use Movary\Application\User;
use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class SettingsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Repository $syncLogRepository,
        private readonly Authentication $authenticationService,
        private readonly User\Api $userApi,
        private readonly ?string $applicationVersion = null,
    ) {
    }

    public function render() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        $passwordErrorNotEqual = empty($_SESSION['passwordErrorNotEqual']) === false ? true : null;
        $passwordErrorMinLength = empty($_SESSION['passwordErrorMinLength']) === false ? $_SESSION['passwordErrorMinLength'] : null;
        $passwordErrorCurrentInvalid = empty($_SESSION['passwordErrorCurrentInvalid']) === false ? $_SESSION['passwordErrorCurrentInvalid'] : null;
        $passwordUpdated = empty($_SESSION['passwordUpdated']) === false ? $_SESSION['passwordUpdated'] : null;
        unset($_SESSION['passwordUpdated'], $_SESSION['passwordErrorCurrentInvalid'], $_SESSION['passwordErrorMinLength'], $_SESSION['passwordErrorNotEqual']);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings.html.twig', [
                'plexWebhookUrl' => $this->userApi->findPlexWebhookId($userId) ?? '-',
                'passwordErrorNotEqual' => $passwordErrorNotEqual,
                'passwordErrorMinLength' => $passwordErrorMinLength,
                'passwordErrorCurrentInvalid' => $passwordErrorCurrentInvalid,
                'passwordUpdated' => $passwordUpdated,
                'traktClientId' => $this->userApi->findTraktClientId($userId),
                'traktUserName' => $this->userApi->findTraktUserName($userId),
                'applicationVersion' => $this->applicationVersion ?? '-',
                'lastSyncTrakt' => $this->syncLogRepository->findLastTraktSync() ?? '-',
                'lastSyncTmdb' => $this->syncLogRepository->findLastTmdbSync() ?? '-',
                'lastSyncLetterboxd' => $this->syncLogRepository->findLastLetterboxdSync() ?? '-',
            ]),
        );
    }

    public function updatePassword(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $newPassword = $request->getPostParameters()['newPassword'];
        $newPasswordRepeat = $request->getPostParameters()['newPasswordRepeat'];
        $currentPassword = $request->getPostParameters()['currentPassword'];

        if ($this->userApi->isValidPassword($this->authenticationService->getCurrentUserId(), $currentPassword) === false) {
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

        $this->userApi->updatePassword($this->authenticationService->getCurrentUserId(), $newPassword);

        $_SESSION['passwordUpdated'] = true;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
