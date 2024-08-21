<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History\Location;

class MovieHistoryLocationApi
{
    public function __construct()
    {
    }

    public function findLocationsByUserId(int $userId) : MovieHistoryLocationEntityList
    {
        return MovieHistoryLocationEntityList::create();
    }
}
