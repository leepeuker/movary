<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\ValueObject;

use Movary\AbstractList;

/**
 * @method CrewMember[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class Crew extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $cast = new self();

        foreach ($data as $crewMember) {
            $cast->add(CrewMember::createFromArray($crewMember));
        }

        return $cast;
    }

    private function add(CrewMember $member) : void
    {
        $this->data[] = $member;
    }
}
