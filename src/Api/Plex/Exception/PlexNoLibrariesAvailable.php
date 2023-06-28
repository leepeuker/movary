<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexNoLibrariesAvailable extends RuntimeException
{
    public static function create(int $userId) : self
    {
        return new self('No libraries have been found for the user with id ' . $userId);
    }
}
