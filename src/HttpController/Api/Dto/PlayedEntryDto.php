<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;

class PlayedEntryDto implements JsonSerializable
{
    public function __construct(
        private readonly MovieDto $movieDto,
        private readonly WatchDateDtoList $watchDates,
    ) {
    }

    public static function create(MovieDto $movieDto, WatchDateDtoList $watchDates) : self
    {
        return new self($movieDto, $watchDates);
    }

    public function jsonSerialize() : array
    {
        return [
            'movie' => $this->movieDto,
            'watchDates' => $this->watchDates
        ];
    }
}
