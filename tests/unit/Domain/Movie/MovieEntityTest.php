<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Movary\Domain\Movie\MovieEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieEntity::class)]
class MovieEntityTest extends TestCase
{
    public function testEffectivePosterPathUsesOverride() : void
    {
        $movie = MovieEntity::createFromArray($this->createMovieData('/default.jpg', '/override.jpg'));

        self::assertSame('/override.jpg', $movie->getTmdbPosterPathEffective());
        self::assertSame('/override.jpg', $movie->getTmdbPosterPathOverride());
    }

    public function testEffectivePosterPathFallsBackToTmdbDefault() : void
    {
        $movie = MovieEntity::createFromArray($this->createMovieData('/default.jpg', null));

        self::assertSame('/default.jpg', $movie->getTmdbPosterPathEffective());
        self::assertNull($movie->getTmdbPosterPathOverride());
    }

    public function testBackdropPathIsReadWithoutChangingPoster() : void
    {
        $data = $this->createMovieData('/poster.jpg', null);
        $data['tmdb_backdrop_path'] = '/backdrop.jpg';
        $movie = MovieEntity::createFromArray($data);

        self::assertSame('/backdrop.jpg', $movie->getTmdbBackdropPath());
        self::assertSame('/poster.jpg', $movie->getTmdbPosterPathEffective());
        self::assertNull(MovieEntity::createFromArray($this->createMovieData(null, null))->getTmdbBackdropPath());
    }

    private function createMovieData(?string $defaultPoster, ?string $overridePoster) : array
    {
        return [
            'id' => 12,
            'title' => 'Movie',
            'original_title' => null,
            'trakt_id' => null,
            'imdb_id' => null,
            'tmdb_id' => 34,
            'poster_path' => null,
            'overview' => null,
            'tagline' => null,
            'original_language' => 'en',
            'runtime' => 90,
            'release_date' => null,
            'tmdb_vote_average' => null,
            'tmdb_vote_count' => null,
            'tmdb_poster_path' => $defaultPoster,
            'tmdb_poster_path_override' => $overridePoster,
            'imdb_rating_average' => null,
            'imdb_rating_vote_count' => null,
            'updated_at_tmdb' => null,
            'updated_at_imdb' => null,
        ];
    }
}
