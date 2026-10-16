<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\Service\PasswordPolicy;
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

    private UserEntity&MockObject $currentUserStub;

    private UserController $subject;

    private EmailVerificationRequestService&MockObject $verificationRequestServiceMock;

    private UserApi&MockObject $userApiMock;

    private PasswordPolicy&MockObject $passwordPolicyMock;

    private Authentication&MockObject $authenticationMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->currentWebUserStub = $this->createStub(CurrentWebUser::class);
        $this->currentUserStub = $this->createMock(UserEntity::class);
        $this->currentUserStub->method('getId')->willReturn(42);
        $this->currentUserStub->method('getEmail')->willReturn('current@example.com');
        $this->currentUserStub->method('isAdmin')->willReturn(true);
        $this->currentUserStub->method('hasCoreAccountChangesDisabled')->willReturn(false);
        $this->currentWebUserStub->method('findUser')->willReturn($this->currentUserStub);
        $this->currentWebUserStub->method('requireUser')->willReturn($this->currentUserStub);
        $this->verificationRequestServiceMock = $this->createMock(EmailVerificationRequestService::class);
        $this->passwordPolicyMock = $this->createMock(PasswordPolicy::class);
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->subject = new UserController(
            $this->userApiMock,
            $this->currentWebUserStub,
            $this->verificationRequestServiceMock,
            $this->passwordPolicyMock,
            $this->authenticationMock,
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
            ->with('user@example.com', 'short', 'user', false, true, true)
            ->willThrowException(new PasswordTooShort(15));

        $response = $this->subject->createUser($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Password too short.', $response->getBody());
    }

    public function testUpdatesOwnEmailImmediatelyAfterPasswordConfirmation() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'current password',
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('isValidPassword')
            ->with(42, 'current password')
            ->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('updateEmail')
            ->with(42, 'new@example.com')
            ->willReturn(false);
        $this->verificationRequestServiceMock->expects(self::never())->method('requestForUser');

        $response = $this->subject->updateOwnEmail($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            ['verificationPending' => false, 'verificationRequested' => false],
            Json::decode((string)$response->getBody()),
        );
    }

    public function testOwnEmailUpdateSchedulesRequiredVerification() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'current password',
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->userApiMock->method('updateEmail')->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserById')
            ->with(42)
            ->willReturn($this->currentUserStub);
        $this->verificationRequestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($this->currentUserStub)
            ->willReturn(true);

        $response = $this->subject->updateOwnEmail($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            ['verificationPending' => true, 'verificationRequested' => true],
            Json::decode((string)$response->getBody()),
        );
    }

    public function testOwnEmailUpdateReportsExistingPendingChangeWithoutRescheduling() : void
    {
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('getId')->willReturn(42);
        $currentUser->method('getPendingEmail')->willReturn('pending@example.com');
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $currentWebUser->method('requireUser')->willReturn($currentUser);
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'pending@example.com',
            'currentPassword' => 'current password',
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->userApiMock->method('updateEmail')->willReturn(false);
        $this->verificationRequestServiceMock->expects(self::never())->method('requestForUser');
        $subject = new UserController(
            $this->userApiMock,
            $currentWebUser,
            $this->verificationRequestServiceMock,
            $this->passwordPolicyMock,
            $this->authenticationMock,
        );

        $response = $subject->updateOwnEmail($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            ['verificationPending' => true, 'verificationRequested' => false],
            Json::decode((string)$response->getBody()),
        );
    }

    public function testOwnEmailUpdateRejectsWrongCurrentPassword() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'wrong password',
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(false);
        $this->userApiMock->expects(self::never())->method('updateEmail');

        $response = $this->subject->updateOwnEmail($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Current password wrong.', $response->getBody());
    }

    public function testOwnEmailUpdateRejectsInvalidAddress() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'invalid',
            'currentPassword' => 'current password',
        ]));
        $this->userApiMock->expects(self::never())->method('isValidPassword');
        $this->userApiMock->expects(self::never())->method('updateEmail');

        $response = $this->subject->updateOwnEmail($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Email address is not valid.', $response->getBody());
    }

    public function testOwnEmailUpdateRejectsAddressAlreadyInUse() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'used@example.com',
            'currentPassword' => 'current password',
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->userApiMock->method('updateEmail')->willThrowException(new EmailNotUnique());

        $response = $this->subject->updateOwnEmail($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('Email already in use.', $response->getBody());
    }

    public function testOwnEmailUpdateIsForbiddenWhenCoreAccountChangesAreDisabled() : void
    {
        $currentUser = $this->createStub(UserEntity::class);
        $currentUser->method('hasCoreAccountChangesDisabled')->willReturn(true);
        $currentWebUser = $this->createStub(CurrentWebUser::class);
        $currentWebUser->method('requireUser')->willReturn($currentUser);
        $request = $this->createStub(Request::class);
        $this->userApiMock->expects(self::never())->method('updateEmail');
        $subject = new UserController(
            $this->userApiMock,
            $currentWebUser,
            $this->verificationRequestServiceMock,
            $this->passwordPolicyMock,
            $this->authenticationMock,
        );

        self::assertSame(403, $subject->updateOwnEmail($request)->getStatusCode()->getCode());
    }

    public function testUpdatesOwnEmailAndPasswordTogether() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'current password',
            'newPassword' => 'a secure password',
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('isValidPassword')
            ->with(42, 'current password')
            ->willReturn(true);
        $this->passwordPolicyMock
            ->expects(self::once())
            ->method('ensurePasswordIsValid')
            ->with('a secure password');
        $this->userApiMock
            ->expects(self::once())
            ->method('updateEmail')
            ->with(42, 'new@example.com')
            ->willReturn(false);
        $this->userApiMock
            ->expects(self::once())
            ->method('updatePassword')
            ->with(42, 'a secure password');
        $this->authenticationMock->expects(self::once())->method('logout');

        $response = $this->subject->updateOwnCredentials($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            [
                'emailChanged' => true,
                'verificationPending' => false,
                'verificationRequested' => false,
                'passwordChanged' => true,
            ],
            Json::decode((string)$response->getBody()),
        );
    }

    public function testUpdatesOwnEmailWithoutChangingPassword() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'current password',
            'newPassword' => null,
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->passwordPolicyMock->expects(self::never())->method('ensurePasswordIsValid');
        $this->userApiMock->expects(self::once())->method('updateEmail')->willReturn(false);
        $this->userApiMock->expects(self::never())->method('updatePassword');
        $this->authenticationMock->expects(self::never())->method('logout');

        $response = $this->subject->updateOwnCredentials($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertFalse(Json::decode((string)$response->getBody())['passwordChanged']);
    }

    public function testOwnCredentialsUpdateValidatesPasswordBeforeChangingEmail() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'email' => 'new@example.com',
            'currentPassword' => 'current password',
            'newPassword' => 'too short',
        ]));
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->passwordPolicyMock
            ->expects(self::once())
            ->method('ensurePasswordIsValid')
            ->willThrowException(new PasswordTooShort(15));
        $this->userApiMock->expects(self::never())->method('updateEmail');
        $this->userApiMock->expects(self::never())->method('updatePassword');

        $response = $this->subject->updateOwnCredentials($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('New password not meeting requirements', $response->getBody());
    }

    public function testResendsOwnPendingEmailVerification() : void
    {
        $this->currentUserStub->method('getPendingEmail')->willReturn('new@example.com');
        $this->verificationRequestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($this->currentUserStub)
            ->willReturn(true);

        self::assertSame(200, $this->subject->resendOwnPendingEmailVerification()->getStatusCode()->getCode());
    }

    public function testDoesNotResendOwnEmailVerificationWithoutPendingChange() : void
    {
        $this->currentUserStub->method('getPendingEmail')->willReturn(null);
        $this->verificationRequestServiceMock->expects(self::never())->method('requestForUser');

        $response = $this->subject->resendOwnPendingEmailVerification();

        self::assertSame(400, $response->getStatusCode()->getCode());
    }

    public function testCancelsOwnPendingEmailChange() : void
    {
        $this->userApiMock->expects(self::once())->method('cancelPendingEmailChange')->with(42);

        self::assertSame(200, $this->subject->cancelOwnPendingEmailChange()->getStatusCode()->getCode());
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
            $this->passwordPolicyMock,
            $this->authenticationMock,
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
            $this->passwordPolicyMock,
            $this->authenticationMock,
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

    public function testApprovePendingEmailChangePromotesAddress() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['userId' => '7']);
        $this->userApiMock->expects(self::once())->method('approvePendingEmailChange')->with(7)->willReturn(true);

        self::assertSame(200, $this->subject->approvePendingEmailChange($request)->getStatusCode()->getCode());
    }
}
