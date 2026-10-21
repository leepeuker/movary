<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class WatchlistTest extends ApiTestCase
{
    public function testOwnerCanCreateReadAndDeleteWatchlistEntry() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->api
            ->post($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertTrue($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));

        $watchlistResponse = $this->api
            ->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertSame(1, $watchlistResponse['currentPage']);
        self::assertSame(1, $watchlistResponse['maxPage']);
        self::assertCount(1, $watchlistResponse['watchlist']);
        self::assertSame($movie->getTitle(), $watchlistResponse['watchlist'][0]['movie']['title']);
        self::assertSame($movie->getId(), $watchlistResponse['watchlist'][0]['movie']['ids']['movary']);
        self::assertSame($movie->getTmdbId(), $watchlistResponse['watchlist'][0]['movie']['ids']['tmdb']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',
            $watchlistResponse['watchlist'][0]['addedAt'],
        );

        $this->api
            ->delete($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertFalse($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));
        self::assertSame([], $this->api->get($path, $headers)->assertStatus(200)->json()['watchlist']);
    }

    #[DataProvider('writeMethodProvider')]
    public function testRejectsUnauthenticatedWrites(string $method) : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->api
            ->request($method, $this->createPath($user->getName()), body: [])
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testRejectsUnauthenticatedRead() : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->api
            ->get($this->createPath($user->getName()))
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testRejectsInvalidToken() : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->api
            ->get($this->createPath($user->getName()), $this->createAuthorizationHeader('invalid-token'))
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testCannotReadPrivateWatchlistOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken(0);
        $authenticatedUser = $this->fixture->createUserWithToken();

        $this->api
            ->get(
                $this->createPath($target['user']->getName()),
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(403);
    }

    public function testCannotWriteWatchlistOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken();
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($target['user']->getName()),
                [['movaryId' => $movie->getId()]],
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(403);

        self::assertFalse($this->fixture->hasMovieInWatchlist($movie->getId(), $target['user']->getId()));
    }

    public function testReturnsNotFoundForUnknownUser() : void
    {
        $authenticatedUser = $this->fixture->createUserWithToken();

        $this->api
            ->get(
                $this->createPath('UnknownApiUser'),
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(404);
    }

    public function testDeletingUnknownMovieIsIdempotent() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->api
            ->delete(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => 2_147_483_647]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertFalse($this->fixture->hasMovieInWatchlist(2_147_483_647, $fixture['user']->getId()));
    }

    /** @return iterable<string, array{string}> */
    public static function writeMethodProvider() : iterable
    {
        yield 'post' => ['POST'];
        yield 'delete' => ['DELETE'];
    }

    private function createPath(string $username) : string
    {
        return '/api/users/' . $username . '/watchlist/movies';
    }
}
