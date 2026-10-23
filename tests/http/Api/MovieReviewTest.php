<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class MovieReviewTest extends HttpTestCase
{
    public function testOwnerCanCreateReadUpdateAndDeleteReview() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();
        $path = $this->createPath($fixture['user']->getName(), $movie->getId());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http
            ->put($path, ['review' => 'Initial review', 'containsSpoilers' => true], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $review = $this->fixture->findMovieReview($movie->getId(), $fixture['user']->getId());
        self::assertNotNull($review);
        self::assertSame('Initial review', $review->getReview());
        self::assertTrue($review->containsSpoilers());

        $response = $this->http
            ->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->assertHeaderSame('Pragma', 'no-cache')
            ->json();

        self::assertSame('Initial review', $response['review']);
        self::assertTrue($response['containsSpoilers']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $response['createdAt']);
        self::assertNull($response['updatedAt']);

        $this->http
            ->put($path, ['review' => 'Updated review'], $headers)
            ->assertStatus(204);

        $updatedReview = $this->fixture->findMovieReview($movie->getId(), $fixture['user']->getId());
        self::assertNotNull($updatedReview);
        self::assertSame('Updated review', $updatedReview->getReview());
        self::assertFalse($updatedReview->containsSpoilers());
        self::assertNotNull($updatedReview->getUpdatedAt());

        $this->http
            ->request('DELETE', $path, $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        self::assertNull($this->fixture->findMovieReview($movie->getId(), $fixture['user']->getId()));
        $this->http->get($path, $headers)->assertStatus(404);
    }

    #[DataProvider('methodProvider')]
    public function testRejectsUnauthenticatedRequests(string $method) : void
    {
        $user = $this->fixture->createUserWithToken()['user'];
        $movie = $this->fixture->createMovie();

        $this->http
            ->request($method, $this->createPath($user->getName(), $movie->getId()), body: [])
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    #[DataProvider('methodProvider')]
    public function testCannotAccessAnotherUsersReview(string $method) : void
    {
        $target = $this->fixture->createUserWithToken();
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->request(
                $method,
                $this->createPath($target['user']->getName(), $movie->getId()),
                $this->createAuthorizationHeader($authenticatedUser['token']),
                ['review' => 'Should not be saved'],
            )
            ->assertStatus(403);

        self::assertNull($this->fixture->findMovieReview($movie->getId(), $target['user']->getId()));
    }

    public function testReturnsNotFoundForUnknownUser() : void
    {
        $authenticatedUser = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->get(
                $this->createPath('UnknownApiUser', $movie->getId()),
                $this->createAuthorizationHeader($authenticatedUser['token']),
            )
            ->assertStatus(404);
    }

    #[DataProvider('methodProvider')]
    public function testReturnsNotFoundForUnknownMovie(string $method) : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->request(
                $method,
                $this->createPath($fixture['user']->getName(), 2_147_483_647),
                $this->createAuthorizationHeader($fixture['token']),
                ['review' => 'Review'],
            )
            ->assertStatus(404);
    }

    public function testDeletingMissingReviewIsIdempotent() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->request(
                'DELETE',
                $this->createPath($fixture['user']->getName(), $movie->getId()),
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(204);
    }

    public function testRejectsMalformedJson() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->requestRaw(
                'PUT',
                $this->createPath($fixture['user']->getName(), $movie->getId()),
                '{',
                [...$this->createAuthorizationHeader($fixture['token']), 'Content-Type' => 'application/json'],
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidJson',
                'message' => 'Request body must contain a valid JSON object.',
            ]);
    }

    public function testRejectsMissingReview() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->requestRaw(
                'PUT',
                $this->createPath($fixture['user']->getName(), $movie->getId()),
                '{}',
                [...$this->createAuthorizationHeader($fixture['token']), 'Content-Type' => 'application/json'],
            )
            ->assertStatus(400)
            ->assertJsonSame([
                'error' => 'InvalidRequest',
                'message' => 'Field "review" is required and must be a non-empty string.',
            ]);
    }

    /** @param array<string, mixed> $body */
    #[DataProvider('invalidReviewProvider')]
    public function testRejectsInvalidReview(array $body, string $message) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $movie = $this->fixture->createMovie();

        $this->http
            ->put(
                $this->createPath($fixture['user']->getName(), $movie->getId()),
                $body,
                $this->createAuthorizationHeader($fixture['token']),
            )
            ->assertStatus(400)
            ->assertJsonSame(['error' => 'InvalidRequest', 'message' => $message]);

        self::assertNull($this->fixture->findMovieReview($movie->getId(), $fixture['user']->getId()));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidReviewProvider() : iterable
    {
        yield 'blank review' => [
            ['review' => '  '],
            'Field "review" is required and must be a non-empty string.',
        ];
        yield 'review too long' => [
            ['review' => str_repeat('a', 10_001)],
            'Field "review" must not exceed 10000 characters.',
        ];
        yield 'invalid spoiler flag' => [
            ['review' => 'Review', 'containsSpoilers' => 1],
            'Field "containsSpoilers" must be a boolean.',
        ];
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
        return '/api/v1/users/' . $username . '/movies/' . $movieId . '/review';
    }
}
