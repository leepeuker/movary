<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\AbstractList;

/**
 * @method Movie[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class MovieList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(Movie::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(Movie $genre) : void
    {
        $this->data[] = $genre;
    }
}
