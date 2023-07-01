<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexAuthenticationInvalid extends RuntimeException
{
    public static function create() : self
    {
        return new self('Plex authentication is not valid');
    }
}
