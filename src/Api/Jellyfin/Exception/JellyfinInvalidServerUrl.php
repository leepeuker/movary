<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Exception;

use RuntimeException;

class JellyfinInvalidServerUrl extends RuntimeException
{
    public static function create() : self
    {
        return new self('Jellyfin server URL is incorrect or not present');
    }
}
