<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class ServerHasUsers implements MiddlewareInterface
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly ApplicationUrlService $urlService,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        if ($this->userApi->hasUsers() === false) {
            return null;
        }

        return Response::createSeeOther($this->urlService->createApplicationUrl());
    }
}
