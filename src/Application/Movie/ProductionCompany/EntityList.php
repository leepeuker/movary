<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany;

use Movary\AbstractList;

/**
 * @method Entity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class EntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(Entity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(Entity $dto) : void
    {
        $this->data[] = $dto;
    }
}
