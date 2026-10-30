<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Http\Movary\HttpResponse;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class AuthenticationTest extends HttpTestCase
{
    private const string ENDPOINT = '/api/v1/me';

    public function testOldEndpointIsNotFound() : void
    {
        $this->http->get('/api/v1/authentication/token')->assertStatus(404);
    }

    public function testRejectsMissingToken() : void
    {
        $this->assertUnauthorizedResponse($this->http->get(self::ENDPOINT));
    }

    public function testRejectsInvalidBearerToken() : void
    {
        $this->assertUnauthorizedResponse(
            $this->http->get(self::ENDPOINT, ['Authorization' => 'Bearer invalid-token']),
        );
    }

    public function testAuthenticatesBearerToken() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->get(self::ENDPOINT, ['Authorization' => 'Bearer ' . $fixture['token']])
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertHeaderSame('Cache-Control', 'private, no-cache')
            ->assertJsonSame([
                'user' => [
                    'id' => $fixture['user']->getId(),
                    'name' => $fixture['user']->getName(),
                    'isAdmin' => false,
                ],
            ]);
    }

    public function testAuthenticatesCustomHeaderToken() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http
            ->get(self::ENDPOINT, ['X-Movary-Token' => $fixture['token']])
            ->assertStatus(200)
            ->assertHeaderSame('Content-Type', 'application/json')
            ->assertJsonSame([
                'user' => [
                    'id' => $fixture['user']->getId(),
                    'name' => $fixture['user']->getName(),
                    'isAdmin' => false,
                ],
            ]);
    }

    private function assertUnauthorizedResponse(HttpResponse $response) : void
    {
        $response
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer')
            ->assertHeaderSame('Cache-Control', 'private, no-cache');
    }
}
