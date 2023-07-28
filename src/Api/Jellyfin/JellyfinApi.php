<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Movary\ValueObject\Url;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient
    ) {}

    public function fetchJellyfinServerInfo()
    {
        return $this->jellyfinClient->get('/system/info/public');
    }
}