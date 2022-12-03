<?php declare(strict_types=1);

namespace Movary\Domain\Movie\ProductionCompany;

use Movary\AbstractList;

/**
 * @method ProductionCompanyEntity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class ProductionCompanyEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $historyEntry) {
            $list->add(ProductionCompanyEntity::createFromArray($historyEntry));
        }

        return $list;
    }

    private function add(ProductionCompanyEntity $dto) : void
    {
        $this->data[] = $dto;
    }
}
