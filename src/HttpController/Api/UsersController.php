<?php

namespace Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

readonly class UsersController
{
    public function __construct(
        private UserPageAuthorizationChecker $userPageAuthorizationChecker
    ) {}

    public function getVisibleUsers(Request $request) : Response
    {
        $visibleUsers = $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor($request);
        if(empty($visibleUsers) === true) {
            return Response::createNoContent();
        }
        return Response::createJson(
            Json::encode(
                $visibleUsers
            )
        );
    }
}
