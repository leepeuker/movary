<?php declare(strict_types=1);

namespace Movary\HttpController\Rest;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function createUser(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false
            && $this->authenticationService->getCurrentUser()->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        $this->userApi->createUser(
            $requestUserData['email'],
            $requestUserData['password'],
            $requestUserData['name'],
            $requestUserData['isAdmin'],
        );

        return Response::createOk();
    }

    public function deleteUser(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->authenticationService->getCurrentUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $this->userApi->deleteUser($userId);

        return Response::createOk();
    }

    public function updateUser(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createForbidden();
        }

        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->authenticationService->getCurrentUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        $this->userApi->updateName($userId, $requestUserData['name']);
        $this->userApi->updateEmail($userId, $requestUserData['email']);
        $this->userApi->updateIsAdmin($userId, $requestUserData['isAdmin']);

        return Response::createOk();
    }
}
