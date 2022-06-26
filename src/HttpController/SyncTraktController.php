<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\SessionService;
use Movary\ValueObject\Http\Response;

class SyncTraktController
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

        throw new \RuntimeException('Not implemented yet');
    }
}
