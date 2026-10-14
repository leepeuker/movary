<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

use RuntimeException;

class UserDisabled extends RuntimeException
{
    public static function create() : self
    {
        return new self('User disabled.');
    }
}
