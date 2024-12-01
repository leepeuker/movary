<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<MovieSearchResultDto>
 */
class MovieSearchResultDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function get(int $tmdbId) : MovieSearchResultDto
    {
        return $this->data[$tmdbId];
    }

    public function jsonSerialize() : array
    {
        return array_values($this->data);
    }

    public function set(MovieSearchResultDto $dto) : void
    {
        $this->data[$dto->getTmdbId()] = $dto;
    }
}
