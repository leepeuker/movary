<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

use Movary\ValueObject\AbstractList;

/**
 * @method MovieHistoryLocationEntity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class MovieHistoryLocationEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $location) {
            $list->add(MovieHistoryLocationEntity::createFromArray($location));
        }

        return $list;
    }

    private function add(MovieHistoryLocationEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
