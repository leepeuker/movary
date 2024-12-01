<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;
use Movary\ValueObject\Date;

class HistoryEntryDto implements JsonSerializable
{
    public function __construct(
        private readonly MovieDto $movieDto,
        private readonly Date $watchedAt,
    ) {
    }

    public static function create(MovieDto $movieDto, Date $playDate) : self
    {
        return new self($movieDto, $playDate);
    }

    public function jsonSerialize() : array
    {
        return [
            'movie' => $this->movieDto,
            'watchedAt' => $this->watchedAt,
        ];
    }
}
