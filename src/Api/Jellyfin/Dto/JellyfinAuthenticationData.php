<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinAuthenticationData
{
    private function __construct(
        private readonly JellyfinAccessToken $jellyfinAccessToken,
        private readonly JellyfinUserId $jellyfinUserId,
    ) {
    }

    public static function create(JellyfinAccessToken $jellyfinAccessToken, JellyfinUserId $jellyfinUserId) : self
    {
        return new self($jellyfinAccessToken, $jellyfinUserId);
    }

    public function getUserId() : JellyfinUserId
    {
        return $this->jellyfinUserId;
    }

    public function getAccessToken() : JellyfinAccessToken
    {
        return $this->jellyfinAccessToken;
    }
}
