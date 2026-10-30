<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class PlayedTest extends HttpTestCase
{
    public function testPlayedMovieReadGroupsDatedAndUnknownPlays() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $playsPath = '/api/v1/users/' . $fixture['user']->getName() . '/plays';
        $playedPath = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->post($playsPath, ['movaryId' => $movie->getId(), 'watchedAt' => '2026-10-29', 'comment' => 'First'], $headers)->assertStatus(201);
        $this->http->post($playsPath, ['movaryId' => $movie->getId(), 'watchedAt' => '2026-10-29'], $headers)->assertStatus(201);
        $this->http->post($playsPath, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)->assertStatus(201);

        $response = $this->http->get($playedPath, $headers)->assertStatus(200)->json();
        self::assertSame(1, $response['currentPage']);
        self::assertSame(1, $response['maxPage']);
        self::assertCount(1, $response['played']);
        self::assertSame($movie->getId(), $response['played'][0]['movie']['ids']['movary']);
        self::assertContains(['date' => '2026-10-29', 'plays' => 2, 'comment' => 'First'], $response['played'][0]['watchDates']);
        self::assertContains(['date' => null, 'plays' => 1, 'comment' => null], $response['played'][0]['watchDates']);
    }

    public function testPlayedMovieReadKeepsMovieLevelSearch() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->post('/api/v1/users/' . $fixture['user']->getName() . '/plays', [
            'movaryId' => $movie->getId(),
            'watchedAt' => null,
        ], $headers)->assertStatus(201);

        $response = $this->http->get(
            $this->createPath($fixture['user']->getName()) . '?search=' . rawurlencode($movie->getTitle()),
            $headers,
        )->assertStatus(200)->json();
        self::assertCount(1, $response['played']);
    }

    public function testReadRequiresTokenAndRespectsPrivateUser() : void
    {
        $owner = $this->fixture->createUserWithToken(0);
        $other = $this->fixture->createUserWithToken();
        $path = $this->createPath($owner['user']->getName());

        $this->http->get($path)->assertStatus(401);
        $this->http->get($path, $this->createAuthorizationHeader('invalid-token'))->assertStatus(401);
        $this->http->get($path, $this->createAuthorizationHeader($other['token']))->assertStatus(403);
        $this->http->get($this->createPath('UnknownApiUser'), $this->createAuthorizationHeader($other['token']))->assertStatus(404);
    }

    public function testOldPlayedMoviePathIsUnavailable() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $this->http->get(
            '/api/v1/users/' . $fixture['user']->getName() . '/played/movies',
            $this->createAuthorizationHeader($fixture['token']),
        )->assertStatus(404);
    }

    #[DataProvider('invalidQueryProvider')]
    public function testRejectsInvalidMovieQuery(string $query) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $this->http->get(
            $this->createPath($fixture['user']->getName()) . '?' . $query,
            $this->createAuthorizationHeader($fixture['token']),
        )->assertStatus(400);
    }

    public static function invalidQueryProvider() : array
    {
        return [['page=0'], ['limit=101'], ['sortBy=position'], ['releaseYear=1900']];
    }

    #[DataProvider('removedWriteProvider')]
    public function testGroupedPlayedWritesAreUnavailable(string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $this->http->request(
            $method,
            $this->createPath($fixture['user']->getName()),
            $this->createAuthorizationHeader($fixture['token']),
            [],
        )->assertStatus(405);
    }

    public static function removedWriteProvider() : array
    {
        return [['POST'], ['PUT'], ['DELETE']];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/plays/movies';
    }
}
