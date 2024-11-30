<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<TmdbGenre>
 */
class TmdbGenreList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(TmdbGenre::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(TmdbGenre $genre) : void
    {
        $this->data[] = $genre;
    }
}
