<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

use RuntimeException;

class InvalidRelativeUrl extends RuntimeException
{
    public static function create(string $url) : self
    {
        return new self('Not a valid relative url: ' . $url);
    }
}
