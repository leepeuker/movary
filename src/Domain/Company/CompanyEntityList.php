<?php declare(strict_types=1);

namespace Movary\Domain\Company;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<CompanyEntity>
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

    public function getUniqueCompanies() : self
    {
        $uniqueList = self::create();

        foreach ($this as $company) {
            if ($uniqueList->containsCompanyWithId($company->getId()) === true) {
                continue;
            }

            $uniqueList->add($company);
        }

        return $uniqueList;
    }

    private function containsCompanyWithId(int $id) : bool
    {
        foreach ($this as $company) {
            if ($company->getId() === $id) {
                return true;
            }
        }

        return false;
    }
}
