<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Person;

use Movary\Domain\Person\PersonEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonEntity::class)]
class PersonEntityTest extends TestCase
{
    public function testProfileOverrideTakesPrecedenceOverTmdbDefault() : void
    {
        $data = [
            'id' => 12,
            'name' => 'Person',
            'gender' => 0,
            'known_for_department' => null,
            'tmdb_id' => 34,
            'imdb_id' => null,
            'poster_path' => null,
            'tmdb_poster_path' => '/default.jpg',
            'biography' => null,
            'birth_date' => null,
            'death_date' => null,
            'place_of_birth' => null,
            'updated_at_tmdb' => null,
        ];

        self::assertSame('/default.jpg', PersonEntity::createFromArray($data)->getTmdbPosterPathEffective());
        self::assertSame('/selected.jpg', PersonEntity::createFromArray([
            ...$data,
            'tmdb_poster_path_override' => '/selected.jpg',
        ])->getTmdbPosterPathEffective());
    }
}
