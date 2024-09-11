<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<PlayedEntryDto>
 */
class PlayedEntryDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(PlayedEntryDto $dto) : void
    {
        $this->data[] = $dto;
    }
}
