<?php declare(strict_types=1);

namespace Movary\Service\Netflix\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<NetflixActivityItem>
 */
class NetflixActivityItemList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(NetflixActivityItem $item) : void
    {
        $this->data[] = $item;
    }
}
