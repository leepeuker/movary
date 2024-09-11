<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Rating;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<Dto>
 */
class DtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $list = new self();

        foreach ($data as $movie) {
            $list->add(Dto::createFromArray($movie));
        }

        return $list;
    }

    private function add(Dto $dto) : void
    {
        $this->data[] = $dto;
    }
}
