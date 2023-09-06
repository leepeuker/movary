<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method MovieDto[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
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
