<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;

class DashboardRowManifestDto implements JsonSerializable
{
    public function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly int $position,
        private readonly bool $visible,
        private readonly bool $initiallyExpanded,
        private readonly string $href,
    ) {
    }

    public function jsonSerialize() : array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'position' => $this->position,
            'visible' => $this->visible,
            'initiallyExpanded' => $this->initiallyExpanded,
            'href' => $this->href,
        ];
    }
}
