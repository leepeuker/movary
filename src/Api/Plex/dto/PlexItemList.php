<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

use Movary\ValueObject\AbstractList;

class PlexItemList extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(PlexItem $plexItem) : void
    {
        $this->data[] = $plexItem;
    }
}