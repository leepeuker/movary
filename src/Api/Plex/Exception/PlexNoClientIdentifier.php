<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexNoClientIdentifier extends RuntimeException
{
    public static function create() : self
    {
        return new self('Plex client identifier not set');
    }
}
