<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<TmdbProductionCompany>
 */
class TmdbProductionCompanyList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $productionCompanyData) {
            $list->add(TmdbProductionCompany::createFromArray($productionCompanyData));
        }

        return $list;
    }

    private function add(TmdbProductionCompany $productionCompany) : void
    {
        $this->data[] = $productionCompany;
    }
}
