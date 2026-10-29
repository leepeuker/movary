<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class HistoryTest extends HttpTestCase
{
    private const string WATCHED_AT = '2024-05-06';

    public function testRepeatedSameDayAdditionsRemainOnePaginatedHistoryEntry() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'plays' => 2,
        ]], $headers)->assertStatus(204);
        $this->http->post($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
        ]], $headers)->assertStatus(204);

        $entry = $this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT);
        self::assertNotNull($entry);
        self::assertSame(3, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT));

        $response = $this->http->get($path . '?limit=1', $headers)->assertStatus(200)->json();
        self::assertSame(1, $response['currentPage']);
        self::assertSame(1, $response['maxPage']);
        self::assertCount(1, $response['history']);
        self::assertSame(self::WATCHED_AT, $response['history'][0]['watchedAt']);
        self::assertSame($movie->getId(), $response['history'][0]['movie']['ids']['movary']);
    }

    public function testOwnerCanCreateReadUpdateAndDeleteHistory() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http
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
        self::assertSame(2, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT));
        self::assertSame(1, $createdEntry->getPosition());
        self::assertSame('Created through the API', $createdEntry->getComment());

        $historyResponse = $this->http
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

        $this->http
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
        self::assertSame(3, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT));
        self::assertSame(2, $updatedEntry->getPosition());
        self::assertSame('Updated through the API', $updatedEntry->getComment());

        $this->http
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
        self::assertSame([], $this->http->get($path, $headers)->assertStatus(200)->json()['history']);
    }

    public function testRejectsReductionThatWouldDeleteAnAnnotatedPlay() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        foreach (['First comment', 'Second comment'] as $comment) {
            $this->http->post($path, [[
                'movaryId' => $movie->getId(),
                'watchedAt' => self::WATCHED_AT,
                'comment' => $comment,
            ]], $headers)->assertStatus(204);
        }

        $this->http->put($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'plays' => 1,
        ]], $headers)->assertStatus(409);

        self::assertSame(2, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT));
    }

    public function testExplicitNullCommentClearsCanonicalPlayComment() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'comment' => 'Remove me',
        ]], $headers)->assertStatus(204);
        $this->http->put($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'plays' => 1,
            'comment' => null,
        ]], $headers)->assertStatus(204);

        self::assertNull($this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT)?->getComment());
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

    public function testCannotReadPrivateHistoryOfAnotherUser() : void
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

    public function testCannotWriteHistoryOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken();
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
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

        $this->http
            ->get(
                $this->createPath('UnknownApiUser'),
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(404);
    }

    public function testRejectsInvalidSortOrder() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->get(
                $this->createPath($fixture['user']->getName()) . '?sortOrder=unsupported',
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "sortOrder" must be one of: asc, desc.',
            ]);
    }

    public function testRejectsMalformedJson() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->requestRaw(
                'POST',
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

    public function testRejectsInvalidAdditionFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->post(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => $movie->getId(), 'watchedAt' => 'not-a-date']],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchedAt" must be a date in YYYY-MM-DD format.',
            ]);
    }

    public function testRejectsInvalidUpdateFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->put(
                $this->createPath($fixture['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchedAt' => self::WATCHED_AT,
                    'position' => 1,
                ]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].plays" must be a positive integer.',
            ]);
    }

    public function testAcceptsNullOptionalPositionOnAddition() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->post(
                $this->createPath($fixture['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchedAt' => self::WATCHED_AT,
                    'position' => null,
                ]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(204);

        $entry = $this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT);
        self::assertNotNull($entry);
        self::assertSame(1, $entry->getPosition());
    }

    public function testAcceptsUpdateWithoutOptionalFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'position' => 2,
            'comment' => 'Keep me',
        ]], $headers)->assertStatus(204);

        $this->http->put($path, [[
            'movaryId' => $movie->getId(),
            'watchedAt' => self::WATCHED_AT,
            'plays' => 2,
        ]], $headers)->assertStatus(204);

        $entry = $this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT);
        self::assertNotNull($entry);
        self::assertSame(2, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT));
        self::assertSame(2, $entry->getPosition());
        self::assertSame('Keep me', $entry->getComment());
    }

    public function testRejectsInvalidDeletionFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->delete(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => $movie->getId()]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchedAt" must be a date in YYYY-MM-DD format.',
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
                    ['movaryId' => $movie->getId(), 'watchedAt' => self::WATCHED_AT],
                    ['movaryId' => 2_147_483_647, 'watchedAt' => self::WATCHED_AT],
                ],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'MovieNotFound',
                'message' => 'Movie with id 2147483647 does not exist.',
            ]);

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        ));
    }

    public function testRejectsOversizedValueWithoutPartiallyApplyingRequest() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->post(
                $this->createPath($fixture['user']->getName()),
                [
                    ['movaryId' => $movie->getId(), 'watchedAt' => self::WATCHED_AT],
                    ['movaryId' => $movie->getId(), 'watchedAt' => '2024-05-07', 'plays' => 32768],
                ],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[1].plays" must be at most 32767.',
            ]);

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        ));
    }

    /** @return iterable<string, array{string}> */
    public static function writeMethodProvider() : iterable
    {
        yield 'post' => ['POST'];
        yield 'put' => ['PUT'];
        yield 'delete' => ['DELETE'];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/history/movies';
    }
}
