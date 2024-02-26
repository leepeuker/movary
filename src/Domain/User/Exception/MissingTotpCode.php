<?php declare(strict_types=1);

namespace Movary\Domain\User\Exception;

class MissingTotpCode extends InvalidCredentials
{
    public static function create() : self
    {
        return new self('Two-factor authentication code missing.');
    }
}
