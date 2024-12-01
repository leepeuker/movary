<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

use RuntimeException;

class ConfigNotSetException extends RuntimeException
{
    public static function create(string $key) : self
    {
        return new self('Required config not set: ' . $key);
    }
}
