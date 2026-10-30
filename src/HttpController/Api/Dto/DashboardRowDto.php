<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;

class DashboardRowDto implements JsonSerializable
{
    public function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly array|WatchlistEntryDtoList $items,
    ) {
    }

    public function jsonSerialize() : array
    {
        return ['key' => $this->key, 'title' => $this->title, 'items' => $this->items];
    }
}
