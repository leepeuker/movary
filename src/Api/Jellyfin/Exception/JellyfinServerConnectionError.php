<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Exception;

use Movary\ValueObject\Url;
use RuntimeException;

class JellyfinServerConnectionError extends RuntimeException
{
    public static function create(Url $url) : self
    {
        return new self('Cannot connect to Jellyfin server: ' . $url);
    }
}
