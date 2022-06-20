<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Exception;

class AuthorizationError extends \RuntimeException
{
    public static function create() : self
    {
        return new self('TMDB API key is probably not set or invalid.');
    }
}
