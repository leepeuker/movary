<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class MovieRatingTest extends HttpTestCase
{
    public function testOwnerCanCreateReadUpdateAndDeleteRating() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName(), $movie->getId());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->get($path, $headers)->assertStatus(404);
        $this->http->put($path, ['rating' => 8], $headers)->assertStatus(204)->assertBodySame('');
        $this->http->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->assertHeaderSame('Pragma', 'no-cache')
            ->assertJsonSame(['rating' => 8]);

        $this->http->put($path, ['rating' => 10], $headers)->assertStatus(204);
        $this->http->get($path, $headers)->assertJsonSame(['rating' => 10]);

        $this->http->request('DELETE', $path, $headers)->assertStatus(204)->assertBodySame('');
        $this->http->get($path, $headers)->assertStatus(404);
        $this->http->request('DELETE', $path, $headers)->assertStatus(204);
    }

    #[DataProvider('invalidRatingProvider')]
    public function testRejectsInvalidRating(mixed $rating) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName(), $movie->getId());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->put($path, ['rating' => $rating], $headers)
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "rating" must be an integer between 1 and 10.',
            ]);
        $this->http->get($path, $headers)->assertStatus(404);
    }

    public function testRejectsMissingRating() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName(), $movie->getId());

        $this->http->put($path, [], $this->createAuthorizationHeader($fixture['token']))
            ->assertStatus(400);
    }

    #[DataProvider('methodProvider')]
    public function testRejectsUnauthenticatedRequest(string $method) : void
    {
        $user = $this->fixture->createUserWithToken()['user'];
        $movie = $this->fixture->createMovie();

        $this->http->request($method, $this->createPath($user->getName(), $movie->getId()), body: [])
            ->assertStatus(401);
    }

    public function testCannotReadPrivateRatingOrWriteAnotherUsersRating() : void
    {
        $target = $this->fixture->createUserWithToken(0);
        $other = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($target['user']->getName(), $movie->getId());

        $this->http->put($path, ['rating' => 7], $this->createAuthorizationHeader($target['token']))
            ->assertStatus(204);
        $otherHeaders = $this->createAuthorizationHeader($other['token']);
        $this->http->get($path, $otherHeaders)->assertStatus(403);
        $this->http->put($path, ['rating' => 9], $otherHeaders)->assertStatus(403);
        $this->http->request('DELETE', $path, $otherHeaders)->assertStatus(403);
        $this->http->get($path, $this->createAuthorizationHeader($target['token']))
            ->assertJsonSame(['rating' => 7]);
    }

    public function testCanReadSharedRatingButCannotWriteIt() : void
    {
        $target = $this->fixture->createUserWithToken(2);
        $other = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($target['user']->getName(), $movie->getId());

        $this->http->put($path, ['rating' => 7], $this->createAuthorizationHeader($target['token']))
            ->assertStatus(204);
        $this->http->get($path, $this->createAuthorizationHeader($other['token']))
            ->assertStatus(200)
            ->assertJsonSame(['rating' => 7]);
        $this->http->put($path, ['rating' => 9], $this->createAuthorizationHeader($other['token']))
            ->assertStatus(403);
    }

    #[DataProvider('methodProvider')]
    public function testReturnsNotFoundForUnknownMovie(string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http->request(
            $method,
            $this->createPath($fixture['user']->getName(), 2_147_483_647),
            $this->createAuthorizationHeader($fixture['token']),
            ['rating' => 8],
        )->assertStatus(404);
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidRatingProvider() : iterable
    {
        yield 'zero' => [0];
        yield 'eleven' => [11];
        yield 'string' => ['8'];
        yield 'decimal' => [8.5];
        yield 'boolean' => [true];
        yield 'null' => [null];
    }

    /** @return iterable<string, array{string}> */
    public static function methodProvider() : iterable
    {
        yield 'get' => ['GET'];
        yield 'put' => ['PUT'];
        yield 'delete' => ['DELETE'];
    }

    private function createPath(string $username, int $movieId) : string
    {
        return '/api/v1/users/' . $username . '/movies/' . $movieId . '/rating';
    }
}
