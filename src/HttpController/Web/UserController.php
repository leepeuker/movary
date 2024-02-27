<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
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
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false
            && $this->authenticationService->getCurrentUser()->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        try {
            $this->userApi->createUser(
                $requestUserData['email'],
                $requestUserData['password'],
                $requestUserData['name'],
                $requestUserData['isAdmin'],
            );
        } catch (EmailNotUnique) {
            return Response::createBadRequest('Email already in use.');
        } catch (UsernameNotUnique) {
            return Response::createBadRequest('Name already in use.');
        } catch (PasswordTooShort) {
            return Response::createBadRequest('Password too short.');
        } catch (UsernameInvalidFormat) {
            return Response::createBadRequest('Name is not in a valid format.');
        }

        return Response::createOk();
    }

    public function deleteUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->authenticationService->getCurrentUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $this->userApi->deleteUser($userId);

        return Response::createOk();
    }

    public function fetchUsers() : Response
    {
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === false
            && $this->authenticationService->getCurrentUser()->isAdmin() === false) {
            return Response::createForbidden();
        }

        return Response::createJson(Json::encode($this->userApi->fetchAll()));
    }

    public function updateUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->authenticationService->getCurrentUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        try {
            $this->userApi->updateName($userId, $requestUserData['name']);
            $this->userApi->updateEmail($userId, $requestUserData['email']);
            $this->userApi->updateIsAdmin($userId, $requestUserData['isAdmin']);

            if ($requestUserData['password'] !== null) {
                $this->userApi->updatePassword($userId, $requestUserData['password']);
            }
        } catch (EmailNotUnique) {
            return Response::createBadRequest('Email already in use.');
        } catch (UsernameNotUnique) {
            return Response::createBadRequest('Name already in use.');
        } catch (PasswordTooShort) {
            return Response::createBadRequest('Password too short.');
        } catch (UsernameInvalidFormat) {
            return Response::createBadRequest('Name is not in a valid format.');
        }

        return Response::createOk();
    }
}
