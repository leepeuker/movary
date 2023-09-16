<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Middleware;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\Domain\User\Service\Authentication;
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
        $requestedUser = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisibleForApiRequest($request, $requestedUser) === false) {
            return Response::createForbidden();
        }

        return null;
    }
}
