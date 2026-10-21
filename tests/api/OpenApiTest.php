<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class OpenApiTest extends ApiTestCase
{
    public function testReturnsRuntimeOpenApiDocumentWithoutAuthentication() : void
    {
        $schema = $this->api
            ->get('/api/openapi')
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->json();

        self::assertSame('3.0.2', $schema['openapi']);
        self::assertNotSame('', $schema['info']['version']);
        self::assertSame('/api', $schema['servers'][0]['url']);
        self::assertArrayHasKey('/users/{username}/history/movies', $schema['paths']);
    }
}
