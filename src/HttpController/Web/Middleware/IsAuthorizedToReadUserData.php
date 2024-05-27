<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

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

        if ($this->authenticationService->isUserPageVisibleForWebRequest($requestedUser) === false) {
            return Response::createForbiddenRedirect($request->getPath());
        }

        return null;
    }
}
