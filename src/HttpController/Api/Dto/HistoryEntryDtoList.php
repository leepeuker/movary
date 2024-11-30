<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<HistoryEntryDto>
 */
class HistoryEntryDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(HistoryEntryDto $dto) : void
    {
        $this->data[] = $dto;
    }
}
