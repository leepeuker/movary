<?php declare(strict_types=1);

namespace Movary\Api\Github;

use Movary\ValueObject\AbstractList;

class ReleaseDtoList extends AbstractList
{
    public static function create(ReleaseDto ...$releases) : self
    {
        $list = new self();

        foreach ($releases as $release) {
            $list->add($release);
        }

        return $list;
    }

    public function add(ReleaseDto $release) : void
    {
        $this->data[] = $release;
    }
}
