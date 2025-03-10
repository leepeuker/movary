<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class IsUnauthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function __invoke(Request $request) : ?Response
    {
        if ($this->authenticationService->getUserIdByApiToken($request) !== null) {
            return Response::createForbidden();
        }

        return null;
    }
}
