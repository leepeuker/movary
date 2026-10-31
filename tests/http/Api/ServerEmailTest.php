<?php declare(strict_types=1);

namespace Tests\Http\Movary\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Http\Movary\HttpTestCase;

#[CoversNothing]
class ServerEmailTest extends HttpTestCase
{
    private const string PATH = '/api/v1/admin/server/email';

    public function testAllRoutesRequireAnAdminToken() : void
    {
        $user = $this->fixture->createUserWithToken();
        foreach (['GET', 'PATCH', 'DELETE'] as $method) {
            $this->http->request($method, self::PATH)->assertStatus(401);
            $this->http->request($method, self::PATH, $this->createAuthorizationHeader($user['token']))
                ->assertStatus(403);
        }
    }

    public function testAdminCanConfigureReadPatchAndDeleteWithoutExposingCredentials() : void
    {
        $admin = $this->fixture->createUserWithToken(isAdmin: true);
        $headers = $this->createAuthorizationHeader($admin['token']);
        try {
            $this->http->request('PATCH', self::PATH, $headers, [
                'emailEnabled' => true,
                'smtpHost' => 'smtp.example.com',
                'smtpPort' => 587,
                'smtpFromAddress' => 'sender@example.com',
                'smtpEncryption' => 'tls',
                'smtpWithAuthentication' => true,
                'smtpUser' => 'private-smtp-user',
                'smtpPassword' => 'private-smtp-password',
            ])->assertStatus(204)->assertBodySame('');
            $expected = [
                'emailEnabled' => true,
                'smtpHost' => 'smtp.example.com',
                'smtpPort' => 587,
                'smtpFromAddress' => 'sender@example.com',
                'smtpEncryption' => 'tls',
                'smtpWithAuthentication' => true,
                'smtpUserConfigured' => true,
                'smtpPasswordConfigured' => true,
                'environmentControlledFields' => [],
            ];
            $this->http->get(self::PATH, $headers)->assertStatus(200)
                ->assertHeaderSame('Cache-Control', 'no-store')->assertJsonSame($expected);

            $this->http->request('PATCH', self::PATH, $headers, ['smtpPort' => 465])->assertStatus(204);
            $expected['smtpPort'] = 465;
            $this->http->get(self::PATH, $headers)->assertJsonSame($expected);

            foreach ([['smtpHost' => 'changed', 'smtpPort' => 0], ['emailEnabled' => 'false'],
                ['smtpPasswordConfigured' => false], []] as $invalid) {
                $this->http->request('PATCH', self::PATH, $headers, $invalid)->assertStatus(400);
                $this->http->get(self::PATH, $headers)->assertJsonSame($expected);
            }

            $this->http->request('DELETE', self::PATH, $headers)->assertStatus(204)->assertBodySame('');
            $this->http->get(self::PATH, $headers)->assertJsonSame([
                'emailEnabled' => false,
                'smtpHost' => null,
                'smtpPort' => null,
                'smtpFromAddress' => null,
                'smtpEncryption' => null,
                'smtpWithAuthentication' => false,
                'smtpUserConfigured' => false,
                'smtpPasswordConfigured' => false,
                'environmentControlledFields' => [],
            ]);
            $this->http->request('DELETE', self::PATH, $headers)->assertStatus(204);
        } finally {
            $this->http->request('DELETE', self::PATH, $headers)->assertStatus(204);
        }
    }
}
