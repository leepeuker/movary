<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\AbstractList;

/**
 * @method ProductionCompany[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class ProductionCompanyList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(ProductionCompany::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(ProductionCompany $productionCompany) : void
    {
        $this->data[] = $productionCompany;
    }
}
