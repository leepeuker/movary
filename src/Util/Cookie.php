<?php declare(strict_types=1);

namespace Movary\Util;

use Closure;
use Movary\Service\CookieSecurity;

class Cookie
{
    private readonly Closure $writer;

    public function __construct(
        private readonly CookieSecurity $cookieSecurity,
        ?Closure $writer = null,
    )
    {
        $this->writer = $writer ?? static fn(string $name, string $value, array $options) : bool => setcookie(
            $name,
            $value,
            $options,
        );
    }

    public function delete(string $name) : void
    {
        unset($_COOKIE[$name]);
        ($this->writer)(
            $name,
            '',
            [
                'expires' => 1,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $this->cookieSecurity->isSecure(),
            ],
        );
    }

    public function find(string $name) : ?string
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) === true && $value !== '' ? $value : null;
    }

    public function set(string $name, string $value, ?int $expires) : void
    {
        $_COOKIE[$name] = $value;

        $options = [];
        if ($expires !== null) {
            $options['expires'] = $expires;
        }
        $options += [
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $this->cookieSecurity->isSecure(),
        ];

        ($this->writer)(
            $name,
            $value,
            $options,
        );
    }
}
