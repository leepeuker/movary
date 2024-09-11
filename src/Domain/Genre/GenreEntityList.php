<?php declare(strict_types=1);

namespace Movary\Domain\Genre;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<GenreEntity>
 */
class GenreEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genre) {
            $list->add(GenreEntity::createFromArray($genre));
        }

        return $list;
    }

    public function add(GenreEntity $genre) : void
    {
        $this->data[] = $genre;
    }
}
