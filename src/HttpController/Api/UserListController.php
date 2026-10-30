<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserListController
{
    public function __construct(
        private readonly Authentication $authentication,
        private readonly UserApi $userApi,
    ) {
    }

    public function getUsers(Request $request) : Response
    {
        $authenticatedUser = $this->authentication->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            return Response::createBearerUnauthorized();
        }

        $viewer = $this->userApi->findUserById($authenticatedUser->getUserId());
        if ($viewer === null) {
            return Response::createBearerUnauthorized();
        }

        $usernames = array_column($this->userApi->fetchAllInternVisibleUsernames(), 'name');
        if (in_array($viewer->getName(), $usernames, true) === false) {
            $usernames[] = $viewer->getName();
        }
        sort($usernames, SORT_STRING);

        return Response::createSensitiveJson(Json::encode(['users' => $usernames]));
    }
}
