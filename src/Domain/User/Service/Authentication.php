<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotFound;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use Movary\HttpController\Web\CreateUserController;
use Movary\Service\ServerSettings;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class Authentication
{
    private const string AUTHENTICATION_COOKIE_NAME = 'id';

    private const int MAX_EXPIRATION_AGE_IN_DAYS = 30;

    /** @var array<string, int> */
    private array $validatedAuthTokenUserIds = [];

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserApi $userApi,
        private readonly SessionWrapper $sessionWrapper,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly ServerSettings $serverSettings,
        private readonly Request $request,
    ) {
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
        $token = $this->getAuthenticationCookie();
        $userId = $token === null ? null : $this->findUserIdByValidAuthToken($token);

        if ($userId === null) {
            throw new RuntimeException('Could not find a current user');
        }

        return $userId;
    }

    public function getToken(Request $request) : ?string
    {
        $tokenInCookie = $this->getAuthenticationCookie();
        if ($tokenInCookie !== null) {
            return $tokenInCookie;
        }

        return $request->getHeaders()['X-Movary-Token'] ?? null;
    }

    public function getUserIdByToken(Request $request) : ?int
    {
        $token = $this->getToken($request);
        if ($token === null) {
            return null;
        }

        $apiTokenUserId = $this->userApi->findUserIdByApiToken($token);
        if ($apiTokenUserId !== null) {
            return $apiTokenUserId;
        }

        return $this->findUserIdByValidAuthToken($token);
    }

    public function isUserAuthenticatedWithCookie() : bool
    {
        $token = $this->getAuthenticationCookie();

        if ($token !== null && $this->findUserIdByValidAuthToken($token) !== null) {
            return true;
        }

        if ($token !== null) {
            $this->clearAuthenticationCookie();
        }

        return false;
    }

    public function isUserPageVisibleForApiRequest(Request $request, UserEntity $targetUser) : bool
    {
        $requestUserId = $this->getUserIdByToken($request);

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
        return match (true) {
            $this->isValidApiToken($token) => true,
            $this->isValidAuthToken($token) => true,
            default => false,
        };
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

        $this->setAuthenticationCookieAndNewSession($token, $authTokenExpirationDate);

        return $userAndToken;
    }

    public function logout() : void
    {
        $token = $this->getAuthenticationCookie();

        if ($token !== null) {
            $this->deleteToken($token);
            $this->clearAuthenticationCookie();
        }

        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
    }

    public function setAuthenticationCookieAndNewSession(string $token, DateTime $expirationDate) : void
    {
        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
        $_COOKIE[self::AUTHENTICATION_COOKIE_NAME] = $token;
        setcookie(
            self::AUTHENTICATION_COOKIE_NAME,
            $token,
            [
                'expires' => (int)$expirationDate->format('U'),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $this->isAuthenticationCookieSecure(),
            ],
        );
    }

    private function clearAuthenticationCookie() : void
    {
        unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
        setcookie(
            self::AUTHENTICATION_COOKIE_NAME,
            '',
            [
                'expires' => 1,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $this->isAuthenticationCookieSecure(),
            ],
        );
    }

    public function isAuthenticationCookieSecure() : bool
    {
        if ($this->request->isHttps() === true) {
            return true;
        }

        return str_starts_with(strtolower($this->serverSettings->getApplicationUrl() ?? ''), 'https://');
    }

    private function findUserIdByValidAuthToken(string $token) : ?int
    {
        if (isset($this->validatedAuthTokenUserIds[$token]) === true) {
            return $this->validatedAuthTokenUserIds[$token];
        }

        $tokenData = $this->repository->findAuthTokenData($token);
        if ($tokenData === null) {
            return null;
        }

        if ($tokenData['expirationDate']->isAfter(DateTime::create()) === false) {
            $this->repository->deleteAuthToken($token);

            return null;
        }

        $this->validatedAuthTokenUserIds[$token] = $tokenData['userId'];

        return $tokenData['userId'];
    }

    private function getAuthenticationCookie() : ?string
    {
        $token = $_COOKIE[self::AUTHENTICATION_COOKIE_NAME] ?? null;

        return is_string($token) === true && $token !== '' ? $token : null;
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

    private function isValidApiToken(string $token) : bool
    {
        return $this->userApi->findUserIdByApiToken($token) !== null;
    }

    private function isValidAuthToken(string $token) : bool
    {
        return $this->findUserIdByValidAuthToken($token) !== null;
    }

    private function setAuthenticationToken(int $userId, string $deviceName, string $userAgent, DateTime $expirationDate) : string
    {
        $token = bin2hex(random_bytes(16));

        $this->repository->createAuthToken($userId, $token, $deviceName, $userAgent, $expirationDate);

        return $token;
    }
}
