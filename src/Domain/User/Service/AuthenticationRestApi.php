<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class AuthenticationRestApi implements AuthenticationInterface
{
    public function __construct(
        private readonly AuthenticationBase $authentication,
        private readonly UserApi $userApi,
    ) {
    }

    public function deleteToken(string $apiToken) : void
    {
        $this->authentication->deleteToken($apiToken);
    }

    public function getCurrentUser(Request $request) : UserEntity
    {
        $apiToken = $this->getToken($request);

        $user = $this->userApi->findByToken($apiToken);
        if ($user === null) {
            throw new RuntimeException('Could not find a current user');
        }

        return $user;
    }

    public function getToken(Request $request) : ?string
    {
        return $request->getHeaders()['X-Movary-Token'] ?? null;
    }

    public function isUserAuthenticated(Request $request) : bool
    {
        $token = $this->getToken($request);
        if ($token === null) {
            return false;
        }

        return $this->authentication->isValidAuthToken($token);
    }

    public function isUserPageVisibleForApiRequest(Request $request, UserEntity $targetUser) : bool
    {
        try {
            $userId = $this->getCurrentUser($request)->getId();
        } catch (RuntimeException) {
            $userId = null;
        }

        $privacyLevel = $targetUser->getPrivacyLevel();

        if ($privacyLevel === 2) {
            return true;
        }

        if ($privacyLevel === 1 && $userId !== null) {
            return true;
        }

        return $targetUser->getId() === $userId;
    }

    public function login(
        string $email,
        string $password,
        bool $rememberMe,
        string $deviceName,
        string $userAgent,
        ?int $userTotpInput = null,
    ) : array {
        $user = $this->authentication->findUserByEmail($email);

        $this->authentication->verifyUserAuthentication($user, $password, $userTotpInput);

        $token = $this->authentication->createAuthenticationToken(
            $user,
            $rememberMe,
            $deviceName,
            $userAgent,
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
