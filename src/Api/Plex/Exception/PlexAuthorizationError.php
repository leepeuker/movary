<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

class PlexAuthorizationError extends \RuntimeException
{
    public static function create() : self
    {
        return new self('Plex token is probably not set or invalid.');
    }
}
