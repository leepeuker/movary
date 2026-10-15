<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\UserController;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserController::class)]
#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends TestCase
{
    private CurrentWebUser $currentWebUserStub;

    private UserController $subject;

    private EmailVerificationRequestService&MockObject $verificationRequestServiceMock;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->currentWebUserStub = $this->createStub(CurrentWebUser::class);
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('isAdmin')->willReturn(true);
        $this->currentWebUserStub->method('findUser')->willReturn($currentUser);
        $this->currentWebUserStub->method('requireUser')->willReturn($currentUser);
        $this->verificationRequestServiceMock = $this->createMock(EmailVerificationRequestService::class);
        $this->subject = new UserController(
            $this->userApiMock,
            $this->currentWebUserStub,
            $this->verificationRequestServiceMock,
        );
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

    public function testAdminCanDisableAnotherUser() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'user@example.com',
            'password' => null,
            'name' => 'user',
            'isAdmin' => false,
            'isEnabled' => false,
        ]));
        $this->userApiMock->expects(self::once())->method('updateIsEnabled')->with(7, false);

        $response = $this->subject->updateUser($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }

    public function testAdminCannotDisableOwnAccount() : void
    {
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('getId')->willReturn(7);
        $currentUser->method('isAdmin')->willReturn(true);
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $currentWebUser->method('requireUser')->willReturn($currentUser);
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'admin@example.com',
            'password' => null,
            'name' => 'admin',
            'isAdmin' => true,
            'isEnabled' => false,
        ]));
        $this->userApiMock->expects(self::never())->method('updateIsEnabled');

        $response = (new UserController(
            $this->userApiMock,
            $currentWebUser,
            $this->verificationRequestServiceMock,
        ))->updateUser($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('You cannot disable your own account.', $response->getBody());
    }

    public function testNonAdminCannotChangeAccountStatus() : void
    {
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('getId')->willReturn(7);
        $currentUser->method('isAdmin')->willReturn(false);
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $currentWebUser->method('requireUser')->willReturn($currentUser);
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'user@example.com',
            'password' => null,
            'name' => 'user',
            'isAdmin' => false,
            'isEnabled' => true,
        ]));
        $this->userApiMock->expects(self::never())->method('updateIsEnabled');

        $response = (new UserController(
            $this->userApiMock,
            $currentWebUser,
            $this->verificationRequestServiceMock,
        ))->updateUser($request);

        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    public function testUpdateUserSchedulesPendingEmailVerification() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'password' => null,
            'name' => 'user',
            'isAdmin' => false,
        ]));
        $user = $this->createStub(UserEntity::class);
        $this->userApiMock->method('updateEmail')->willReturn(true);
        $this->userApiMock->method('findUserById')->willReturn($user);
        $this->verificationRequestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($user)
            ->willReturn(true);

        self::assertSame(200, $this->subject->updateUser($request)->getStatusCode()->getCode());
    }

    public function testUpdateUserReportsPendingEmailDeliveryFailure() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'typo@example.com',
            'password' => null,
            'name' => 'user',
            'isAdmin' => false,
        ]));
        $this->userApiMock->method('updateEmail')->willReturn(true);
        $this->userApiMock->method('findUserById')->willReturn($this->createStub(UserEntity::class));
        $this->verificationRequestServiceMock->method('requestForUser')->willReturn(false);

        $response = $this->subject->updateUser($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertStringContainsString('current email remains active', (string)$response->getBody());
    }

    public function testResendPendingEmailVerificationSchedulesJob() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $user = $this->createStub(UserEntity::class);
        $user->method('getPendingEmail')->willReturn('new@example.com');
        $this->userApiMock->method('findUserById')->willReturn($user);
        $this->verificationRequestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($user)
            ->willReturn(true);

        self::assertSame(
            200,
            $this->subject->resendPendingEmailVerification($request)->getStatusCode()->getCode(),
        );
    }

    public function testCancelPendingEmailChangeClearsPendingAddress() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $this->userApiMock->expects(self::once())->method('cancelPendingEmailChange')->with(7);

        self::assertSame(200, $this->subject->cancelPendingEmailChange($request)->getStatusCode()->getCode());
    }
}
