<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserIsUnauthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false) {
            return null;
        }

        $userName = $this->authenticationService->getCurrentUser()->getName();

        return Response::createSeeOther("/users/$userName/dashboard");
    }
}
