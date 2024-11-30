<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<JellyfinMovieDto>
 */
class JellyfinMovieDtoList extends AbstractList
{
    public static function create(JellyfinMovieDto ...$movies) : self
    {
        $list = new self();

        foreach ($movies as $movie) {
            $list->add($movie);
        }

        return $list;
    }

    public static function createFromArray(array $data) : self
    {
        $list = self::create();

        foreach ($data as $movieData) {
            $list->add(JellyfinMovieDto::createFromArray($movieData));
        }

        return $list;
    }

    public function add(JellyfinMovieDto $movie) : void
    {
        $this->data[$movie->getJellyfinItemId()] = $movie;
    }

    public function getByItemId(string $itemId) : ?JellyfinMovieDto
    {
        return $this->data[$itemId] ?? null;
    }

    public function getTmdbIds() : array
    {
        $tmdbIds = [];

        foreach ($this as $movie) {
            $tmdbIds[] = $movie->getTmdbId();
        }

        return $tmdbIds;
    }
}
