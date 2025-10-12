<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class UserIsAuthenticated implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            return null;
        }

        return Response::createForbiddenRedirect(
            $this->urlService->createApplicationUrl(
                RelativeUrl::create($_SERVER['REQUEST_URI']),
            ),
            $this->urlService->createApplicationUrl(),
        );
    }
}
