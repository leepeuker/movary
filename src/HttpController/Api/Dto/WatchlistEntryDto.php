<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;
use Movary\ValueObject\DateTime;

class WatchlistEntryDto implements JsonSerializable
{
    public function __construct(
        private readonly MovieDto $movieDto,
        private readonly DateTime $addedAt,
    ) {
    }

    public static function create(MovieDto $movieDto, DateTime $addedAt) : self
    {
        return new self($movieDto, $addedAt);
    }

    public function jsonSerialize() : array
    {
        return [
            'movie' => $this->movieDto,
            'addedAt' => $this->addedAt,
        ];
    }
}
