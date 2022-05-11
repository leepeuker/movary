<?php declare(strict_types=1);

namespace Movary\Application\User\Service;

use Movary\Application\User\Exception\InvalidPassword;
use Movary\Application\User\Repository;

class Login
{
    private Repository $userRepository;

    public function __construct(Repository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function authenticate(string $password, bool $rememberMe) : void
    {
        $user = $this->userRepository->fetchAdminUser();

        if (password_verify($password, $user->getPasswordHash()) === false) {
            throw InvalidPassword::create();
        }

        if ($rememberMe === true) {
            session_destroy();
            ini_set('session.cookie_lifetime', '2419200');
            ini_set('session.gc_maxlifetime', '2419200');
            session_start(
                [
                    'cookie_lifetime' => 2419200,
                ]
            );
        }

        session_regenerate_id();

        $_SESSION['user']['id'] = $user->getId();
    }
}
