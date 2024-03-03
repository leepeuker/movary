<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserHasJellyfinToken implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function __invoke(Request $request) : ?Response
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($this->authenticationService->getCurrentUserId());

        if ($jellyfinAuthentication !== null) {
            return null;
        }

        return Response::createBadRequest(JellyfinInvalidAuthentication::create()->getMessage());
    }
}
