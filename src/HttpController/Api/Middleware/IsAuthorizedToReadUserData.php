<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class IsAuthorizedToReadUserData implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function __invoke(Request $request) : ?Response
    {
        $authenticatedUser = $this->authenticationService->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            return Response::createBearerUnauthorized();
        }

        $requestedUsername = (string)$request->getRouteParameters()['username'];

        $requestedUser = $this->userApi->findUserByName($requestedUsername);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisible($requestedUser, $authenticatedUser) === false) {
            return Response::createForbidden();
        }

        return null;
    }
}
