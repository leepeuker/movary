<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class IsAdmin implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function __invoke(Request $request) : ?Response
    {
        $authenticatedUser = $this->authenticationService->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            return Response::createBearerUnauthorized();
        }

        $user = $this->userApi->findUserById($authenticatedUser->getUserId());
        if ($user?->isAdmin() !== true) {
            return Response::createForbidden();
        }

        return null;
    }
}
