<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexNoClientIdentifier extends RuntimeException
{
    public static function create() : self
    {
        return new self('No client identifier has been found. Please add it in the environment variables before using the Plex API');
    }
}