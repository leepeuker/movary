<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordInvalidEncoding;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class UserController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly CurrentWebUser $currentWebUser,
        private readonly EmailVerificationRequestService $emailVerificationRequestService,
    ) {
    }

    public function createUser(Request $request) : Response
    {
        $currentUser = $this->currentWebUser->findUser();
        if ($currentUser === null || $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        try {
            $this->userApi->createUser(
                $requestUserData['email'],
                $requestUserData['password'],
                $requestUserData['name'],
                $requestUserData['isAdmin'],
                emailVerified: true,
            );
        } catch (EmailNotUnique) {
            return Response::createBadRequest('Email already in use.');
        } catch (UsernameNotUnique) {
            return Response::createBadRequest('Name already in use.');
        } catch (PasswordTooShort) {
            return Response::createBadRequest('Password too short.');
        } catch (PasswordInvalidEncoding) {
            return Response::createBadRequest('Password must contain valid Unicode text.');
        } catch (UsernameInvalidFormat) {
            return Response::createBadRequest('Name is not in a valid format.');
        }

        return Response::createOk();
    }

    public function deleteUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->currentWebUser->requireUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $this->userApi->deleteUser($userId);

        return Response::createOk();
    }

    public function fetchUsers() : Response
    {
        $currentUser = $this->currentWebUser->findUser();
        if ($currentUser === null || $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        return Response::createJson(Json::encode($this->userApi->fetchAll()));
    }

    public function updateUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $currentUser = $this->currentWebUser->requireUser();

        if ($currentUser->getId() !== $userId && $currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $requestUserData = Json::decode($request->getBody());

        $statusValidationResponse = $this->validateAccountStatusUpdate($currentUser, $userId, $requestUserData);
        if ($statusValidationResponse !== null) {
            return $statusValidationResponse;
        }

        try {
            $this->userApi->updateName($userId, $requestUserData['name']);
            $verificationRequested = $this->userApi->updateEmail($userId, $requestUserData['email']);
            $this->userApi->updateIsAdmin($userId, $requestUserData['isAdmin']);

            if ($requestUserData['password'] !== null) {
                $this->userApi->updatePassword($userId, $requestUserData['password']);
            }
            if (array_key_exists('isEnabled', $requestUserData) === true) {
                $this->userApi->updateIsEnabled($userId, $requestUserData['isEnabled']);
            }
        } catch (EmailNotUnique) {
            return Response::createBadRequest('Email already in use.');
        } catch (UsernameNotUnique) {
            return Response::createBadRequest('Name already in use.');
        } catch (PasswordTooShort) {
            return Response::createBadRequest('Password too short.');
        } catch (PasswordInvalidEncoding) {
            return Response::createBadRequest('Password must contain valid Unicode text.');
        } catch (UsernameInvalidFormat) {
            return Response::createBadRequest('Name is not in a valid format.');
        }

        return $this->scheduleEmailVerificationIfRequested($userId, $verificationRequested);
    }

    public function resendPendingEmailVerification(Request $request) : Response
    {
        $currentUser = $this->currentWebUser->requireUser();
        if ($currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $user = $this->userApi->findUserById((int)$request->getRouteParameters()['userId']);
        if ($user === null || $user->getPendingEmail() === null) {
            return Response::createBadRequest('There is no pending email change for this user.');
        }
        if ($this->emailVerificationRequestService->requestForUser($user) === false) {
            return Response::createBadRequest('The verification email could not be scheduled.');
        }

        return Response::createOk();
    }

    public function cancelPendingEmailChange(Request $request) : Response
    {
        $currentUser = $this->currentWebUser->requireUser();
        if ($currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }

        $this->userApi->cancelPendingEmailChange((int)$request->getRouteParameters()['userId']);

        return Response::createOk();
    }

    private function validateAccountStatusUpdate(
        UserEntity $currentUser,
        int $targetUserId,
        array $requestUserData,
    ) : ?Response {
        if (array_key_exists('isEnabled', $requestUserData) === false) {
            return null;
        }
        if ($currentUser->isAdmin() === false) {
            return Response::createForbidden();
        }
        if ($currentUser->getId() === $targetUserId && $requestUserData['isEnabled'] === false) {
            return Response::createBadRequest('You cannot disable your own account.');
        }

        return null;
    }

    private function scheduleEmailVerificationIfRequested(int $userId, bool $verificationRequested) : Response
    {
        if ($verificationRequested === false) {
            return Response::createOk();
        }

        $user = $this->userApi->findUserById($userId);
        if ($user === null || $this->emailVerificationRequestService->requestForUser($user) === false) {
            return Response::createBadRequest(
                'Email change is pending, but the verification email could not be scheduled. '
                . 'The current email remains active.',
            );
        }

        return Response::createOk();
    }
}
