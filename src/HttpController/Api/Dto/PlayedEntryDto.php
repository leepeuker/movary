<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\DateTime;

class PlayedEntryDto implements \JsonSerializable
{
    public function __construct(
        private readonly MovieDto $movieDto,
    ) {
    }

    public static function create(MovieDto $movieDto) : self
    {
        return new self($movieDto);
    }

    public function jsonSerialize() : array
    {
        return [
            'movie' => $this->movieDto,
        ];
    }
}
