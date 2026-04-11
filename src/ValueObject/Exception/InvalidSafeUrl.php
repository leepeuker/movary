<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

class InvalidSafeUrl extends InvalidUrl
{
    public static function create(string $url) : self
    {
        return new self('Not a valid safe url: ' . $url);
    }
}
