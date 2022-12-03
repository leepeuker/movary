<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class UsernameInvalidFormat extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('Name is not valid.');
    }
}
