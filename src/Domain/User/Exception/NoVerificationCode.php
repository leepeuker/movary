<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class NoVerificationCode extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('Verification code is not present.');
    }
}
