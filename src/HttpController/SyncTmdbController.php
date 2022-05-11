<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\SessionService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class SyncTmdbController
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {
    }

    public function execute() : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        exec(
            sprintf(
                "%s 2>&1",
                'cd ' . __DIR__ . '/../../ && php bin/console.php app:sync-tmdb',
            )
        );

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
