<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Exception;

use RuntimeException;

class JellyfinInvalidAuthentication extends RuntimeException
{
    public static function create() : self
    {
        return new self('Jellyfin authentication is not valid');
    }
}
