<?php declare(strict_types=1);

namespace Movary\Application\Company;

use Movary\AbstractList;

/**
 * @method Entity[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class EntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $company) {
            $list->add(Entity::createFromArray($company));
        }

        return $list;
    }

    public function add(Entity $company) : void
    {
        $this->data[] = $company;
    }
}
