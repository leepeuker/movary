<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class UserPreferencesTest extends HttpTestCase
{
    private const string PATH = '/api/v1/me/preferences';

    public function testPreferencesCanBeReadAndUpdatedWithSameShape() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $initial = $this->http->get(self::PATH, $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->json();

        self::assertSame($fixture['user']->getName(), $initial['username']);
        self::assertCount(9, $initial);

        $updated = [
            'username' => $fixture['user']->getName() . 'Updated',
            'privacyLevel' => 2,
            'dateFormatId' => 3,
            'movieTitleDisplay' => 'both',
            'country' => 'DE',
            'watchlistAutomaticRemovalEnabled' => true,
            'displayCharacterNames' => true,
            'displayTmdbRatings' => true,
            'displayImdbRatings' => true,
        ];

        $this->http->request('PATCH', self::PATH, $headers, $updated)->assertStatus(204);
        $this->http->get(self::PATH, $headers)->assertJsonSame($updated);
        $this->http->request('PATCH', self::PATH, $headers, $updated)->assertStatus(204);

        $this->http->request('PATCH', self::PATH, $headers, ['displayImdbRatings' => false])->assertStatus(204);
        $updated['displayImdbRatings'] = false;
        $this->http->get(self::PATH, $headers)->assertJsonSame($updated);
    }

    public function testInvalidFieldDoesNotApplyOtherFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $initial = $this->http->get(self::PATH, $headers)->json();

        $this->http->request('PATCH', self::PATH, $headers, [
            'dateFormatId' => 3,
            'displayImdbRatings' => 'true',
        ])->assertStatus(400);

        $this->http->get(self::PATH, $headers)->assertJsonSame($initial);
    }

    public function testDuplicateUsernameDoesNotApplyOtherFields() : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $other = $this->fixture->createUserWithToken();
        $headers = $this->createAuthorizationHeader($fixture['token']);
        $initial = $this->http->get(self::PATH, $headers)->json();

        $this->http->request('PATCH', self::PATH, $headers, [
            'dateFormatId' => 3,
            'username' => $other['user']->getName(),
        ])->assertStatus(400);

        $this->http->get(self::PATH, $headers)->assertJsonSame($initial);
    }

    #[DataProvider('invalidFieldProvider')]
    public function testRejectsInvalidFields(array $body) : void
    {
        $fixture = $this->fixture->createUserWithToken();
        $this->http->request('PATCH', self::PATH, $this->createAuthorizationHeader($fixture['token']), $body)
            ->assertStatus(400);
    }

    public function testRequiresAuthentication() : void
    {
        $this->http->get(self::PATH)->assertStatus(401);
        $this->http->request('PATCH', self::PATH, body: ['privacyLevel' => 2])->assertStatus(401);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidFieldProvider() : iterable
    {
        yield 'empty request' => [[]];
        yield 'unknown field' => [['unknown' => true]];
        yield 'invalid username format' => [['username' => 'bad-name']];
        yield 'invalid privacy' => [['privacyLevel' => 4]];
        yield 'invalid date format' => [['dateFormatId' => 4]];
        yield 'invalid title display' => [['movieTitleDisplay' => 'invalid']];
        yield 'invalid country' => [['country' => 'XX']];
        yield 'invalid boolean' => [['displayTmdbRatings' => 1]];
    }
}
