<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserIsAdmin implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $authenticatedUser = $this->authenticationService->requireWebSession();
        if ($this->userApi->fetchUser($authenticatedUser->getUserId())->isAdmin() === true) {
            return null;
        }

        return Response::createForbidden();
    }
}
