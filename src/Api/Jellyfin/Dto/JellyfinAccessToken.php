<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinAccessToken
{
    private function __construct(
        private readonly string $JellyfinAccessToken,
    ) {
    }

    public static function create(string $jellyfinAccessToken) : self
    {
        return new self($jellyfinAccessToken);
    }

    public function __toString() : string
    {
        return $this->JellyfinAccessToken;
    }
}
