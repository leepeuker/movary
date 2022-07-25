<?php declare(strict_types=1);

namespace Movary\Application\User\Exception;

class EmailNotUnique extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('Email is not unique.');
    }
}
