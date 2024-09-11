<?php declare(strict_types=1);

namespace Movary\Util;

use RuntimeException;

class SessionWrapper
{
    public function destroy() : void
    {
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $sessionName = session_name();
            if ($sessionName === false) {
                throw new RuntimeException('Could not get session name');
            }

            $params = session_get_cookie_params();

            setcookie(
                $sessionName,
                '',
                time() - 42000,
                (string)$params['path'],
                (string)$params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly'],
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            session_regenerate_id();
        }
    }

    public function find(string $key) : mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function has(string $key) : bool
    {
        return isset($_SESSION[$key]) === true;
    }

    public function set(string $key, mixed $value) : void
    {
        $_SESSION[$key] = $value;
    }

    public function start() : void
    {
        session_start();
    }

    public function unset(string ...$keys) : void
    {
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }
}
