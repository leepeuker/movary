<?php declare(strict_types=1);

namespace Movary\ValueObject\Exception;

class ConfigKeyNotSetException extends \RuntimeException
{
    public static function create(string $key) : self
    {
        return new self('Config key does not exist: ' . $key);
    }
}
