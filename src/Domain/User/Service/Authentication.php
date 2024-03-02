<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotFound;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use Movary\HttpController\Api\ValueObject\AuthenticationObject;
use Movary\HttpController\Web\CreateUserController;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class Authentication
{
    private const AUTHENTICATION_COOKIE_NAME = 'id';

    private const MAX_EXPIRATION_AGE_IN_DAYS = 30;

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
    ) {
    }

    public function createAuthenticationObjectFromCookie() : ?AuthenticationObject
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);
        $authenticationMethod = AuthenticationObject::COOKIE_AUTHENTICATION;
        if (empty($token) === true || $this->isValidToken((string)$token) === true) {
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
            return null;
        }
        $user = $this->userApi->findByToken($token);
        if(empty($user) === true) {
            return null;
        }
        return AuthenticationObject::createAuthenticationObject($token, $authenticationMethod, $user);
    }

    public function createAuthenticationObjectDynamically(Request $request) : ?AuthenticationObject
    {
        $token =  $request->getHeaders()['X-Movary-Token'] ?? filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);
        $authenticationMethod = AuthenticationObject::COOKIE_AUTHENTICATION;
        if (empty($token) === true || $this->isValidToken($token) === false) {
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
            $authenticationMethod = AuthenticationObject::HEADER_AUTHENTICATION;
        }
        $user = $this->userApi->findByToken($token);
        if(empty($user) === true) {
            return null;
        }
        return AuthenticationObject::createAuthenticationObject($token, $authenticationMethod, $user);
    }

    public function createAuthenticationObjectFromHeader(Request $request) : ?AuthenticationObject
    {
        $token = $request->getHeaders()['X-Movary-Token'] ?? null;
        $authenticationMethod = AuthenticationObject::HEADER_AUTHENTICATION;
        if (empty($token) === true || $this->isValidToken((string)$token) === false) {
            return null;
        }
        $user = $this->userApi->findByToken($token);
        if(empty($user) === true) {
            return null;
        }
        return AuthenticationObject::createAuthenticationObject($token, $authenticationMethod, $user);
    }

    public function createExpirationDate(int $days = 1) : DateTime
    {
        $timestamp = strtotime('+' . $days . ' day');

        if ($timestamp === false) {
            throw new RuntimeException('Could not generate timestamp for auth token expiration date.');
        }

        return DateTime::createFromString(date('Y-m-d H:i:s', $timestamp));
    }

    public function deleteToken(string $token) : void
    {
        $this->repository->deleteAuthToken($token);
    }

    public function findUserAndVerifyAuthentication(
        string $email,
        string $password,
        ?int $userTotpCode = null,
    ) : UserEntity {
        $user = $this->repository->findUserByEmail($email);

        if ($user === null) {
            throw EmailNotFound::create();
        }

        if ($this->userApi->isValidPassword($user->getId(), $password) === false) {
            throw InvalidPassword::create();
        }

        $totpUri = $this->userApi->findTotpUri($user->getId());
        if ($totpUri === null) {
            return $user;
        }

        if ($userTotpCode === null) {
            throw MissingTotpCode::create();
        }

        if ($this->twoFactorAuthenticationApi->verifyTotpUri($user->getId(), $userTotpCode) === false) {
            throw InvalidTotpCode::create();
        }

        return $user;
    }

    public function getCurrentUser() : UserEntity
    {
        return $this->userApi->fetchUser($this->getCurrentUserId());
    }

    public function getCurrentUserId() : int
    {
        $userId = $this->sessionWrapper->find('userId');

        if ($userId === null) {

            $authenticationObject = $this->createAuthenticationObjectFromCookie();
            if($authenticationObject === null) {
                throw new RuntimeException('Could not find a current user');
            }
            $this->sessionWrapper->set('userId', $authenticationObject->getUser()->getId());
        }

        if ($userId === null) {
            throw new RuntimeException('Could not find a current user');
        }
        return $userId;
    }

    public function getToken(Request $request) : ?string
    {
        $authenticationObject = $this->createAuthenticationObjectDynamically($request);

        return $authenticationObject?->getToken();
    }

    public function getUserIdByApiToken(Request $request) : ?int
    {
        $apiToken = $this->getToken($request);
        if ($apiToken === null) {
            return null;
        }

        if ($this->isValidToken($apiToken) === false) {
            return null;
        }

        return $this->userApi->findByToken($apiToken)?->getId();
    }

    public function isUserAuthenticatedWithCookie() : bool
    {
        $authenticationObject = $this->createAuthenticationObjectFromCookie();
        if($authenticationObject === null) {
            return false;
        }
        return true;
    }

    public function isUserPageVisibleForApiRequest(Request $request, UserEntity $targetUser) : bool
    {
        $requestUserId = $this->getUserIdByApiToken($request);

        return $this->isUserPageVisibleForUser($targetUser, $requestUserId);
    }

    public function isUserPageVisibleForWebRequest(UserEntity $targetUser) : bool
    {
        $requestUserId = null;
        if ($this->isUserAuthenticatedWithCookie() === true) {
            $requestUserId = $this->getCurrentUserId();
        }

        return $this->isUserPageVisibleForUser($targetUser, $requestUserId);
    }

    public function isValidToken(string $token) : bool
    {
        $tokenExpirationDate = $this->repository->findAuthTokenExpirationDate($token);

        if ($tokenExpirationDate === null) {
            if($this->repository->findUserByToken($token) === null) {
                return false;
            }
        } else {
            if($tokenExpirationDate->isAfter(DateTime::create()) === false) {
                $this->repository->deleteAuthToken($token);
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{user: UserEntity, token: string}
     */
    public function login(
        string $email,
        string $password,
        bool $rememberMe,
        string $deviceName,
        string $userAgent,
        ?int $userTotpInput = null,
    ) : array {
        $user = $this->findUserAndVerifyAuthentication($email, $password, $userTotpInput);

        $authTokenExpirationDate = $this->createExpirationDate();
        if ($rememberMe === true) {
            $authTokenExpirationDate = $this->createExpirationDate(self::MAX_EXPIRATION_AGE_IN_DAYS);
        }

        $token = $this->setAuthenticationToken($user->getId(), $deviceName, $userAgent, $authTokenExpirationDate);

        $userAndToken = ['user' => $user, 'token' => $token];

        if ($deviceName !== CreateUserController::MOVARY_WEB_CLIENT) {
            return $userAndToken;
        }

        $this->setAuthenticationCookieAndNewSession($user->getId(), $token, $authTokenExpirationDate);

        return $userAndToken;
    }

    public function logout() : void
    {
        $token = filter_input(INPUT_COOKIE, 'id');

        if ($token !== null) {
            $this->deleteToken((string)$token);
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
        }

        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
    }

    public function setAuthenticationCookieAndNewSession(int $userId, string $token, DateTime $expirationDate) : void
    {
        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
        setcookie(self::AUTHENTICATION_COOKIE_NAME, $token, [
            'expires' => (int)$expirationDate->format('U'),
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'strict'
        ]);

        $this->sessionWrapper->set('userId', $userId);
    }

    private function isUserPageVisibleForUser(UserEntity $targetUser, ?int $requestUserId) : bool
    {
        $privacyLevel = $targetUser->getPrivacyLevel();

        if ($privacyLevel === 2) {
            return true;
        }

        if ($privacyLevel === 1 && $requestUserId !== null) {
            return true;
        }

        return $targetUser->getId() === $requestUserId;
    }

    private function setAuthenticationToken(int $userId, string $deviceName, string $userAgent, DateTime $expirationDate) : string
    {
        $token = bin2hex(random_bytes(16));

        $this->repository->createAuthToken($userId, $token, $deviceName, $userAgent, $expirationDate);

        return $token;
    }
}
