<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class LocationTest extends HttpTestCase
{
    public function testOwnerCanCreateListReadUpdateAndDeleteLocations() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($path, ['name' => 'Home', 'isCinema' => false], $headers)
            ->assertStatus(204)
            ->assertBodySame('');

        $response = $this->http->get($path, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Cache-Control', 'no-store');
        $locations = $response->json()['locations'];
        self::assertIsArray($locations);
        self::assertCount(1, $locations);
        self::assertSame('Home', $locations[0]['name']);
        self::assertFalse($locations[0]['isCinema']);
        $locationPath = $path . '/' . $locations[0]['id'];

        $this->http->get($locationPath, $headers)->assertStatus(200)->assertJsonSame($locations[0]);
        $this->http->put($locationPath, ['name' => 'Cinema', 'isCinema' => true], $headers)
            ->assertStatus(204);
        $this->http->get($locationPath, $headers)->assertJsonSame([
            'id' => $locations[0]['id'],
            'name' => 'Cinema',
            'isCinema' => true,
        ]);

        $this->http->request('DELETE', $locationPath, $headers)->assertStatus(204);
        $this->http->get($locationPath, $headers)->assertStatus(404);
        $this->http->request('DELETE', $locationPath, $headers)->assertStatus(204);
    }

    public function testOwnerCanToggleLocationsWithoutDeletingThem() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $this->http->post($path, ['name' => 'Home', 'isCinema' => false], $headers)->assertStatus(204);

        $this->http->put($path . '/settings', ['locationsEnabled' => false], $headers)->assertStatus(204);
        $this->http->get($path . '/settings', $headers)->assertStatus(200)
            ->assertJsonSame(['locationsEnabled' => false]);
        self::assertCount(1, $this->http->get($path, $headers)->json()['locations']);

        $this->http->put($path . '/settings', ['locationsEnabled' => true], $headers)->assertStatus(204);
        $this->http->get($path . '/settings', $headers)->assertJsonSame(['locationsEnabled' => true]);
    }

    #[DataProvider('readPathProvider')]
    public function testAnotherUserCannotReadLocationsEvenWhenMovieDataIsPublic(string $suffix) : void
    {
        $target = $this->fixture->createUserWithToken(2);
        $other = $this->fixture->createUserWithToken();
        $path = $this->createPath($target['user']->getName());
        $location = $this->fixture->createLocation($target['user']->getId());
        $suffix = str_replace('{id}', (string)$location->getId(), $suffix);

        $this->http->get($path . $suffix, $this->createAuthorizationHeader($other['token']))
            ->assertStatus(403);
    }

    public function testAnotherUserCannotModifyLocationsOrSettings() : void
    {
        $target = $this->fixture->createUserWithToken();
        $other = $this->fixture->createUserWithToken();
        $location = $this->fixture->createLocation($target['user']->getId());
        $path = $this->createPath($target['user']->getName());
        $headers = $this->createAuthorizationHeader($other['token']);

        $this->http->post($path, ['name' => 'Other', 'isCinema' => false], $headers)->assertStatus(403);
        $this->http->put($path . '/' . $location->getId(), ['name' => 'Other', 'isCinema' => true], $headers)
            ->assertStatus(403);
        $this->http->request('DELETE', $path . '/' . $location->getId(), $headers)->assertStatus(403);
        $this->http->put($path . '/settings', ['locationsEnabled' => false], $headers)->assertStatus(403);
    }

    public function testLocationIdsFromAnotherAccountAreNotAccessible() : void
    {
        $owner = $this->fixture->createUserWithToken();
        $other = $this->fixture->createUserWithToken();
        $location = $this->fixture->createLocation($other['user']->getId());
        $path = $this->createPath($owner['user']->getName()) . '/' . $location->getId();
        $headers = $this->createAuthorizationHeader($owner['token']);

        $this->http->get($path, $headers)->assertStatus(404);
        $this->http->put($path, ['name' => 'Changed', 'isCinema' => true], $headers)->assertStatus(404);
        $this->http->request('DELETE', $path, $headers)->assertStatus(204);
        self::assertSame($location->getName(), $this->http->get(
            $this->createPath($other['user']->getName()) . '/' . $location->getId(),
            $this->createAuthorizationHeader($other['token']),
        )->json()['name']);
    }

    #[DataProvider('invalidLocationProvider')]
    public function testRejectsInvalidLocationInput(array $body) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $path = $this->createPath($fixture['user']->getName());
        $headers = $this->createAuthorizationHeader($fixture['token']);

        $this->http->post($path, $body, $headers)->assertStatus(400);
        self::assertSame([], $this->http->get($path, $headers)->json()['locations']);
    }

    public function testRejectsInvalidLocationSetting() : void
    {
        $fixture = $this->fixture->createUserWithToken();

        $this->http->put(
            $this->createPath($fixture['user']->getName()) . '/settings',
            ['locationsEnabled' => 1],
            $this->createAuthorizationHeader($fixture['token']),
        )->assertStatus(400);
    }

    #[DataProvider('requestProvider')]
    public function testRequiresAuthentication(string $method, string $suffix) : void
    {
        $user = $this->fixture->createUserWithToken()['user'];

        $this->http->request($method, $this->createPath($user->getName()) . $suffix, body: [])
            ->assertStatus(401);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidLocationProvider() : iterable
    {
        yield 'missing name' => [['isCinema' => false]];
        yield 'blank name' => [['name' => ' ', 'isCinema' => false]];
        yield 'non-string name' => [['name' => 1, 'isCinema' => false]];
        yield 'missing cinema flag' => [['name' => 'Home']];
        yield 'non-boolean cinema flag' => [['name' => 'Home', 'isCinema' => 1]];
    }

    /** @return iterable<string, array{string}> */
    public static function readPathProvider() : iterable
    {
        yield 'collection' => [''];
        yield 'settings' => ['/settings'];
        yield 'item' => ['/{id}'];
    }

    /** @return iterable<string, array{string, string}> */
    public static function requestProvider() : iterable
    {
        yield 'list' => ['GET', ''];
        yield 'create' => ['POST', ''];
        yield 'read settings' => ['GET', '/settings'];
        yield 'update settings' => ['PUT', '/settings'];
        yield 'read item' => ['GET', '/1'];
        yield 'update item' => ['PUT', '/1'];
        yield 'delete item' => ['DELETE', '/1'];
    }

    private function createPath(string $username) : string
    {
        return '/api/v1/users/' . $username . '/locations';
    }
}
