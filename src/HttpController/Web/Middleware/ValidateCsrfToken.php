<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\CsrfTokenProvider;
use Movary\Service\CsrfTokenService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class ValidateCsrfToken implements MiddlewareInterface
{
    public function __construct(private readonly CsrfTokenService $tokenService)
    {
    }

    public function __invoke(Request $request) : ?Response
    {
        $submittedToken = $request->getHeader('X-CSRF-Token');
        if ($submittedToken === null) {
            $formToken = $request->getPostParameters()['_csrf'] ?? null;
            $submittedToken = is_string($formToken) === true ? $formToken : null;
        }

        $isValid = $this->tokenService->isValid(
            $request->getCookie(CsrfTokenProvider::COOKIE_NAME),
            $submittedToken,
            $request->getCookie(Authentication::AUTHENTICATION_COOKIE_NAME),
        );

        return $isValid === true ? null : Response::createForbidden();
    }
}
