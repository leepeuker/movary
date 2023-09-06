<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Http\Response;

class UserHasJellyfinToken
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function main() : ?Response
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($this->authenticationService->getCurrentUserId());
        if ($jellyfinAuthentication === null) {
            return Response::createBadRequest(JellyfinInvalidAuthentication::create()->getMessage());
        }

        return null;
    }
}
