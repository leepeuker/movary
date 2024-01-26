<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotFound;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\NoVerificationCode;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
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

    public function deleteToken(string $token) : void
    {
        $this->repository->deleteAuthToken($token);
    }

    public function getCurrentUser() : UserEntity
    {
        return $this->userApi->fetchUser($this->getCurrentUserId());
    }

    public function getCurrentUserId() : int
    {
        $userId = $this->sessionWrapper->find('userId');
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);

        if ($userId === null && $token !== null) {
            $userId = $this->repository->findUserIdByAuthToken((string)$token);
            $this->sessionWrapper->set('userId', $userId);
        }

        if ($userId === null) {
            throw new RuntimeException('Could not find a current user');
        }

        return $userId;
    }

    public function getUserIdByApiToken(Request $request) : ?int
    {
        $apiToken = $request->getHeaders()['X-Auth-Token'] ?? null;
        if ($apiToken === null) {
            return null;
        }

        return $this->userApi->findByApiToken($apiToken)?->getId();
    }

    public function isUserAuthenticated() : bool
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);

        if (empty($token) === false && $this->isValidToken((string)$token) === true) {
            return true;
        }

        if (empty($token) === false) {
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
        }

        return false;
    }

    public function isUserPageVisibleForCurrentUser(int $privacyLevel, int $userId) : bool
    {
        if ($privacyLevel === 2) {
            return true;
        }

        if ($privacyLevel === 1 && $this->isUserAuthenticated() === true) {
            return true;
        }

        return $this->isUserAuthenticated() === true && $this->getCurrentUserId() === $userId;
    }

    public function isUserPageVisibleForApiRequest(Request $request, UserEntity $targetUser) : bool
    {
        $userId = $this->getUserIdByApiToken($request);

        $privacyLevel = $targetUser->getPrivacyLevel();

        if ($privacyLevel === 2) {
            return true;
        }

        if ($privacyLevel === 1 && $userId !== null) {
            return true;
        }

        return $targetUser->getId() === $userId;
    }

    public function login(string $email, string $password, bool $rememberMe, string $deviceName, string $userAgent, ?int $userTotpInput = null) : void
    {
        if ($this->isUserAuthenticated() === true) {
            return;
        }

        $user = $this->repository->findUserByEmail($email);

        if ($user === null) {
            throw EmailNotFound::create();
        }

        if ($this->userApi->isValidPassword($user->getId(), $password) === false) {
            throw InvalidPassword::create();
        }

        $totpUri = $this->userApi->findTotpUri($user->getId());

        if ($totpUri !== null) {
            if(empty($userTotpInput)) {
                throw NoVerificationCode::create();
            }
            if($this->verifyTotp($user->getId(), $userTotpInput) === false) {
                throw InvalidTotpCode::create();
            }
        }

        $this->createAuthenticationCookie($user->getId(), $rememberMe, $deviceName, $userAgent);
    }

    public function createAuthenticationCookie(int $userId, bool $rememberMe, string $deviceName, string $userAgent) : void
    {
        $authTokenExpirationDate = $this->createExpirationDate();
        $cookieExpiration = 0;

        if ($rememberMe === true) {
            $authTokenExpirationDate = $this->createExpirationDate(self::MAX_EXPIRATION_AGE_IN_DAYS);
            $cookieExpiration = (int)$authTokenExpirationDate->format('U');
        }

        $token = $this->generateToken($userId, $deviceName, $userAgent, DateTime::createFromString((string)$authTokenExpirationDate));

        session_regenerate_id();
        setcookie(self::AUTHENTICATION_COOKIE_NAME, $token, [
            'expires' => $cookieExpiration,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'strict'
        ]);

        $this->sessionWrapper->set('userId', $userId);
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

    public function createExpirationDate(int $days = 1) : DateTime
    {
        $timestamp = strtotime('+' . $days . ' day');

        if ($timestamp === false) {
            throw new RuntimeException('Could not generate timestamp for auth token expiration date.');
        }

        return DateTime::createFromString(date('Y-m-d H:i:s', $timestamp));
    }

    private function generateToken(int $userId, string $deviceName, string $userAgent, ?DateTime $expirationDate = null) : string
    {
        if ($expirationDate === null) {
            $expirationDate = $this->createExpirationDate();
        }

        $token = bin2hex(random_bytes(16));

        $this->repository->createAuthToken($userId, $token, $deviceName, $userAgent, $expirationDate);

        return $token;
    }

    public function isValidToken(string $token) : bool
    {
        $tokenExpirationDate = $this->repository->findAuthTokenExpirationDate($token);

        if ($tokenExpirationDate === null || $tokenExpirationDate->isAfter(DateTime::create()) === false) {
            if ($tokenExpirationDate !== null) {
                $this->repository->deleteAuthToken($token);
            }

            return false;
        }

        return true;
    }

    public function getToken() : ?string
    {
        return $_COOKIE[self::AUTHENTICATION_COOKIE_NAME];
    }


    private function verifyTotp(int $userId, int $userTotpInput) : bool
    {
        if ($this->twoFactorAuthenticationApi->verifyTotpUri($userId, $userTotpInput) === false) {
            return false;
        }
        return true;
    }
}
