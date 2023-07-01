<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexAuthenticationTokenMissing extends RuntimeException
{
    public static function create() : self
    {
        return new self('The Plex authentication is missing.');
    }
}
