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

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings.html.twig', [
                'plexWebhookUrl' => $this->userApi->findPlexWebhookId($userId) ?? '-',
                'traktClientId' => $this->userApi->findTraktClientId($userId),
                'traktUserName' => $this->userApi->findTraktUserName($userId),
                'applicationVersion' => $this->applicationVersion ?? '-',
                'lastSyncTrakt' => $this->syncLogRepository->findLastTraktSync() ?? '-',
                'lastSyncTmdb' => $this->syncLogRepository->findLastTmdbSync() ?? '-',
                'lastSyncLetterboxd' => $this->syncLogRepository->findLastLetterboxdSync() ?? '-',
            ]),
        );
    }

    public function updateTrakt(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $traktClientId = $request->getPostParameters()['traktClientId'];
        $traktUserName = $request->getPostParameters()['traktUserName'];
        $userId = $this->authenticationService->getCurrentUserId();

        if (empty($traktClientId) === true) {
            $traktClientId = null;
        }
        if (empty($traktUserName) === true) {
            $traktUserName = null;
        }

        $this->userApi->updateTraktClientId($userId, $traktClientId);
        $this->userApi->updateTraktUserName($userId, $traktUserName);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
