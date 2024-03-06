<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

use Movary\ValueObject\Url;

class JellyfinAuthenticationData
{
    private function __construct(
        private readonly JellyfinAccessToken $jellyfinAccessToken,
        private readonly JellyfinUserId $jellyfinUserId,
        private readonly Url $jellyfinServerUrl,
    ) {
    }

    public static function create(JellyfinAccessToken $jellyfinAccessToken, JellyfinUserId $jellyfinUserId, Url $jellyfinServerUrl) : self
    {
        return new self($jellyfinAccessToken, $jellyfinUserId, $jellyfinServerUrl);
    }

    public function getAccessToken() : JellyfinAccessToken
    {
        return $this->jellyfinAccessToken;
    }

    public function getServerUrl() : Url
    {
        return $this->jellyfinServerUrl;
    }

    public function getUserId() : JellyfinUserId
    {
        return $this->jellyfinUserId;
    }
}
