<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\UserController;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserController::class)]
class UserControllerTest extends TestCase
{
    private CurrentWebUser $currentWebUserStub;

    private UserController $subject;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->currentWebUserStub = $this->createStub(CurrentWebUser::class);
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('isAdmin')->willReturn(true);
        $this->currentWebUserStub->method('findUser')->willReturn($currentUser);
        $this->currentWebUserStub->method('requireUser')->willReturn($currentUser);
        $this->subject = new UserController($this->userApiMock, $this->currentWebUserStub);
    }

    public function testCreateUserTranslatesPasswordPolicyFailure() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'user@example.com',
            'password' => 'short',
            'name' => 'user',
            'isAdmin' => false,
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('createUser')
            ->willThrowException(new PasswordTooShort(15));

        $response = $this->subject->createUser($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Password too short.', $response->getBody());
    }

    public function testUpdateUserTranslatesPasswordPolicyFailure() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'user@example.com',
            'password' => 'short',
            'name' => 'user',
            'isAdmin' => false,
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('updatePassword')
            ->willThrowException(new PasswordTooShort(15));

        $response = $this->subject->updateUser($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Password too short.', $response->getBody());
    }
}
