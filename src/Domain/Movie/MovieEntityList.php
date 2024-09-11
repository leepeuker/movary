<?php declare(strict_types=1);

namespace Movary\Domain\Movie;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<MovieEntity>
 */
class MovieEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $movie) {
            $list->add(MovieEntity::createFromArray($movie));
        }

        return $list;
    }

    private function add(MovieEntity $movie) : void
    {
        $this->data[] = $movie;
    }
}
