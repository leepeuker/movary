<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class AdminUserTest extends HttpTestCase
{
    private const string PATH = '/api/v1/admin/users';

    public function testAdminCanCreateListReadUpdateAndDeleteUser() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $headers = $this->createAuthorizationHeader($admin['token']);
        $body = $this->createBody();

        $created = $this->http->post(self::PATH, $body, $headers)
            ->assertStatus(201)
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->json()['user'];
        $id = $created['id'];
        self::assertIsInt($id);
        self::assertSame([
            'id' => $id,
            'name' => $body['name'],
            'email' => $body['email'],
            'pendingEmail' => null,
            'isAdmin' => false,
            'isEnabled' => true,
            'emailVerified' => true,
        ], $created);
        $path = self::PATH . '/' . $id;

        $this->http->get($path, $headers)->assertStatus(200)->assertJsonSame(['user' => $created]);
        $list = $this->http->get(self::PATH . '?role=user&limit=1&page=1', $headers)
            ->assertStatus(200)
            ->assertHeaderSame('Cache-Control', 'no-store')
            ->json();
        self::assertSame(1, $list['limit']);
        self::assertSame(1, $list['page']);
        self::assertGreaterThanOrEqual(1, $list['total']);
        self::assertCount(1, $list['users']);
        self::assertFalse($list['users'][0]['isAdmin']);

        $updatedName = $body['name'] . 'New';
        $updatedEmail = 'new-' . $body['email'];
        $newPassword = 'new-admin-api-password';
        $this->http->request('PATCH', $path, $headers, [
            'name' => $updatedName,
            'email' => $updatedEmail,
            'password' => $newPassword,
            'isAdmin' => true,
            'isEnabled' => false,
        ])->assertStatus(204)->assertBodySame('');
        $this->http->get($path, $headers)->assertJsonSame(['user' => [
            'id' => $id,
            'name' => $updatedName,
            'email' => $updatedEmail,
            'pendingEmail' => null,
            'isAdmin' => true,
            'isEnabled' => false,
            'emailVerified' => false,
        ]]);
        self::assertTrue($this->fixture->isValidUserPassword($id, $newPassword));
        self::assertFalse($this->fixture->isValidUserPassword($id, $body['password']));

        $adminList = $this->http->get(self::PATH . '?role=admin', $headers)->json()['users'];
        self::assertContains($id, array_column($adminList, 'id'));
        $userList = $this->http->get(self::PATH . '?role=user', $headers)->json()['users'];
        self::assertNotContains($id, array_column($userList, 'id'));

        $this->http->request('DELETE', $path, $headers)->assertStatus(204)->assertBodySame('');
        $this->http->get($path, $headers)->assertStatus(404);
    }

    public function testAdminCanOverrideBothCreationDefaults() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $body = $this->createBody();
        $body['isEnabled'] = false;
        $body['emailVerified'] = false;

        $created = $this->http->post(self::PATH, $body, $this->createAuthorizationHeader($admin['token']))
            ->assertStatus(201)
            ->json()['user'];

        self::assertFalse($created['isEnabled']);
        self::assertFalse($created['emailVerified']);
        $headers = $this->createAuthorizationHeader($admin['token']);
        $this->http->get(self::PATH . '/' . $created['id'], $headers)
            ->assertJsonSame(['user' => $created]);
        self::assertContains($created['id'], array_column(
            $this->http->get(self::PATH . '?role=user', $headers)->json()['users'],
            'id',
        ));
    }

    public function testRoleChangesTakeEffectForAnExistingToken() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $regular = $this->fixture->createUserWithToken();
        $adminHeaders = $this->createAuthorizationHeader($admin['token']);
        $regularHeaders = $this->createAuthorizationHeader($regular['token']);
        $path = self::PATH . '/' . $regular['user']->getId();

        $this->http->get(self::PATH, $regularHeaders)->assertStatus(403);
        $this->http->request('PATCH', $path, $adminHeaders, ['isAdmin' => true])->assertStatus(204);
        $this->http->get(self::PATH, $regularHeaders)->assertStatus(200);
        $this->http->request('PATCH', $path, $adminHeaders, ['isAdmin' => false])->assertStatus(204);
        $this->http->get(self::PATH, $regularHeaders)->assertStatus(403);
    }

    public function testAllManagementRoutesRequireAnAdminToken() : void
    {
        $regular = $this->fixture->createUserWithToken();
        $headers = $this->createAuthorizationHeader($regular['token']);
        $body = $this->createBody();
        $requests = [
            ['GET', self::PATH, null],
            ['POST', self::PATH, $body],
            ['GET', self::PATH . '/' . $regular['user']->getId(), null],
            ['PATCH', self::PATH . '/' . $regular['user']->getId(), ['isAdmin' => true]],
            ['DELETE', self::PATH . '/' . $regular['user']->getId(), null],
        ];

        foreach ($requests as [$method, $path, $requestBody]) {
            $this->http->request($method, $path, body: $requestBody)->assertStatus(401);
            $this->http->request($method, $path, $headers, $requestBody)->assertStatus(403);
        }
        $this->http->get('/api/v1/me', $headers)->assertStatus(200)
            ->assertJsonSame(['user' => [
                'id' => $regular['user']->getId(),
                'name' => $regular['user']->getName(),
                'isAdmin' => false,
            ]]);
    }

    public function testInvalidCreateAndPatchLeaveExistingAccountUntouched() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $headers = $this->createAuthorizationHeader($admin['token']);
        $body = $this->createBody();
        $created = $this->http->post(self::PATH, $body, $headers)->assertStatus(201)->json()['user'];
        $path = self::PATH . '/' . $created['id'];

        $duplicate = $this->createBody();
        $duplicate['email'] = $body['email'];
        $this->http->post(self::PATH, $duplicate, $headers)->assertStatus(400);
        $this->http->post(self::PATH, $body, $headers)->assertStatus(400);
        $this->http->request('PATCH', $path, $headers, ['name' => 'changed', 'isEnabled' => 'false'])
            ->assertStatus(400);
        $this->http->request('PATCH', $path, $headers, ['isAdmin' => true, 'email' => 'invalid'])
            ->assertStatus(400);
        $this->http->request('PATCH', $path, $headers, ['emailVerified' => false])->assertStatus(400);
        $this->http->get($path, $headers)->assertJsonSame(['user' => $created]);
    }

    public function testFailedMultiFieldUpdateDoesNotChangeAccount() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $headers = $this->createAuthorizationHeader($admin['token']);
        $created = $this->http->post(self::PATH, $this->createBody(), $headers)->assertStatus(201)->json()['user'];
        $path = self::PATH . '/' . $created['id'];

        $this->http->request('PATCH', $path, $headers, ['isAdmin' => true, 'password' => 'short'])
            ->assertStatus(400);

        $this->http->get($path, $headers)->assertJsonSame(['user' => $created]);
    }

    public function testCannotDisableOwnAdminAccountAndMissingUsersReturnNotFound() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $headers = $this->createAuthorizationHeader($admin['token']);
        $ownPath = self::PATH . '/' . $admin['user']->getId();

        $this->http->request('PATCH', $ownPath, $headers, ['isEnabled' => false])->assertStatus(400);
        $this->http->get('/api/v1/me', $headers)->assertStatus(200);

        $missing = self::PATH . '/999999999';
        $this->http->get($missing, $headers)->assertStatus(404);
        $this->http->request('PATCH', $missing, $headers, ['name' => 'missing'])->assertStatus(404);
        $this->http->request('DELETE', $missing, $headers)->assertStatus(404);
    }

    #[DataProvider('invalidCreationProvider')]
    public function testRejectsInvalidCreationFields(array $changes) : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $body = array_replace($this->createBody(), $changes);

        $this->http->post(self::PATH, $body, $this->createAuthorizationHeader($admin['token']))
            ->assertStatus(400);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidCreationProvider() : iterable
    {
        yield 'enabled is not boolean' => [['isEnabled' => 'false']];
        yield 'verified is not boolean' => [['emailVerified' => 0]];
        yield 'unknown field' => [['unknown' => true]];
        yield 'invalid username' => [['name' => 'invalid-name']];
        yield 'invalid email' => [['email' => 'invalid']];
    }

    /** @return array{name: string, email: string, password: string, isAdmin: bool} */
    private function createBody() : array
    {
        $suffix = bin2hex(random_bytes(8));

        return [
            'name' => 'AdminApi' . $suffix,
            'email' => 'admin-api-' . $suffix . '@example.com',
            'password' => 'admin-api-password',
            'isAdmin' => false,
        ];
    }
}
