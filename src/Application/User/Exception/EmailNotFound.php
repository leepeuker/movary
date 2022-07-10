<?php declare(strict_types=1);

namespace Movary\Application\User\Exception;

class EmailNotFound extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('No user found with matching email address.');
    }
}
