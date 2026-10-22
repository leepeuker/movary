<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Middleware\IsAdmin;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsAdmin::class)]
class IsAdminTest extends TestCase
{
    public function testAcceptsAdministrator() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(
            AuthenticatedUser::create(12, CredentialType::API_TOKEN),
        );
        $user = $this->createStub(UserEntity::class);
        $user->method('isAdmin')->willReturn(true);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserById')->willReturn($user);

        self::assertNull((new IsAdmin($authentication, $userApi))($request));
    }

    public function testRejectsNonAdministrator() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(
            AuthenticatedUser::create(12, CredentialType::API_TOKEN),
        );
        $user = $this->createStub(UserEntity::class);
        $user->method('isAdmin')->willReturn(false);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserById')->willReturn($user);

        $response = (new IsAdmin($authentication, $userApi))($request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    public function testRejectsMissingOrInvalidPersonalApiToken() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('authenticateApiToken')->with($request)->willReturn(null);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('findUserById');

        $response = (new IsAdmin($authentication, $userApi))($request);

        self::assertNotNull($response);
        self::assertSame(401, $response->getStatusCode()->getCode());
    }
}
