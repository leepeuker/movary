<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast;

use Movary\AbstractList;

/**
 * @method CastEntity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class CastEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(CastEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(CastEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
