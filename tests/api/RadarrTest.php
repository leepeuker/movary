<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class RadarrTest extends ApiTestCase
{
    public function testRejectsUnknownFeedId() : void
    {
        $this->api
            ->get('/feeds/radarr/00000000-0000-4000-8000-000000000000')
            ->assertStatus(404)
            ->assertBodySame('');
    }

    public function testReturnsWatchlistForValidFeedIdWithoutAuthentication() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $this->fixture->addMovieToWatchlist($movie->getId(), $fixture['user']->getId());
        $feedId = $this->fixture->createRadarrFeedId($fixture['user']->getId());

        $response = $this->api
            ->get('/feeds/radarr/' . $feedId)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertCount(1, $response);
        self::assertSame($movie->getTitle(), $response[0]['title']);
        self::assertSame($movie->getTmdbId(), $response[0]['tmdb_id']);
        self::assertNull($response[0]['imdb_id']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',
            $response[0]['added_at'],
        );
    }
}
