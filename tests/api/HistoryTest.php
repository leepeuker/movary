<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class HistoryTest extends ApiTestCase
{
    private const string WATCHED_AT = '2024-05-06';

    public function testOwnerCanCreateReadUpdateAndDeleteHistory() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->api
            ->post($path, [[
                'movaryId' => $movie->getId(),
                'watchedAt' => self::WATCHED_AT,
                'plays' => 2,
                'position' => 1,
                'comment' => 'Created through the API',
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $createdEntry = $this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        );
        self::assertNotNull($createdEntry);
        self::assertSame(2, $createdEntry->getPlays());
        self::assertSame(1, $createdEntry->getPosition());
        self::assertSame('Created through the API', $createdEntry->getComment());

        $historyResponse = $this->api
            ->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertSame(1, $historyResponse['currentPage']);
        self::assertSame(1, $historyResponse['maxPage']);
        self::assertCount(1, $historyResponse['history']);
        self::assertSame(self::WATCHED_AT, $historyResponse['history'][0]['watchedAt']);
        self::assertSame($movie->getTitle(), $historyResponse['history'][0]['movie']['title']);
        self::assertSame($movie->getId(), $historyResponse['history'][0]['movie']['ids']['movary']);
        self::assertSame($movie->getTmdbId(), $historyResponse['history'][0]['movie']['ids']['tmdb']);

        $this->api
            ->put($path, [[
                'movaryId' => $movie->getId(),
                'watchedAt' => self::WATCHED_AT,
                'plays' => 3,
                'position' => 2,
                'comment' => 'Updated through the API',
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $updatedEntry = $this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        );
        self::assertNotNull($updatedEntry);
        self::assertSame(3, $updatedEntry->getPlays());
        self::assertSame(2, $updatedEntry->getPosition());
        self::assertSame('Updated through the API', $updatedEntry->getComment());

        $this->api
            ->delete($path, [[
                'movaryId' => $movie->getId(),
                'watchedAt' => self::WATCHED_AT,
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        ));
        self::assertSame([], $this->api->get($path, $headers)->assertStatus(200)->json()['history']);
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

    public function testCannotReadPrivateHistoryOfAnotherUser() : void
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

    public function testCannotWriteHistoryOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken();
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($target['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchedAt' => self::WATCHED_AT,
                ]],
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(403);

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $target['user']->getId(),
            self::WATCHED_AT,
        ));
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

    /** @return iterable<string, array{string}> */
    public static function writeMethodProvider() : iterable
    {
        yield 'post' => ['POST'];
        yield 'put' => ['PUT'];
        yield 'delete' => ['DELETE'];
    }

    /** @return array{Authorization: string} */
    private function createAuthorizationHeader(string $token) : array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function createPath(string $username) : string
    {
        return '/api/users/' . $username . '/history/movies';
    }
}
