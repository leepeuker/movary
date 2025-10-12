<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class IsAuthorizedToReadUserData implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    public function __invoke(Request $request) : ?Response
    {
        $requestedUsername = (string)$request->getRouteParameters()['username'];

        $requestedUser = $this->userApi->findUserByName($requestedUsername);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisibleForWebRequest($requestedUser) === true) {
            return null;
        }

        return Response::createForbiddenRedirect(
            $this->urlService->createApplicationUrl(
                RelativeUrl::create($request->getPath()),
            ),
            $this->urlService->createApplicationUrl(),
        );
    }
}
