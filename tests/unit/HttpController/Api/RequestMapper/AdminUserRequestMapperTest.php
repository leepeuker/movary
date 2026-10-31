<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\AdminUserRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminUserRequestMapper::class)]
class AdminUserRequestMapperTest extends TestCase
{
    public function testCreateDefaultsBothAccountStatesToTrue() : void
    {
        $result = $this->mapCreate([
            'name' => 'alice', 'email' => 'alice@example.com', 'password' => 'long-password', 'isAdmin' => false,
        ]);

        self::assertTrue($result['isEnabled']);
        self::assertTrue($result['emailVerified']);
    }

    public function testCreateAllowsBothAccountStatesToBeFalse() : void
    {
        $result = $this->mapCreate([
            'name' => 'alice', 'email' => 'alice@example.com', 'password' => 'long-password', 'isAdmin' => false,
            'isEnabled' => false, 'emailVerified' => false,
        ]);

        self::assertFalse($result['isEnabled']);
        self::assertFalse($result['emailVerified']);
    }

    public function testCreateRejectsNonBooleanAccountState() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField('emailVerified', 'must be a boolean'));
        $this->mapCreate([
            'name' => 'alice', 'email' => 'alice@example.com', 'password' => 'long-password', 'isAdmin' => false,
            'emailVerified' => 'false',
        ]);
    }

    public function testUpdateRejectsEmailVerified() : void
    {
        $request = $this->createStub(Request::class);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapJsonObject')->willReturn(['emailVerified' => false]);

        $this->expectExceptionObject(InvalidApiRequest::invalidField('emailVerified', 'is not writable'));
        (new AdminUserRequestMapper($requestMapper))->mapUpdate($request);
    }

    /** @param array<string, mixed> $data
     * @return array{email: string, password: string, name: string, isAdmin: bool, isEnabled: bool, emailVerified: bool}
     */
    private function mapCreate(array $data) : array
    {
        $request = $this->createStub(Request::class);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapJsonObject')->willReturn($data);

        return (new AdminUserRequestMapper($requestMapper))->mapCreate($request);
    }
}
