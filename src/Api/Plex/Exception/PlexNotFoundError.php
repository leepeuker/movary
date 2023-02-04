<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use RuntimeException;

class PlexNotFoundError extends RuntimeException
{
    public static function create($requestedURI) : self
    {
        return new self('The requested URI does not exist: ' . $requestedURI);
    }
}