<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserCanViewUser
{
    public function __construct(
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function main(Request $request) : ?Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createSeeOther('/');
        }

        return null;
    }
}
