<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbPerson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbPerson::class)]
class TmdbPersonTest extends TestCase
{
    public function testExternalIdsAreMappedAndEmptyValuesCleared() : void
    {
        $person = TmdbPerson::createFromArray([
            'id' => 1,
            'imdb_id' => null,
            'name' => 'Example',
            'biography' => '',
            'birthday' => null,
            'deathday' => null,
            'gender' => 0,
            'popularity' => 1.0,
            'known_for_department' => null,
            'profile_path' => null,
            'place_of_birth' => null,
            'external_ids' => [
                'facebook_id' => 'example.person',
                'instagram_id' => ' example ',
                'tiktok_id' => '',
                'youtube_id' => 'UC123',
                'wikidata_id' => 'Q123',
            ],
        ]);

        self::assertSame([
            'facebook_id' => 'example.person',
            'instagram_id' => 'example',
            'tiktok_id' => null,
            'twitter_id' => null,
            'youtube_id' => 'UC123',
            'wikidata_id' => 'Q123',
        ], $person->getExternalIds());
    }
}
