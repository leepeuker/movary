<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Exception;

use Movary\ValueObject\Url;
use RuntimeException;

class JellyfinNotFoundError extends RuntimeException
{
    public static function create(Url $requestUrl) : self
    {
        return new self('The requested url does not exist: ' . $requestUrl);
    }
}
