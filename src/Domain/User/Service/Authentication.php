<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotFound;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Exception\InvalidTotpCode;
use Movary\Domain\User\Exception\MissingTotpCode;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Web\CreateUserController;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Util\Cookie;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class Authentication
{
    public const string AUTHENTICATION_COOKIE_NAME = 'id';

    private const int MAX_EXPIRATION_AGE_IN_DAYS = 30;

    /** @var array<string, int> */
    private array $validatedAuthTokenUserIds = [];

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserApi $userApi,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
        private readonly Cookie $cookie,
        private readonly FlashMessageService $flashMessageService,
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

    public function authenticateApiToken(Request $request) : ?AuthenticatedUser
    {
        $token = $request->getHeader('X-Movary-Token');
        if ($token === null || $token === '') {
            return null;
        }

        $userId = $this->userApi->findUserIdByApiToken($token);
        if ($userId === null) {
            return null;
        }

        return AuthenticatedUser::create($userId, CredentialType::API_TOKEN);
    }

    public function authenticateWebSession() : ?AuthenticatedUser
    {
        $token = $this->getAuthenticationCookie();
        if ($token === null) {
            return null;
        }

        $userId = $this->findUserIdByValidAuthToken($token);
        if ($userId === null) {
            $this->clearAuthenticationCookie();

            return null;
        }

        return AuthenticatedUser::create($userId, CredentialType::WEB_SESSION);
    }

    public function requireWebSession() : AuthenticatedUser
    {
        $authenticatedUser = $this->authenticateWebSession();
        if ($authenticatedUser === null) {
            throw new RuntimeException('Could not find an authenticated web session');
        }

        return $authenticatedUser;
    }

    public function deleteToken(string $token) : void
    {
        $this->repository->deleteAuthToken($this->hashToken($token));
    }

    public function findUserAndVerifyAuthentication(
        string $email,
        string $password,
        ?int $userTotpCode = null,
    ) : UserEntity {
        $attemptId = $this->loginAttemptLimiter->reserveAttempt($email);

        $user = $this->repository->findUserByEmail($email);

        if ($user === null) {
            throw EmailNotFound::create();
        }

        if ($this->userApi->isValidPassword($user->getId(), $password) === false) {
            throw InvalidPassword::create();
        }

        $totpUri = $this->userApi->findTotpUri($user->getId());
        if ($totpUri === null) {
            $this->loginAttemptLimiter->resetAccountAttempts($email);

            return $user;
        }

        if ($userTotpCode === null) {
            $this->loginAttemptLimiter->releaseAttempt($attemptId);

            throw MissingTotpCode::create();
        }

        if ($this->twoFactorAuthenticationApi->verifyTotpUri($user->getId(), $userTotpCode) === false) {
            throw InvalidTotpCode::create();
        }

        $this->loginAttemptLimiter->resetAccountAttempts($email);

        return $user;
    }

    public function getCurrentUser() : UserEntity
    {
        return $this->userApi->fetchUser($this->getCurrentUserId());
    }

    public function getCurrentUserId() : int
    {
        return $this->requireWebSession()->getUserId();
    }

    public function getToken(Request $request) : ?string
    {
        $tokenInCookie = $this->getAuthenticationCookie();
        if ($tokenInCookie !== null) {
            return $tokenInCookie;
        }

        return $this->getTokenFromHeader($request);
    }

    public function getTokenFromHeader(Request $request) : ?string
    {
        $token = $request->getHeader('X-Movary-Token');

        return $token === '' ? null : $token;
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

    public function getUserIdByTokenFromHeader(Request $request) : ?int
    {
        $token = $this->getTokenFromHeader($request);
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
        return $this->authenticateWebSession() !== null;
    }

    public function isUserPageVisibleForApiRequest(Request $request, UserEntity $targetUser) : bool
    {
        $requestUserId = $this->getUserIdByToken($request);

        return $this->isUserPageVisibleForUser($targetUser, $requestUserId);
    }

    public function isUserPageVisible(UserEntity $targetUser, ?AuthenticatedUser $authenticatedUser) : bool
    {
        return $this->isUserPageVisibleForUser($targetUser, $authenticatedUser?->getUserId());
    }

    public function isUserPageVisibleForWebRequest(UserEntity $targetUser) : bool
    {
        return $this->isUserPageVisible($targetUser, $this->authenticateWebSession());
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

        $this->setAuthenticationCookie($token, $authTokenExpirationDate);

        return $userAndToken;
    }

    public function logout() : void
    {
        $token = $this->getAuthenticationCookie();

        if ($token !== null) {
            $this->deleteToken($token);
            $this->clearAuthenticationCookie();
        }

        $this->flashMessageService->clear();
    }

    public function setAuthenticationCookie(string $token, DateTime $expirationDate) : void
    {
        $this->cookie->set(
            self::AUTHENTICATION_COOKIE_NAME,
            $token,
            (int)$expirationDate->format('U'),
        );
    }

    private function clearAuthenticationCookie() : void
    {
        $this->cookie->delete(self::AUTHENTICATION_COOKIE_NAME);
    }

    private function findUserIdByValidAuthToken(string $token) : ?int
    {
        if (isset($this->validatedAuthTokenUserIds[$token]) === true) {
            return $this->validatedAuthTokenUserIds[$token];
        }

        $tokenHash = $this->hashToken($token);
        $tokenData = $this->repository->findAuthTokenData($tokenHash);
        if ($tokenData === null) {
            return null;
        }

        if ($tokenData['expirationDate']->isAfter(DateTime::create()) === false) {
            $this->repository->deleteAuthToken($tokenHash);

            return null;
        }

        $this->validatedAuthTokenUserIds[$token] = $tokenData['userId'];

        return $tokenData['userId'];
    }

    private function getAuthenticationCookie() : ?string
    {
        return $this->cookie->find(self::AUTHENTICATION_COOKIE_NAME);
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

        $this->repository->createAuthToken($userId, $this->hashToken($token), $deviceName, $userAgent, $expirationDate);

        return $token;
    }

    private function hashToken(string $token) : string
    {
        return hash('sha256', $token);
    }
}
