<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre;

use Movary\AbstractList;

/**
 * @method MovieGenreEntity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
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
