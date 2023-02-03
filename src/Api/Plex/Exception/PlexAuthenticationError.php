<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexAuthenticationError extends RuntimeException
{
    public static function create() : self
    {
        return new self('The access token is invalid.');
    }
}