<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Movary\AbstractList;

/**
 * @method Job[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class JobList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $movie) {
            $list->add(Job::createFromArray($movie));
        }

        return $list;
    }

    private function add(Job $dto) : void
    {
        $this->data[] = $dto;
    }
}
