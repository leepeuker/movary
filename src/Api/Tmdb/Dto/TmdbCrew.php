<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<TmdbCrewMember>
 */
class TmdbCrew extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $cast = self::create();

        foreach ($data as $crewMember) {
            $cast->add(TmdbCrewMember::createFromArray($crewMember));
        }

        return $cast;
    }

    private function add(TmdbCrewMember $member) : void
    {
        $this->data[] = $member;
    }
}
