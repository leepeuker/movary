<?php declare(strict_types=1);

namespace Movary\Domain\Company;

use Movary\AbstractList;

/**
 * @method CompanyEntity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class CompanyEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $company) {
            $list->add(CompanyEntity::createFromArray($company));
        }

        return $list;
    }

    public function add(CompanyEntity $company) : void
    {
        $this->data[] = $company;
    }
}
