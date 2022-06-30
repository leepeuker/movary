<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class SyncTraktController
{
    public function __construct(
        private readonly Authentication $authenticationService
    ) {
    }

    public function execute() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        throw new \RuntimeException('Not implemented yet');
    }
}
