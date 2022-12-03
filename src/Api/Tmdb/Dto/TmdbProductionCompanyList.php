<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method TmdbProductionCompany[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class TmdbProductionCompanyList extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genreDate) {
            $list->add(TmdbProductionCompany::createFromArray($genreDate));
        }

        return $list;
    }

    public function add(TmdbProductionCompany $productionCompany) : void
    {
        $this->data[] = $productionCompany;
    }
}
