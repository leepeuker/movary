<?php declare(strict_types=1);

namespace Movary\Api\Plex\Exception;

use Movary\ValueObject\Url;
use RuntimeException;

class PlexNotFoundError extends RuntimeException
{
    public static function create(Url $requestUri) : self
    {
        return new self('The requested URI does not exist: ' . $requestUri);
    }
}
