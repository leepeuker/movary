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
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Util\Cookie;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class Authentication
{
    public const string AUTHENTICATION_COOKIE_NAME = 'id';

    private const int MAX_EXPIRATION_AGE_IN_DAYS = 30;

    private const string WEB_SESSION_DEVICE_NAME = 'Movary Web';

    private bool $apiTokenAuthenticationResolved = false;

    private ?AuthenticatedUser $authenticatedApiUser = null;

    private bool $webSessionAuthenticationResolved = false;

    private ?AuthenticatedUser $authenticatedWebUser = null;

    public function __construct(
        private readonly UserRepository $repository,
        private readonly UserApi $userApi,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
        private readonly Cookie $cookie,
        private readonly FlashMessageService $flashMessageService,
    ) {
    }

    private function createExpirationDate(int $days = 1) : DateTime
    {
        $timestamp = strtotime('+' . $days . ' day');

        if ($timestamp === false) {
            throw new RuntimeException('Could not generate timestamp for auth token expiration date.');
        }

        return DateTime::createFromString(date('Y-m-d H:i:s', $timestamp));
    }

    public function authenticateApiToken(Request $request) : ?AuthenticatedUser
    {
        if ($this->apiTokenAuthenticationResolved === true) {
            return $this->authenticatedApiUser;
        }

        $this->apiTokenAuthenticationResolved = true;

        $token = $request->getHeader('X-Movary-Token');
        if ($token === null || $token === '') {
            return null;
        }

        $userId = $this->userApi->findUserIdByApiToken($token);
        if ($userId === null) {
            return null;
        }

        $this->authenticatedApiUser = AuthenticatedUser::create($userId, CredentialType::API_TOKEN);

        return $this->authenticatedApiUser;
    }

    public function requireApiToken(Request $request) : AuthenticatedUser
    {
        $authenticatedUser = $this->authenticateApiToken($request);
        if ($authenticatedUser === null) {
            throw new RuntimeException('Could not find a valid API token');
        }

        return $authenticatedUser;
    }

    public function authenticateWebSession() : ?AuthenticatedUser
    {
        if ($this->webSessionAuthenticationResolved === true) {
            return $this->authenticatedWebUser;
        }

        $this->webSessionAuthenticationResolved = true;

        $token = $this->getAuthenticationCookie();
        if ($token === null) {
            return null;
        }

        $userId = $this->findUserIdByValidAuthToken($token);
        if ($userId === null) {
            $this->clearAuthenticationCookie();

            return null;
        }

        $this->authenticatedWebUser = AuthenticatedUser::create($userId, CredentialType::WEB_SESSION);

        return $this->authenticatedWebUser;
    }

    public function requireWebSession() : AuthenticatedUser
    {
        $authenticatedUser = $this->authenticateWebSession();
        if ($authenticatedUser === null) {
            throw new RuntimeException('Could not find an authenticated web session');
        }

        return $authenticatedUser;
    }

    private function deleteToken(string $token) : void
    {
        $this->repository->deleteAuthToken($this->hashToken($token));
    }

    private function findUserAndVerifyAuthentication(
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

    public function isUserPageVisible(UserEntity $targetUser, ?AuthenticatedUser $authenticatedUser) : bool
    {
        return $this->isUserPageVisibleForUser($targetUser, $authenticatedUser?->getUserId());
    }

    public function loginWebSession(
        string $email,
        string $password,
        bool $rememberMe,
        string $userAgent,
        ?int $userTotpInput = null,
    ) : void {
        $user = $this->findUserAndVerifyAuthentication($email, $password, $userTotpInput);

        $authTokenExpirationDate = $this->createExpirationDate();
        if ($rememberMe === true) {
            $authTokenExpirationDate = $this->createExpirationDate(self::MAX_EXPIRATION_AGE_IN_DAYS);
        }

        $token = $this->setAuthenticationToken(
            $user->getId(),
            self::WEB_SESSION_DEVICE_NAME,
            $userAgent,
            $authTokenExpirationDate,
        );

        $this->setAuthenticationCookie($token, $authTokenExpirationDate);
        $this->authenticatedWebUser = AuthenticatedUser::create(
            $user->getId(),
            CredentialType::WEB_SESSION,
        );
        $this->webSessionAuthenticationResolved = true;
    }

    public function logout() : void
    {
        $token = $this->getAuthenticationCookie();

        if ($token !== null) {
            $this->deleteToken($token);
            $this->clearAuthenticationCookie();
        }

        $this->authenticatedWebUser = null;
        $this->webSessionAuthenticationResolved = true;

        $this->flashMessageService->clear();
    }

    private function setAuthenticationCookie(string $token, DateTime $expirationDate) : void
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
        $tokenHash = $this->hashToken($token);
        $tokenData = $this->repository->findAuthTokenData($tokenHash);
        if ($tokenData === null) {
            return null;
        }

        if ($tokenData['expirationDate']->isAfter(DateTime::create()) === false) {
            $this->repository->deleteAuthToken($tokenHash);

            return null;
        }

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
