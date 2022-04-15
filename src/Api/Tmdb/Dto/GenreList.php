<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\AbstractList;

/**
 * @method Genre[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class GenreList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(Genre::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(Genre $genre) : void
    {
        $this->data[] = $genre;
    }
}
