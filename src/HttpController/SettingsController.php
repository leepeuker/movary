<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\SessionService;
use Movary\Application\SyncLog\Repository;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class SettingsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Repository $syncLogRepository,
        private readonly SessionService $sessionService,
        private readonly ?string $applicationVersion = null,
    ) {
    }

    public function render() : Response
    {
        if ($this->sessionService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/settings.html.twig', [
                'plexWebhookUrl' => $this->applicationVersion ?? '-',
                'applicationVersion' => $this->applicationVersion ?? '-',
                'lastSyncTrakt' => $this->syncLogRepository->findLastTraktSync() ?? '-',
                'lastSyncTmdb' => $this->syncLogRepository->findLastTmdbSync() ?? '-',
                'lastSyncLetterboxd' => $this->syncLogRepository->findLastLetterboxdSync() ?? '-',
            ]),
        );
    }
}
