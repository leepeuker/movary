<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method TmdbCastMember[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class TmdbCast extends AbstractList
{
    public static function createFromArray(array $data) : self
    {
        $cast = new self();

        foreach ($data as $castMember) {
            $cast->add(TmdbCastMember::createFromArray($castMember));
        }

        return $cast;
    }

    private function add(TmdbCastMember $member) : void
    {
        $this->data[] = $member;
    }
}
