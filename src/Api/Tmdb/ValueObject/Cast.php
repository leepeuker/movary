<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\ValueObject;

use Movary\AbstractList;

/**
 * @method CastMember[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class Cast extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $cast = new self();

        foreach ($data as $castMember) {
            $cast->add(CastMember::createFromArray($castMember));
        }

        return $cast;
    }

    private function add(CastMember $member) : void
    {
        $this->data[] = $member;
    }
}
