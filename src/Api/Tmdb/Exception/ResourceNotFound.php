<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Exception;

class ResourceNotFound extends \RuntimeException
{
    public static function create(string $url) : self
    {
        return new self('No resource found: ' . $url);
    }
}
