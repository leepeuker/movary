<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinAuthenticationData
{
    private function __construct(
        private readonly JellyfinAccessToken $jellyfinAccessToken,
        private readonly JellyfinUserid $jellyfinUserid
    ) {
    }

    public static function create(JellyfinAccessToken $JellyfinAccessToken, JellyfinUserid $jellyfinUserid) : self
    {
        return new self($JellyfinAccessToken, $jellyfinUserid);
    }

    public function getUserIdAsString() : string
    {
        return (string)$this->jellyfinUserid;
    }

    public function getAccessTokenAsString() : string
    {
        return (string)$this->jellyfinAccessToken;
    }

    public function getUserId() : JellyfinUserid
    {
        return $this->jellyfinUserid;
    }

    public function getAccessToken() : JellyfinAccessToken
    {
        return $this->jellyfinAccessToken;
    }
}
