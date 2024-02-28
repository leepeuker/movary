<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Middleware;

use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Middleware\MiddlewareInterface;
use Movary\ValueObject\Http\Response;

class UserHasJellyfinToken implements MiddlewareInterface
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function __invoke() : ?Response
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($this->authenticationService->getCurrentUserId());

        if ($jellyfinAuthentication !== null) {
            return null;
        }

        return Response::createBadRequest(JellyfinInvalidAuthentication::create()->getMessage());
    }
}
