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
        if ($this->sessionService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        throw new \RuntimeException('Not implemented yet');
    }
}
