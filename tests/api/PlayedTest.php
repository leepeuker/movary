<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class PlayedTest extends ApiTestCase
{
    private const string WATCHED_AT = '2024-05-06';

    public function testOwnerCanCreateReadUpdateAndDeletePlayedMovie() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->api
            ->post($path, [[
                'movaryId' => $movie->getId(),
                'watchDates' => [
                    [
                        'watchedAt' => self::WATCHED_AT,
                        'plays' => 2,
                        'comment' => 'Dated play',
                    ],
                    [
                        'watchedAt' => null,
                        'plays' => 1,
                        'comment' => 'Undated play',
                    ],
                ],
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $datedEntry = $this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        );
        self::assertNotNull($datedEntry);
        self::assertSame(2, $datedEntry->getPlays());
        self::assertSame('Dated play', $datedEntry->getComment());

        $undatedEntry = $this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            null,
        );
        self::assertNotNull($undatedEntry);
        self::assertSame(1, $undatedEntry->getPlays());
        self::assertSame('Undated play', $undatedEntry->getComment());

        $playedResponse = $this->api
            ->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertSame(1, $playedResponse['currentPage']);
        self::assertSame(1, $playedResponse['maxPage']);
        self::assertCount(1, $playedResponse['played']);
        self::assertSame($movie->getTitle(), $playedResponse['played'][0]['movie']['title']);
        self::assertSame($movie->getId(), $playedResponse['played'][0]['movie']['ids']['movary']);
        self::assertCount(2, $playedResponse['played'][0]['watchDates']);
        self::assertContains(
            ['date' => self::WATCHED_AT, 'plays' => 2, 'comment' => 'Dated play'],
            $playedResponse['played'][0]['watchDates'],
        );
        self::assertContains(
            ['date' => null, 'plays' => 1, 'comment' => 'Undated play'],
            $playedResponse['played'][0]['watchDates'],
        );

        $this->api
            ->put($path, [[
                'movaryId' => $movie->getId(),
                'watchDates' => [[
                    'watchedAt' => self::WATCHED_AT,
                    'plays' => 4,
                    'comment' => 'Updated dated play',
                ]],
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $updatedEntry = $this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        );
        self::assertNotNull($updatedEntry);
        self::assertSame(4, $updatedEntry->getPlays());
        self::assertSame('Updated dated play', $updatedEntry->getComment());

        $this->api
            ->delete($path, [[
                'movaryId' => $movie->getId(),
                'watchDates' => [self::WATCHED_AT],
            ]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            self::WATCHED_AT,
        ));
        self::assertNotNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            null,
        ));

        $this->api
            ->delete($path, [['movaryId' => $movie->getId()]], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertNull($this->fixture->findHistoryEntry(
            $movie->getId(),
            $fixture['user']->getId(),
            null,
        ));
        self::assertSame([], $this->api->get($path, $headers)->assertStatus(200)->json()['played']);
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

    public function testCannotReadPrivatePlayedMoviesOfAnotherUser() : void
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

    public function testCannotWritePlayedMoviesOfAnotherUser() : void
    {
        $target = $this->fixture->createUserWithToken();
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($target['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchDates' => [['watchedAt' => self::WATCHED_AT]],
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

    public function testRejectsMalformedJson() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->api
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

    public function testRejectsInvalidNestedWatchDate() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => $movie->getId(), 'watchDates' => ['not-an-object']]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchDates[0]" must be an object.',
            ]);
    }

    public function testAcceptsAdditionDefaultsForNullableWatchDate() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($fixture['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchDates' => [['watchedAt' => null, 'plays' => null]],
                ]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(204);

        $entry = $this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), null);
        self::assertNotNull($entry);
        self::assertSame(1, $entry->getPlays());
        self::assertNull($entry->getComment());
    }

    public function testAcceptsUpdateWithoutOptionalComment() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->api->post($path, [[
            'movaryId' => $movie->getId(),
            'watchDates' => [[
                'watchedAt' => self::WATCHED_AT,
                'comment' => 'Keep me',
            ]],
        ]], $headers)->assertStatus(204);

        $this->api->put($path, [[
            'movaryId' => $movie->getId(),
            'watchDates' => [[
                'watchedAt' => self::WATCHED_AT,
                'plays' => 2,
            ]],
        ]], $headers)->assertStatus(204);

        $entry = $this->fixture->findHistoryEntry($movie->getId(), $fixture['user']->getId(), self::WATCHED_AT);
        self::assertNotNull($entry);
        self::assertSame(2, $entry->getPlays());
        self::assertSame('Keep me', $entry->getComment());
    }

    public function testRejectsUpdateWithoutPlays() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->put(
                $this->createPath($fixture['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchDates' => [['watchedAt' => self::WATCHED_AT]],
                ]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchDates[0].plays" must be a positive integer.',
            ]);
    }

    public function testRejectsInvalidDeletionDate() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->delete(
                $this->createPath($fixture['user']->getName()),
                [['movaryId' => $movie->getId(), 'watchDates' => ['not-a-date']]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchDates[0]" must be a date in YYYY-MM-DD format or null.',
            ]);
    }

    public function testRejectsUnknownMovieWithoutPartiallyApplyingRequest() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($fixture['user']->getName()),
                [
                    [
                        'movaryId' => $movie->getId(),
                        'watchDates' => [['watchedAt' => self::WATCHED_AT]],
                    ],
                    [
                        'movaryId' => 2_147_483_647,
                        'watchDates' => [['watchedAt' => self::WATCHED_AT]],
                    ],
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

    public function testRejectsOversizedNestedValueWithoutPartiallyApplyingRequest() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->api
            ->post(
                $this->createPath($fixture['user']->getName()),
                [[
                    'movaryId' => $movie->getId(),
                    'watchDates' => [
                        ['watchedAt' => self::WATCHED_AT],
                        ['watchedAt' => '2024-05-07', 'plays' => 32768],
                    ],
                ]],
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "items[0].watchDates[1].plays" must be at most 32767.',
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
        return '/api/users/' . $username . '/played/movies';
    }
}
