<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<PersonEntity>
 */
class PersonEntityList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $genre) {
            $list->add(PersonEntity::createFromArray($genre));
        }

        return $list;
    }

    public function add(PersonEntity $genre) : void
    {
        $this->data[] = $genre;
    }
}
