<?php declare(strict_types=1);

namespace Movary\Domain\Country;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<CountryEntity>
 */
class CountryEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $country) {
            $list->add(CountryEntity::createFromArray($country));
        }

        return $list;
    }

    public function add(CountryEntity $Country) : void
    {
        $this->data[] = $Country;
    }
}
