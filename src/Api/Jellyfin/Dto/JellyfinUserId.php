<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinUserId
{
    private function __construct(
        private readonly string $jellyfinUserid,
    ) {
    }

    public static function create(string $jellyfinUserid) : self
    {
        return new self($jellyfinUserid);
    }

    public function __toString() : string
    {
        return $this->jellyfinUserid;
    }
}
