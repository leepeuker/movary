<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class UserIsUnauthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return null;
        }

        $userName = $this->authenticationService->getCurrentUser()->getName();

        return Response::createSeeOther("/users/$userName/dashboard");
    }
}
