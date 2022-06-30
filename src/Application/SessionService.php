<?php declare(strict_types=1);

namespace Movary\Application;

use Movary\Application\User\Service\Authentication;

class SessionService
{
    public function __construct(private readonly Authentication $authenticationService)
    {
    }

    public function isUserAuthenticated() : bool
    {
        if (empty($_COOKIE['id']) === false && $this->authenticationService->isValidToken($_COOKIE['id']) === true) {
            return true;
        }

        if (empty($_COOKIE['id']) === false) {
            unset($_COOKIE['id']);
            setcookie('id', '', -1);
        }

        return false;
    }
}
