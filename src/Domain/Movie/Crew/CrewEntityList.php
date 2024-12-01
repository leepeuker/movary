<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Crew;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<CrewEntity>
 */
class CrewEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(CrewEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(CrewEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
