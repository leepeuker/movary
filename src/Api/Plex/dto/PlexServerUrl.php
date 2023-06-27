<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexServerUrl
{
    public function __construct(
        private readonly string $serverUrl = "http://localhost:32400"
    ){
    }

    public static function createPlexServerUrl(string $serverUrl) : self
    {
        return new self($serverUrl);
    }

    public function getPlexServerUrl() : string
    {
        return $this->serverUrl;
    }
}