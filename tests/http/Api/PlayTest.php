<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class PlayTest extends HttpTestCase
{
    public function testOwnerCanCreateReadUpdateAndDeleteIndividualPlays() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $location = $this->fixture->createLocation($fixture['user']->getId());
        $watchlist = '/api/v1/users/' . $fixture['user']->getName() . '/watchlist';
        $this->http->post($watchlist, [['movaryId' => $movie->getId()]], $headers)->assertStatus(204);

        $firstResponse = $this->http->post($path, [
            'movaryId' => $movie->getId(),
            'watchedAt' => '2026-10-29',
            'comment' => 'First play',
            'locationId' => $location->getId(),
        ], $headers)->assertStatus(201)->assertHeaderSame('Content-Type', 'application/json');
        $first = $firstResponse->json();
        $firstResponse->assertHeaderSame('Location', $path . '/' . $first['id']);
        self::assertSame('2026-10-29', $first['watchedAt']);
        self::assertSame('First play', $first['comment']);
        self::assertSame($location->getId(), $first['locationId']);
        self::assertSame($movie->getId(), $first['movie']['ids']['movary']);
        self::assertFalse($this->fixture->hasMovieInWatchlist($movie->getId(), $fixture['user']->getId()));

        $second = $this->http->post($path, [
            'movaryId' => $movie->getId(),
            'watchedAt' => '2026-10-29',
        ], $headers)->assertStatus(201)->json();
        self::assertNotSame($first['id'], $second['id']);
        self::assertSame(2, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), '2026-10-29'));

        $response = $this->http->get($path . '?limit=1', $headers)->assertStatus(200)->json();
        self::assertSame(2, $response['maxPage']);
        self::assertSame($second['id'], $response['plays'][0]['id']);
        self::assertSame($first['id'], $this->http->get($path . '?limit=1&page=2', $headers)->assertStatus(200)->json()['plays'][0]['id']);

        $this->http->put($path . '/' . $second['id'], [
            'watchedAt' => null,
            'position' => 3,
            'comment' => 'Second play',
            'locationId' => null,
        ], $headers)->assertStatus(204);
        $updated = $this->http->get($path . '/' . $second['id'], $headers)->assertStatus(200)->json();
        self::assertNull($updated['watchedAt']);
        self::assertSame(3, $updated['position']);
        self::assertSame('Second play', $updated['comment']);
        self::assertSame(1, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), null));

        $this->http->delete($path . '/' . $second['id'], [], $headers)->assertStatus(204);
        $this->http->get($path . '/' . $second['id'], $headers)->assertStatus(404);
        self::assertSame(1, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), '2026-10-29'));
    }

    public function testUnknownDatesAndSearchAreIncludedInPlayList() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->post($path, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)->assertStatus(201);
        $this->http->post($path, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)->assertStatus(201);

        $plays = $this->http->get($path . '?search=' . rawurlencode($movie->getTitle()), $headers)->assertStatus(200)->json()['plays'];
        self::assertIsArray($plays);
        self::assertCount(2, $plays);
        self::assertNull($plays[0]['watchedAt']);
        self::assertNotSame($plays[0]['id'], $plays[1]['id']);
    }

    public function testOtherUserCannotCreateOrMutatePlaysAndPrivatePlaysAreHidden() : void
    {
        $owner = $this->fixture->createUserWithToken(0);
        $other = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($owner['user']->getName());
        $ownerHeaders = $this->createAuthorizationHeader($owner['token']);
        $otherHeaders = $this->createAuthorizationHeader($other['token']);
        $playId = $this->http->post($path, ['movaryId' => $movie->getId(), 'watchedAt' => null], $ownerHeaders)->assertStatus(201)->json()['id'];

        $this->http->get($path, $otherHeaders)->assertStatus(403);
        $this->http->get($path . '/' . $playId, $otherHeaders)->assertStatus(403);
        $this->http->post($path, ['movaryId' => $movie->getId(), 'watchedAt' => null], $otherHeaders)->assertStatus(403);
        $this->http->put($path . '/' . $playId, [
            'watchedAt' => null,
            'position' => 1,
            'comment' => null,
            'locationId' => null,
        ], $otherHeaders)->assertStatus(403);
        $this->http->delete($path . '/' . $playId, [], $otherHeaders)->assertStatus(403);
        self::assertSame(1, $this->fixture->countHistoryPlays($movie->getId(), $owner['user']->getId(), null));
    }

    public function testAuthenticationAndUnknownUserAreChecked() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $path = $this->createPath($fixture['user']->getName());
        $this->http->get($path)->assertStatus(401)->assertHeaderSame('WWW-Authenticate', 'Bearer');
        $this->http->post($path, ['watchedAt' => null])->assertStatus(401);
        $this->http->get($this->createPath('UnknownApiUser'), $this->createAuthorizationHeader($fixture['token']))->assertStatus(404);
    }

    public function testRejectsMalformedCreationAndIncompleteUpdate() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->requestRaw('POST', $path, '{', $headers)->assertStatus(400);

        $playId = $this->http->post($path, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)->assertStatus(201)->json()['id'];
        $this->http->put($path . '/' . $playId, [
            'watchedAt' => null,
            'position' => 1,
            'comment' => null,
        ], $headers)->assertStatus(400);
        $this->http->get($path . '/' . $playId, $headers)->assertStatus(200);
    }

    public function testUnknownMovieAndOtherUsersLocationAreRejected() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $other = $this->fixture->createUserWithToken();
        $otherLocation = $this->fixture->createLocation($other['user']->getId());
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->post($path, ['movaryId' => 2_147_483_647, 'watchedAt' => null], $headers)->assertStatus(400);
        $this->http->post($path, [
            'movaryId' => $this->fixture->createMovie()->getId(),
            'watchedAt' => null,
            'locationId' => $otherLocation->getId(),
        ], $headers)->assertStatus(400);
        self::assertSame([], $this->http->get($path, $headers)->assertStatus(200)->json()['plays']);
    }

    #[DataProvider('invalidQueryProvider')]
    public function testRejectsInvalidListQuery(string $query) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $this->http->get(
            $this->createPath($fixture['user']->getName()) . '?' . $query,
            $this->createAuthorizationHeader($fixture['token']),
        )->assertStatus(400);
    }

    public static function invalidQueryProvider() : array
    {
        return [['page=0'], ['limit=101'], ['sortOrder=unsupported']];
    }

    #[DataProvider('invalidCreationProvider')]
    public function testRejectsInvalidCreation(array $body, string $field) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $body['movaryId'] = $this->fixture->createMovie()->getId();
        $path = $this->createPath($fixture['user']->getName());
        $response = $this->http->post($path, $body, $this->createAuthorizationHeader($fixture['token']))->assertStatus(400)->json();
        self::assertStringContainsString($field, $response['message']);
    }

    public static function invalidCreationProvider() : array
    {
        return [
            'missing date' => [['movaryId' => 1], 'watchedAt'],
            'invalid date' => [['movaryId' => 1, 'watchedAt' => '2026-02-30'], 'watchedAt'],
            'count is unsupported' => [['movaryId' => 1, 'watchedAt' => null, 'plays' => 2], 'plays'],
            'invalid position' => [['movaryId' => 1, 'watchedAt' => null, 'position' => 0], 'position'],
        ];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/plays';
    }
}
