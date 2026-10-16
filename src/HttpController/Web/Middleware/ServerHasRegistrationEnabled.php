<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\RelativeUrl;

class ServerHasRegistrationEnabled implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly ApplicationUrlService $urlService,
        private readonly bool $registrationEnabled,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->userApi->hasUsers() === false || $this->registrationEnabled === true) {
            return null;
        }

        return Response::createSeeOther(
            $this->urlService->createApplicationUrl(RelativeUrl::create('/login')),
        );
    }
}
