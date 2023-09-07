<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class UserIsAdmin implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function __invoke() : ?Response
    {
        if ($this->authenticationService->getCurrentUser()->isAdmin() === false) {
            return Response::createSeeOther('/');
        }

        return null;
    }
}
