<?php declare(strict_types=1);

namespace Movary\Util;

class SessionWrapper
{
    public function destroy() : void
    {
        session_destroy();
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
