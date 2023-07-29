<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinUserId
{
    private function __construct(
        private readonly string $jellyfinUserId,
    ) {
    }

    public static function create(string $jellyfinUserId) : self
    {
        return new self($jellyfinUserId);
    }

    public function __toString() : string
    {
        return $this->jellyfinUserId;
    }
}
