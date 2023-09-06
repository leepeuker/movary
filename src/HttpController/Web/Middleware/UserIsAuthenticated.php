<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class UserIsAuthenticated
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function main() : ?Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther("/login");
        }

        return null;
    }
}
