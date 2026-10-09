<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class UserIsUnauthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ApplicationUrlService $urlService,
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $authenticatedUser = $this->authenticationService->authenticateWebSession();
        if ($authenticatedUser === null) {
            return null;
        }

        $userName = $this->userApi->fetchUser($authenticatedUser->getUserId())->getName();

        return Response::createSeeOther(
            $this->urlService->createApplicationUrl(
                RelativeUrl::create("/users/$userName"),
            ),
        );
    }
}
