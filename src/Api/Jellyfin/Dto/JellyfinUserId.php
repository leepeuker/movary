<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinUserid
{
    private function __construct(
        private readonly string $JellyfinUserid,
    ) {
    }

    public static function create(string $JellyfinUserid) : self
    {
        return new self($JellyfinUserid);
    }

    public function __toString() : string
    {
        return $this->JellyfinUserid;
    }
}
