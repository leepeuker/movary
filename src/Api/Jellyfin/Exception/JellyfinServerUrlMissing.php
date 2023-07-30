<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Exception;

use RuntimeException;

class JellyfinServerUrlMissing extends RuntimeException
{
    public static function create() : self
    {
        return new self('The Jellyfin server url is missing.');
    }
}
