<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

use Movary\ValueObject\Url;

class PlexUserClientConfiguration
{
    private function __construct(
        private readonly PlexAccessToken $accessToken,
        private readonly Url $serverUrl,
    ) {
    }

    public static function create(PlexAccessToken $accessToken, Url $serverUrl) : self
    {
        return new self($accessToken, $serverUrl);
    }

    public function getAccessToken() : PlexAccessToken
    {
        return $this->accessToken;
    }

    public function getServerUrl() : Url
    {
        return $this->serverUrl;
    }
}
