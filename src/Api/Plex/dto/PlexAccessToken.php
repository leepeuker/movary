<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexAccessToken
{
    public function __construct(
        private readonly string $plexAccessToken
    ){
    }

    public static function createPlexAccessToken(string $plexAccessToken)
    {
        return new self($plexAccessToken);
    }

    public function getPlexAccessTokenAsString() : string
    {
        return $this->plexAccessToken;
    }
}