<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\AbstractList;

/**
 * @method TmdbCrewMember[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class TmdbCrew extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $cast = new self();

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
