<?php declare(strict_types=1);

namespace Movary\Application;

class SessionService
{
    public function isCurrentUserLoggedIn() : bool
    {
        return empty($_SESSION['user']['id']) === false;
    }
}
