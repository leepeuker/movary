<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Exception;
use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordInvalidEncoding;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\AdminUserRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class AdminUserController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authentication,
        private readonly EmailVerificationRequestService $emailVerificationRequestService,
        private readonly AdminUserRequestMapper $adminUserRequestMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function getUsers(Request $request) : Response
    {
        $page = $this->requestMapper->mapPage($request);
        $limit = $this->requestMapper->mapLimit($request);
        $role = $request->getGetParameters()['role'] ?? null;
        $isAdmin = match ($role) {
            null => null,
            'admin' => true,
            'user' => false,
            default => throw InvalidApiRequest::invalidField('role', 'must be admin or user'),
        };
        $users = array_map(
            static fn(array $user) : array => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'pendingEmail' => $user['pendingEmail'],
                'isAdmin' => (bool)$user['isAdmin'],
                'isEnabled' => (bool)$user['isEnabled'],
            ],
            $this->userApi->fetchAllPaginated($limit, ($page - 1) * $limit, $isAdmin),
        );

        return Response::createSensitiveJson(Json::encode([
            'users' => $users,
            'page' => $page,
            'limit' => $limit,
            'total' => $this->userApi->countUsers($isAdmin),
        ]));
    }

    public function createUser(Request $request) : Response
    {
        $data = $this->adminUserRequestMapper->mapCreate($request);
        try {
            $this->userApi->createUser(
                $data['email'],
                $data['password'],
                $data['name'],
                $data['isAdmin'],
                $data['isEnabled'],
                $data['emailVerified'],
            );
        } catch (EmailNotUnique | PasswordInvalidEncoding | PasswordTooShort | UsernameInvalidFormat | UsernameNotUnique $exception) {
            $this->throwValidationError($exception);
        }

        $user = $this->userApi->fetchUserByName($data['name']);

        return Response::createSensitiveJson(Json::encode(['user' => $this->mapUser($user)]), StatusCode::createCreated());
    }

    public function getUser(Request $request) : Response
    {
        $user = $this->userApi->findUserById((int)$request->getRouteParameters()['userId']);
        if ($user === null) {
            return Response::createNotFound();
        }

        return Response::createSensitiveJson(Json::encode(['user' => $this->mapUser($user)]));
    }

    public function updateUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        $user = $this->userApi->findUserById($userId);
        if ($user === null) {
            return Response::createNotFound();
        }

        $data = $this->adminUserRequestMapper->mapUpdate($request);
        $adminId = $this->authentication->requireApiToken($request)->getUserId();
        if (($data['isEnabled'] ?? true) === false && $adminId === $userId) {
            throw InvalidApiRequest::invalidField('isEnabled', 'cannot disable your own account');
        }

        try {
            $this->applyUserUpdates($userId, $data);
        } catch (EmailNotUnique | PasswordInvalidEncoding | PasswordTooShort | UsernameInvalidFormat | UsernameNotUnique $exception) {
            $this->throwValidationError($exception);
        }

        return Response::createNoContent();
    }

    public function deleteUser(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];
        if ($this->userApi->findUserById($userId) === null) {
            return Response::createNotFound();
        }

        $this->userApi->deleteUser($userId);

        return Response::createNoContent();
    }

    /** @param array{email?: string, password?: string, name?: string, isAdmin?: bool, isEnabled?: bool} $data */
    private function applyUserUpdates(int $userId, array $data) : void
    {
        if (isset($data['name'])) {
            $this->userApi->updateName($userId, $data['name']);
        }
        if (isset($data['email'])) {
            $verificationRequested = $this->userApi->updateEmail($userId, $data['email']);
            if ($verificationRequested === true) {
                $updatedUser = $this->userApi->fetchUser($userId);
                if ($this->emailVerificationRequestService->requestForUser($updatedUser) === false) {
                    throw InvalidApiRequest::invalidField('email', 'change is pending, but the verification email could not be scheduled; the current email remains active');
                }
            }
        }
        if (isset($data['isAdmin'])) {
            $this->userApi->updateIsAdmin($userId, $data['isAdmin']);
        }
        if (isset($data['password'])) {
            $this->userApi->updatePassword($userId, $data['password']);
        }
        if (isset($data['isEnabled'])) {
            $this->userApi->updateIsEnabled($userId, $data['isEnabled']);
        }

    }

    /** @return array{id: int, name: string, email: string, pendingEmail: ?string, isAdmin: bool, isEnabled: bool, emailVerified: bool} */
    private function mapUser(UserEntity $user) : array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'pendingEmail' => $user->getPendingEmail(),
            'isAdmin' => $user->isAdmin(),
            'isEnabled' => $user->isEnabled(),
            'emailVerified' => $user->isEmailVerified(),
        ];
    }

    private function throwValidationError(Exception $exception) : never
    {
        if ($exception instanceof EmailNotUnique) {
            throw InvalidApiRequest::invalidField('email', 'must be unique');
        }
        if ($exception instanceof UsernameNotUnique) {
            throw InvalidApiRequest::invalidField('name', 'must be unique');
        }
        if ($exception instanceof UsernameInvalidFormat) {
            throw InvalidApiRequest::invalidField('name', 'must contain only letters and digits');
        }

        throw InvalidApiRequest::invalidField('password', 'does not meet the password requirements');
    }
}
