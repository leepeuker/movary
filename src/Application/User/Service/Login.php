<?php declare(strict_types=1);

namespace Movary\Application\User\Service;

use Movary\Application\User\Exception\InvalidPassword;
use Movary\Application\User\Repository;
use Movary\ValueObject\DateTime;

class Login
{
    public function __construct(
        private readonly Repository $userRepository,
        private readonly Authentication $authenticationService
    ) {
    }

    public function login(string $password, bool $rememberMe) : void
    {
        $user = $this->userRepository->fetchAdminUser();

        if (password_verify($password, $user->getPasswordHash()) === false) {
            throw InvalidPassword::create();
        }

        $expirationTime = DateTime::createFromString(date('Y-m-d H:i:s', strtotime('+1 day')));
        if ($rememberMe === true) {
            $expirationTime = DateTime::createFromString(date('Y-m-d H:i:s', strtotime('+30 day')));
        }

        $token = $this->authenticationService->generateToken(DateTime::createFromString((string)$expirationTime));

        setcookie('id', $token, (int)$expirationTime->format('U'));
    }
}
