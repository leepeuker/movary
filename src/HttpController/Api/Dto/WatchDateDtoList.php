<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\AbstractList;
use RuntimeException;

/**
 * @method WatchDateDto[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class WatchDateDtoList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(WatchDateDto $dto) : void
    {
        foreach ($this as $watchDate) {
            if ($watchDate->getWatchDate() == $dto->getWatchDate()) {
                throw new RuntimeException('Watch date must be unique');
            }
        }

        $this->data[] = $dto;
    }
}
