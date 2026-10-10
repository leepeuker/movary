<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Middleware\IsAuthorizedToWriteUserData;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\HttpController\Api\Middleware\IsAuthorizedToWriteUserData::class)]
class IsAuthorizedToWriteUserDataTest extends TestCase
{
    public function testAuthorizesRequestedUserWithExplicitHeaderToken() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'example']);

        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(12);

        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('findUserByName')->with('example')->willReturn($user);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('authenticateApiToken')
            ->with($request)
            ->willReturn(AuthenticatedUser::create(12, CredentialType::API_TOKEN));

        self::assertNull((new IsAuthorizedToWriteUserData($userApi, $authentication))($request));
    }

    public function testRejectsCookieOnlyAuthentication() : void
    {
        $request = $this->createStub(Request::class);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('findUserByName');

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('authenticateApiToken')
            ->with($request)
            ->willReturn(null);
        $response = (new IsAuthorizedToWriteUserData($userApi, $authentication))($request);

        self::assertNotNull($response);
        self::assertSame(401, $response->getStatusCode()->getCode());
        self::assertContains('WWW-Authenticate: Bearer', array_map('strval', $response->getHeaders()));
    }

    public function testReturnsForbiddenWhenAuthenticatedAsDifferentUser() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'example']);
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(13);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserByName')->willReturn($user);
        $authentication = $this->createStub(Authentication::class);
        $authentication
            ->method('authenticateApiToken')
            ->willReturn(AuthenticatedUser::create(12, CredentialType::API_TOKEN));

        $response = (new IsAuthorizedToWriteUserData($userApi, $authentication))($request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }
}
