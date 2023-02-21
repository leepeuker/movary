<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexAccount
{
    public function __construct(
        private readonly int $plexId,
        private readonly string $username,
        private readonly string $email
    ){
    }

    public static function createplexAccount(int $plexId, string $username, string $email) : self
    {
        return new self($plexId, $username, $email);
    }

    public function getPlexId() : int
    {
        return $this->plexId;
    }

    public function getPlexUsername() : string
    {
        return $this->username;
    }

    public function getPlexEmail() : string
    {
        return $this->email;
    }
}