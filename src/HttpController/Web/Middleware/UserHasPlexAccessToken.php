<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Api\Plex\Exception\PlexAuthenticationMissing;
use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Response;

class UserHasPlexAccessToken
{
    public function __construct(
        private readonly Authentication $authenticationService,
    ) {
    }

    public function main() : ?Response
    {
        if ($this->authenticationService->getCurrentUser()->getPlexAccessToken() === null) {
            return Response::createBadRequest(PlexAuthenticationMissing::create()->getMessage());
        }

        return null;
    }
}
