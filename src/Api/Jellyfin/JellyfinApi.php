<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient
    ) {}

    public function fetchJellyfinServerInfo()
    {
        $response = $this->jellyfinClient->get('/system/info/public');
        return $response;
    }
}