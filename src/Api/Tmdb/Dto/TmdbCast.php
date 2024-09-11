<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<TmdbCastMember>
 */
class TmdbCast extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public static function createFromArray(array $data) : self
    {
        $cast = self::create();

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
