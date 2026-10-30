<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\UserListController;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserListController::class)]
class UserListControllerTest extends TestCase
{
    public function testIncludesPrivateTokenOwnerAndInternallyVisibleUsers() : void
    {
        $request = $this->createStub(Request::class);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(AuthenticatedUser::create(7, CredentialType::API_TOKEN));
        $viewer = $this->createStub(UserEntity::class);
        $viewer->method('getName')->willReturn('alice');
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('findUserById')->with(7)->willReturn($viewer);
        $userApi->expects(self::once())->method('fetchAllInternVisibleUsernames')->willReturn([
            ['name' => 'bob'],
            ['name' => 'charlie'],
        ]);

        $response = (new UserListController($authentication, $userApi))->getUsers($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('{"users":["alice","bob","charlie"]}', $response->getBody());
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
    }

    public function testDoesNotDuplicateVisibleTokenOwner() : void
    {
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('authenticateApiToken')->willReturn(AuthenticatedUser::create(7, CredentialType::API_TOKEN));
        $viewer = $this->createStub(UserEntity::class);
        $viewer->method('getName')->willReturn('alice');
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserById')->willReturn($viewer);
        $userApi->method('fetchAllInternVisibleUsernames')->willReturn([['name' => 'alice'], ['name' => 'bob']]);

        $response = (new UserListController($authentication, $userApi))->getUsers($this->createStub(Request::class));

        self::assertSame('{"users":["alice","bob"]}', $response->getBody());
    }

    public function testRequiresPersonalApiToken() : void
    {
        $authentication = $this->createStub(Authentication::class);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('fetchAllInternVisibleUsernames');

        $response = (new UserListController($authentication, $userApi))->getUsers($this->createStub(Request::class));

        self::assertSame(401, $response->getStatusCode()->getCode());
    }
}
