<?php declare(strict_types=1);

namespace Tests\Api\Movary;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Api\Movary\Support\ApiResponse;
use Tests\Api\Movary\Support\ApiTestCase;

#[CoversNothing]
class AuthenticationTest extends ApiTestCase
{
    private const string ENDPOINT = '/api/v1/authentication/token';

    public function testRejectsMissingToken() : void
    {
        $this->assertUnauthorizedResponse($this->api->get(self::ENDPOINT));
    }

    public function testRejectsInvalidBearerToken() : void
    {
        $this->assertUnauthorizedResponse(
            $this->api->get(self::ENDPOINT, ['Authorization' => 'Bearer invalid-token']),
        );
    }

    public function testAuthenticatesBearerToken() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->api
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

        $this->api
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

    private function assertUnauthorizedResponse(ApiResponse $response) : void
    {
        $response
            ->assertStatus(401)
            ->assertHeaderSame('WWW-Authenticate', 'Bearer')
            ->assertHeaderSame('Cache-Control', 'private, no-cache');
    }
}
