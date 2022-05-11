<?php declare(strict_types=1);

namespace Movary\Application\User\Exception;

class InvalidPassword extends \RuntimeException
{
    public static function create() : self
    {
        return new self('Provided invalid admin password.');
    }
}
