<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Genre;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<MovieGenreEntity>
 */
class MovieGenreEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(MovieGenreEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(MovieGenreEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
