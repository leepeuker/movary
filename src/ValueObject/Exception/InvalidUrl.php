<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

class InvalidUrl extends \RuntimeException
{
    public static function create(string $url) : self
    {
        return new self('Not a valid url: ' . $url);
    }
}
