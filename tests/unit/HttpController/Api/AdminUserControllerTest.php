<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Doctrine\DBAL\Connection;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\AdminUserController;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\AdminUserRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminUserController::class)]
class AdminUserControllerTest extends TestCase
{
    public function testCreateUsesMappedAccountStatesAndReturnsCreatedUser() : void
    {
        $request = $this->createStub(Request::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $user->method('getName')->willReturn('alice');
        $user->method('getEmail')->willReturn('alice@example.com');
        $user->method('isEnabled')->willReturn(false);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('createUser')->with(
            'alice@example.com', 'long-password', 'alice', false, false, false,
        );
        $userApi->method('fetchUserByName')->willReturn($user);
        $mapper = $this->createStub(AdminUserRequestMapper::class);
        $mapper->method('mapCreate')->willReturn([
            'email' => 'alice@example.com', 'password' => 'long-password', 'name' => 'alice',
            'isAdmin' => false, 'isEnabled' => false, 'emailVerified' => false,
        ]);

        $response = $this->controller($userApi, $mapper)->createUser($request);

        self::assertSame(201, $response->getStatusCode()->getCode());
        self::assertSame(12, json_decode((string)$response->getBody(), true)['user']['id']);
    }

    public function testGetUserReturnsNotFoundWhenAccountDoesNotExist() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '12']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('findUserById')->willReturn(null);

        self::assertSame(404, $this->controller($userApi)->getUser($request)->getStatusCode()->getCode());
    }

    public function testUpdateRejectsDisablingOwnAccount() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '12']);
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('findUserById')->willReturn($this->createStub(UserEntity::class));
        $userApi->expects(self::never())->method('updateIsEnabled');
        $mapper = $this->createStub(AdminUserRequestMapper::class);
        $mapper->method('mapUpdate')->willReturn(['isEnabled' => false]);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('requireApiToken')->willReturn(AuthenticatedUser::create(12, CredentialType::API_TOKEN));

        $this->expectExceptionObject(InvalidApiRequest::invalidField('isEnabled', 'cannot disable your own account'));
        $this->controller($userApi, $mapper, $authentication)->updateUser($request);
    }

    private function controller(
        UserApi $userApi,
        ?AdminUserRequestMapper $mapper = null,
        ?Authentication $authentication = null,
    ) : AdminUserController {
        return new AdminUserController(
            $userApi,
            $authentication ?? $this->createStub(Authentication::class),
            $this->createStub(EmailVerificationRequestService::class),
            $mapper ?? $this->createStub(AdminUserRequestMapper::class),
            $this->createStub(RequestMapper::class),
            $this->createStub(Connection::class),
        );
    }
}
