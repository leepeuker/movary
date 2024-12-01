<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<MovieHistoryEntity>
 */
class MovieHistoryEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(MovieHistoryEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(MovieHistoryEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
