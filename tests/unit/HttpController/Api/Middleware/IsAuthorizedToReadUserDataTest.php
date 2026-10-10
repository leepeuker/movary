<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Middleware;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Middleware\IsAuthorizedToReadUserData;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsAuthorizedToReadUserData::class)]
class IsAuthorizedToReadUserDataTest extends TestCase
{
    public function testChecksVisibilityUsingOnlyPersonalApiTokenIdentity() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'example']);
        $requestedUser = $this->createStub(UserEntity::class);
        $authenticatedUser = AuthenticatedUser::create(12, CredentialType::API_TOKEN);

        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('findUserByName')->with('example')->willReturn($requestedUser);

        $authentication = $this->createMock(Authentication::class);
        $authentication
            ->expects(self::once())
            ->method('authenticateApiToken')
            ->with($request)
            ->willReturn($authenticatedUser);
        $authentication
            ->expects(self::once())
            ->method('isUserPageVisible')
            ->with($requestedUser, $authenticatedUser)
            ->willReturn(true);
        $authentication->expects(self::never())->method('isUserPageVisibleForApiRequest');

        self::assertNull((new IsAuthorizedToReadUserData($userApi, $authentication))($request));
    }

    public function testCookieOnlyRequestHasNoApiIdentity() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'example']);
        $requestedUser = $this->createStub(UserEntity::class);

        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserByName')->willReturn($requestedUser);

        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('authenticateApiToken')->with($request)->willReturn(null);
        $authentication
            ->expects(self::once())
            ->method('isUserPageVisible')
            ->with($requestedUser, null)
            ->willReturn(false);

        $response = (new IsAuthorizedToReadUserData($userApi, $authentication))($request);

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode()->getCode());
    }
}
