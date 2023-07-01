<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexAccessToken
{
    private function __construct(
        private readonly string $plexAccessToken,
    ) {
    }

    public static function create(string $plexAccessToken) : self
    {
        return new self($plexAccessToken);
    }

    public function getPlexAccessTokenAsString() : string
    {
        return $this->plexAccessToken;
    }
}
