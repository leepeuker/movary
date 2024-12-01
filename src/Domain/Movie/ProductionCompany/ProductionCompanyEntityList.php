<?php declare(strict_types=1);

namespace Movary\Domain\Movie\ProductionCompany;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<ProductionCompanyEntity>
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
