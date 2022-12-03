<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class EmailNotFound extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('No user found with matching email address.');
    }
}
