<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class MovieAddTest extends ApiTestCase
{
    private const string ENDPOINT = '/api/movies/add';

    public function testRejectsUnauthenticatedRequest() : void
    {
        $this->api
            ->requestRaw('POST', self::ENDPOINT, '{"tmdbId":42}', ['Content-Type' => 'application/json'])
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer');
    }

    /** @param array{error: string, message: string} $expectedError */
    #[DataProvider('invalidBodyProvider')]
    public function testRejectsInvalidBody(string $body, array $expectedError) : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->api
            ->requestRaw(
                'POST',
                self::ENDPOINT,
                $body,
                [...$this->createAuthorizationHeader($fixture['token']), 'Content-Type' => 'application/json'],
            )
            ->assertStatus(400)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertJsonSame($expectedError);
    }

    public static function invalidBodyProvider() : array
    {
        return [
            'malformed JSON' => [
                '{',
                ['error' => 'InvalidJson', 'message' => 'Request body must contain a valid JSON object.'],
            ],
            'JSON list' => [
                '[{"tmdbId":42}]',
                ['error' => 'InvalidJson', 'message' => 'Request body must contain a valid JSON object.'],
            ],
            'missing tmdbId' => [
                '{}',
                ['error' => 'MissingProviderId', 'message' => 'No external provider id submitted'],
            ],
            'string tmdbId' => [
                '{"tmdbId":"42"}',
                ['error' => 'InvalidRequest', 'message' => 'Field "tmdbId" must be a positive integer.'],
            ],
            'zero tmdbId' => [
                '{"tmdbId":0}',
                ['error' => 'InvalidRequest', 'message' => 'Field "tmdbId" must be a positive integer.'],
            ],
        ];
    }
}
