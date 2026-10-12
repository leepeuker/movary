<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\Exception\PasswordInvalidEncoding;
use Movary\Domain\User\Exception\PasswordTooShort;

class PasswordPolicy
{
    public const int MINIMUM_LENGTH = 15;

    public function ensurePasswordIsValid(string $password) : void
    {
        if (mb_check_encoding($password, 'UTF-8') === false) {
            throw new PasswordInvalidEncoding();
        }

        if (mb_strlen($password, 'UTF-8') < self::MINIMUM_LENGTH) {
            throw new PasswordTooShort(self::MINIMUM_LENGTH);
        }
    }
}
