<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinUser
{
    private function __construct(
        private readonly JellyfinUserId $jellyfinUserId,
        private readonly string $jellyfinUsername,
    ) {
    }

    public static function create(JellyfinUserId $jellyfinUserId, string $jellyfinUsername) : self
    {
        return new self($jellyfinUserId, $jellyfinUsername);
    }

    public function getUserId() : JellyfinUserId
    {
        return $this->jellyfinUserId;
    }

    public function getUsername() : string
    {
        return $this->jellyfinUsername;
    }
}
