<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\AbstractList;
use Movary\ValueObject\DateTime;

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

    public function containsTraktId(TraktId $traktId) : bool
    {
        foreach ($this as $watchedMovie) {
            if ($watchedMovie->getMovie()->getTraktId()->isEqual($traktId) === true) {
                return true;
            }
        }

        return false;
    }

    public function getLatestLastUpdated() : ?DateTime
    {
        if ($this->count() === 0) {
            return null;
        }

        $items = $this->data;

        usort($items, [$this, 'compareLastUpdatedAtAsc']);

        return $items[0]->getLastUpdated();
    }

    public function sortByLastUpdatedAt() : self
    {
        $items = $this->data;

        usort($items, [$this, 'compareLastUpdatedAtAsc']);

        $newList = self::create();
        foreach ($items as $item) {
            $newList->add($item);
        }

        return $newList;
    }

    private function add(Dto $dto) : void
    {
        $this->data[] = $dto;
    }

    private function compareLastUpdatedAtAsc(Dto $a, Dto $b) : int
    {
        if ($a->getLastUpdated()->isEqual($b->getLastUpdated()) === true) {
            return 0;
        }

        return $a->getLastUpdated() > $b->getLastUpdated() ? -1 : 1;
    }
}
