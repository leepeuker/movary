<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use RuntimeException;

class AuthenticationWeb implements AuthenticationInterface
{
    private const AUTHENTICATION_COOKIE_NAME = 'id';

    private const CLIENT_DEVICE_NAME = 'Movary Web Client';

    public function __construct(
        private readonly AuthenticationBase $authentication,
        private readonly SessionWrapper $sessionWrapper,
        private readonly UserRepository $repository,
    ) {
    }

    public function getCurrentUser(Request $request) : UserEntity
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);

        $user = $this->repository->findUserByAuthToken((string)$token);

        if ($user === null) {
            throw new RuntimeException('Could not find a current user');
        }

        $this->sessionWrapper->set('userId', $user->getId());

        return $user;
    }

    public function getToken(Request $request) : ?string
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);
        if (empty($token) === true) {
            return null;
        }

        return $token;
    }

    public function isUserAuthenticated(Request $request) : bool
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);

        if (empty($token) === false && $this->authentication->isValidAuthToken((string)$token) === true) {
            return true;
        }

        if (empty($token) === false) {
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
        }

        return false;
    }

    public function login(
        string $email,
        string $password,
        bool $rememberMe,
        string $userAgent,
        ?int $userTotpInput = null,
    ) : void {
        $user = $this->authentication->findUserByEmail($email);

        $this->authentication->verifyUserAuthentication($user, $password, $userTotpInput);

        $token = $this->authentication->createAuthenticationToken(
            $user,
            $rememberMe,
            self::CLIENT_DEVICE_NAME,
            $userAgent,
        );

        $this->setAuthenticationCookieAndNewSession($user->getId(), $token);
    }

    public function logout() : void
    {
        $token = filter_input(INPUT_COOKIE, self::AUTHENTICATION_COOKIE_NAME);

        if ($token !== null) {
            $this->authentication->deleteToken((string)$token);
            unset($_COOKIE[self::AUTHENTICATION_COOKIE_NAME]);
            setcookie(self::AUTHENTICATION_COOKIE_NAME, '', -1);
        }

        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
    }

    private function setAuthenticationCookieAndNewSession(int $userId, array $token) : void
    {
        $this->sessionWrapper->destroy();
        $this->sessionWrapper->start();
        setcookie(self::AUTHENTICATION_COOKIE_NAME, $token['token'], [
            'expires' => $token['expirationDate']->format('U'),
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'strict'
        ]);

        $this->sessionWrapper->set('userId', $userId);
    }
}
