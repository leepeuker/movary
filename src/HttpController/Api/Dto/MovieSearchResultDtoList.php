<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method MovieSearchResultDto[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class MovieSearchResultDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function set(MovieSearchResultDto $dto) : void
    {
        $this->data[$dto->getTmdbId()] = $dto;
    }

    public function get(int $tmdbId) : MovieSearchResultDto
    {
        return $this->data[$tmdbId];
    }
}
