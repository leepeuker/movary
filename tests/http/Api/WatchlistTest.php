<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class WatchlistTest extends HttpTestCase
{
    public function testOwnerCanCreateReadAndDeleteWatchlistEntry() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http
            ->post($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertTrue($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));

        $watchlistResponse = $this->http
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

        $this->http
            ->delete($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertFalse($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));
        self::assertSame([], $this->http->get($path, $headers)->assertStatus(200)->json()['watchlist']);
    }

    public function testOldMoviesRouteIsRemoved() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $path = $this->createPath($fixture['user']->getName()) . '/movies';
        $headers = $this->createAuthorizationHeader($fixture['token']);

        foreach (['GET', 'POST', 'DELETE'] as $method) {
            $this->http->request($method, $path, $headers, $method === 'GET' ? null : [])->assertStatus(404);
        }
    }

    #[DataProvider('writeMethodProvider')]
    public function testRejectsUnauthenticatedWrites(string $method) : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->http
            ->request($method, $this->createPath($user->getName()), body: [])
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testRejectsUnauthenticatedRead() : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->http
            ->get($this->createPath($user->getName()))
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testRejectsInvalidToken() : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->http
            ->get($this->createPath($user->getName()), $this->createAuthorizationHeader('invalid-token'))
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    public function testCannotReadPrivateWatchlistOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken(0);
        $authenticatedUser = $this->fixture->createUserWithToken();

        $this->http
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

        $this->http
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

        $this->http
            ->get(
                $this->createPath('UnknownApiUser'),
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(404);
    }

    public function testDeletingUnknownMovieIsIdempotent() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->delete(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => 2_147_483_647]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertFalse($this->fixture->hasMovieInWatchlist(2_147_483_647, $fixture['user']->getId()));
    }

    #[DataProvider('writeMethodProvider')]
    public function testRejectsMalformedJson(string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->requestRaw(
                $method,
                $this->createPath($fixture['user']->getName()),
                '{',
                [...$this->createAuthorizationHeader($fixture['token']), 'Content-Type' => 'application/json'],
            )
            ->assertStatus(400)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertJsonSame([
                'error' => 'InvalidJson',
                'message' => 'Request body must contain a valid JSON array.',
            ]);
    }

    #[DataProvider('writeMethodProvider')]
    public function testRejectsMissingMovieId(string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->requestRaw(
                $method,
                $this->createPath($fixture['user']->getName()),
                '[{}]',
                [...$this->createAuthorizationHeader($fixture['token']), 'Content-Type' => 'application/json'],
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].movaryId" must be a positive integer.',
            ]);
    }

    public function testRejectsUnknownMovieWithoutPartiallyApplyingRequest() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->post(
                $this->createPath($fixture['user']->getName()),
                [
                    ['movaryId' => $movie->getId()],
                    ['movaryId' => 2_147_483_647],
                ],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'MovieNotFound',
                'message' => 'Movie with id 2147483647 does not exist.',
            ]);

        self::assertFalse($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));
    }

    public function testRejectsInvalidRemovalWithoutPartiallyApplyingRequest() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http
            ->post($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204);

        $this->http
            ->delete(
                $path,
                [
                    ['movaryId' => $movie->getId()],
                    ['movaryId' => 0],
                ],
                $headers,
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[1].movaryId" must be a positive integer.',
            ]);

        self::assertTrue($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));
    }

    /** @return iterable<string, array{string}> */
    public static function writeMethodProvider() : iterable
    {
        yield 'post' => ['POST'];
        yield 'delete' => ['DELETE'];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/watchlist';
    }
}
