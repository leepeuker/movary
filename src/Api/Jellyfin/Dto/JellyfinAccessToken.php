<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinAccessToken
{
    private function __construct(
        private readonly string $JellyfinAccessToken,
    ) {
    }

    public static function create(string $JellyfinAccessToken) : self
    {
        return new self($JellyfinAccessToken);
    }

    public function __toString() : string
    {
        return $this->JellyfinAccessToken;
    }
}
