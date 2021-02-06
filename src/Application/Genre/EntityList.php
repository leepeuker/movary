<?php declare(strict_types=1);

namespace Movary\Application\Genre;

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

        foreach ($data as $genre) {
            $list->add(Entity::createFromArray($genre));
        }

        return $list;
    }

    public function add(Entity $genre) : void
    {
        $this->data[] = $genre;
    }
}
