<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class PlayTest extends HttpTestCase
{
    public function testOwnerCanReadUpdateAndDeleteOneOfSeveralSameDayPlays() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($this->createHistoryPath($fixture['user']->getName()), [[
            'movaryId' => $movie->getId(),
            'watchedAt' => '2026-10-29',
            'plays' => 2,
            'comment' => 'First play',
        ]], $headers)->assertStatus(204);

        $response = $this->http->get($path . '?limit=1', $headers)->assertStatus(200)->json();
        self::assertSame(2, $response['maxPage']);
        self::assertCount(1, $response['plays']);
        $secondId = $response['plays'][0]['id'];
        $first = $this->http->get($path . '?limit=1&page=2', $headers)->assertStatus(200)->json()['plays'][0];
        self::assertNotSame($first['id'], $secondId);
        self::assertSame('First play', $first['comment']);

        $this->http->put($path . '/' . $secondId, [
            'watchedAt' => null,
            'position' => 3,
            'comment' => 'Second play',
            'locationId' => null,
        ], $headers)->assertStatus(204);

        $updated = $this->http->get($path . '/' . $secondId, $headers)->assertStatus(200)->json();
        self::assertNull($updated['watchedAt']);
        self::assertSame(3, $updated['position']);
        self::assertSame('Second play', $updated['comment']);
        self::assertSame($movie->getId(), $updated['movie']['ids']['movary']);
        $allPlays = $this->http->get($path, $headers)->assertStatus(200)->json()['plays'];
        self::assertIsArray($allPlays);
        self::assertCount(2, $allPlays);
        self::assertSame($secondId, $allPlays[1]['id']);
        self::assertNull($allPlays[1]['watchedAt']);

        $this->http->delete($path . '/' . $secondId, [], $headers)->assertStatus(204);
        $this->http->get($path . '/' . $secondId, $headers)->assertStatus(404);
        self::assertSame(1, $this->fixture->countHistoryPlays($movie->getId(), $fixture['user']->getId(), '2026-10-29'));
    }

    public function testOtherUserCannotMutatePlayAndPrivatePlayIsHidden() : void
    {
        $owner = $this->fixture->createUserWithToken(0);
        $other = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $this->http->post($this->createHistoryPath($owner['user']->getName()), [[
            'movaryId' => $movie->getId(),
            'watchedAt' => '2026-10-29',
        ]], $this->createAuthorizationHeader($owner['token']))->assertStatus(204);
        $playId = $this->fixture->findHistoryEntry($movie->getId(), $owner['user']->getId(), '2026-10-29')?->getId();
        self::assertNotNull($playId);
        $path = $this->createPath($owner['user']->getName()) . '/' . $playId;
        $headers = $this->createAuthorizationHeader($other['token']);

        $this->http->get($path, $headers)->assertStatus(403);
        $this->http->put($path, [
            'watchedAt' => null,
            'position' => 1,
            'comment' => null,
            'locationId' => null,
        ], $headers)->assertStatus(403);
        $this->http->delete($path, [], $headers)->assertStatus(403);
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/history/plays';
    }

    private function createHistoryPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/history/movies';
    }
}
