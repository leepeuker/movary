<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class UserIsAuthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->authenticationService->isUserAuthenticated() === true) {
            return null;
        }

        return Response::createForbidden();
    }
}
