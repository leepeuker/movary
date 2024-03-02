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
        $requestedUsername = (string)$request->getRouteParameters()['username'];

        $requestedUser = $this->userApi->findUserByName($requestedUsername);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisibleForApiRequest($request, $requestedUser) === false) {
            return Response::createForbidden();
        }

        return null;
    }
}
