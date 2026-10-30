<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class DashboardOverviewTest extends HttpTestCase
{
    public function testOverviewReflectsPlaysAndPersonalRating() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $username = $fixture['user']->getName();
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $path = $this->createPath($username);

        $initial = $this->http->get($path, $headers)->assertStatus(200)->json();
        self::assertSame(0, $initial['totalPlayCount']);
        self::assertSame(0, $initial['uniqueMoviesCount']);

        $playsPath = '/api/v1/users/' . $username . '/plays';
        $this->http->post($playsPath, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)
            ->assertStatus(201);
        $this->http->post($playsPath, ['movaryId' => $movie->getId(), 'watchedAt' => null], $headers)
            ->assertStatus(201);
        $this->http->put(
            '/api/v1/users/' . $username . '/movies/' . $movie->getId() . '/rating',
            ['rating' => 8],
            $headers,
        )->assertStatus(204);

        $response = $this->http->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->assertHeaderSame('Pragma', 'no-cache');
        $overview = $response->json();
        self::assertSame(2, $overview['totalPlayCount']);
        self::assertSame(1, $overview['uniqueMoviesCount']);
        self::assertEquals(8, $overview['averagePersonalRating']);
        self::assertEqualsCanonicalizing([
            'totalPlayCount',
            'uniqueMoviesCount',
            'totalHoursWatched',
            'averagePlaysPerDay',
            'averageRuntime',
            'averagePersonalRating',
        ], array_keys($overview));
    }

    public function testReadRequiresTokenAndRespectsPrivateUser() : void
    {
        $owner = $this->fixture->createUserWithToken(0);
        $other = $this->fixture->createUserWithToken();
        $path = $this->createPath($owner['user']->getName());

        $this->http->get($path)->assertStatus(401)->assertHeaderSame('WWW-Authenticate', 'Bearer');
        $this->http->get($path, $this->createAuthorizationHeader($other['token']))->assertStatus(403);
        $this->http->get($this->createPath('UnknownApiUser'), $this->createAuthorizationHeader($other['token']))
            ->assertStatus(404);
    }

    #[DataProvider('sharedPrivacyLevelProvider')]
    public function testAnotherTokenCanReadSharedOverview(int $privacyLevel) : void
    {
        $owner = $this->fixture->createUserWithToken($privacyLevel);
        $other = $this->fixture->createUserWithToken();

        $this->http->get($this->createPath($owner['user']->getName()), $this->createAuthorizationHeader($other['token']))
            ->assertStatus(200);
    }

    /** @return iterable<string, array{int}> */
    public static function sharedPrivacyLevelProvider() : iterable
    {
        yield 'visible to users' => [1];
        yield 'visible to everyone' => [2];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/dashboard/overview';
    }
}
