<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class InvalidTotpCode extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('Two-factor authentication code wrong.');
    }
}
