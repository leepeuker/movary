<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use Movary\ValueObject\Url;
use RuntimeException;

class PlexNotFoundError extends RuntimeException
{
    public static function create(Url $requestUrl) : self
    {
        return new self('The requested url does not exist: ' . $requestUrl);
    }
}
