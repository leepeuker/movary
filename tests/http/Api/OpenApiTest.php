<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class OpenApiTest extends HttpTestCase
{
    public function testReturnsRuntimeOpenApiDocumentWithoutAuthentication() : void
    {
        $schema = $this->http
            ->get('/api/v1/openapi')
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertSame('3.0.2', $schema['openapi']);
        self::assertNotSame('', $schema['info']['version']);
        self::assertSame('/', $schema['servers'][0]['url']);
        self::assertArrayHasKey('/api/v1/users/{username}/plays', $schema['paths']);
        self::assertArrayHasKey('post', $schema['paths']['/api/v1/users/{username}/plays']);
        self::assertArrayNotHasKey('/api/v1/users/{username}/history/movies', $schema['paths']);
        self::assertSame(['get'], array_keys($schema['paths']['/api/v1/users/{username}/plays/movies']));
        self::assertArrayNotHasKey('/api/v1/users/{username}/played/movies', $schema['paths']);
        self::assertArrayHasKey('/api/v1/users/{username}/movies/{movaryId}/review', $schema['paths']);
    }
}
