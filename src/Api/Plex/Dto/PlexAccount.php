<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexAccount
{
    public function __construct(
        private readonly int $plexId,
        private readonly string $username,
    ) {
    }

    public static function create(int $plexId, string $username) : self
    {
        return new self($plexId, $username);
    }

    public function getPlexId() : int
    {
        return $this->plexId;
    }

    public function getPlexUsername() : string
    {
        return $this->username;
    }
}
