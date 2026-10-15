<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Movary\Domain\User\Service\EmailVerificationPolicy;
use Movary\Domain\User\Service\PasswordPolicy;
use Movary\Domain\User\Service\Validator;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserApi::class)]
class UserApiTest extends TestCase
{
    public function testCountUsersReturnsRepositoryResult() : void
    {
        /** @var UserRepository&MockObject $repository */
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('countUsers')->with(null)->willReturn(72);

        self::assertSame(72, $this->createSubject($repository)->countUsers());
    }

    public function testFetchAllPaginatedForwardsPagination() : void
    {
        /** @var UserRepository&MockObject $repository */
        $repository = $this->createMock(UserRepository::class);
        $users = [['id' => '7', 'name' => 'Alice', 'email' => 'alice@example.com', 'isAdmin' => '1']];
        $repository
            ->expects(self::once())
            ->method('fetchAllPaginated')
            ->with(20, 40, null)
            ->willReturn($users);

        self::assertSame($users, $this->createSubject($repository)->fetchAllPaginated(20, 40));
    }

    public function testForwardsRoleFilter() : void
    {
        /** @var UserRepository&MockObject $repository */
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('countUsers')->with(true)->willReturn(3);
        $repository->expects(self::once())->method('fetchAllPaginated')->with(20, 0, true)->willReturn([]);
        $subject = $this->createSubject($repository);

        self::assertSame(3, $subject->countUsers(true));
        self::assertSame([], $subject->fetchAllPaginated(20, 0, true));
    }

    public function testCreateVerifiedUserValidatesPassword() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $validator = $this->createStub(Validator::class);
        $passwordPolicy = $this->createMock(PasswordPolicy::class);
        $passwordPolicy->expects(self::once())->method('ensurePasswordIsValid')->with('a secure password');
        $repository
            ->expects(self::once())
            ->method('createUser')
            ->with(
                'user@example.com',
                self::callback(static fn(string $hash) => password_verify('a secure password', $hash)),
                'user',
                false,
                self::isInstanceOf(\Movary\ValueObject\DateTime::class),
            );

        (new UserApi($repository, $validator, $passwordPolicy, $this->createStub(EmailVerificationPolicy::class)))->createUser(
            'user@example.com',
            'a secure password',
            'user',
            false,
            true,
        );
    }

    public function testCreateUnverifiedUserDoesNotSetVerificationDate() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects(self::once())
            ->method('createUser')
            ->with(
                'user@example.com',
                self::callback(static fn(string $hash) => password_verify('a secure password', $hash)),
                'user',
                false,
                null,
            );

        $this->createSubject($repository)->createUser(
            'user@example.com',
            'a secure password',
            'user',
            false,
            false,
        );
    }

    public function testUpdatePasswordValidatesPasswordAndRevokesAuthenticationTokens() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findUserById')->willReturn($this->createStub(UserEntity::class));
        $passwordPolicy = $this->createMock(PasswordPolicy::class);
        $passwordPolicy->expects(self::once())->method('ensurePasswordIsValid')->with('a secure password');
        $repository
            ->expects(self::once())
            ->method('updatePasswordAndRevokeAuthTokens')
            ->with(
                7,
                self::callback(static fn(string $hash) => password_verify('a secure password', $hash)),
            );

        (new UserApi(
            $repository,
            $this->createStub(Validator::class),
            $passwordPolicy,
            $this->createStub(EmailVerificationPolicy::class),
        ))
            ->updatePassword(7, 'a secure password');
    }

    public function testUpdateIsEnabledForwardsStatusChange() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects(self::once())
            ->method('updateIsEnabledAndRevokeAuthTokens')
            ->with(7, false);

        $this->createSubject($repository)->updateIsEnabled(7, false);
    }

    public function testValidPasswordAcceptsGrandfatheredShortBcryptPassword() : void
    {
        $repository = $this->createStub(UserRepository::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getPasswordHash')->willReturn(password_hash('short', PASSWORD_BCRYPT));
        $repository->method('findUserById')->willReturn($user);

        self::assertTrue($this->createSubject($repository)->isValidPassword(7, 'short'));
    }

    public function testUpdateEmailImmediatelyWhenVerificationIsOptional() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('updateEmail')->with(7, 'new@example.com');
        $repository->expects(self::never())->method('updatePendingEmail');

        self::assertFalse($this->createSubject($repository)->updateEmail(7, 'new@example.com'));
    }

    public function testUpdateEmailCreatesPendingChangeWhenVerificationIsRequired() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getEmail')->willReturn('old@example.com');
        $repository->method('findUserById')->willReturn($user);
        $repository->expects(self::once())->method('updatePendingEmail')->with(7, 'new@example.com');
        $repository->expects(self::never())->method('updateEmail');
        $policy = $this->createStub(EmailVerificationPolicy::class);
        $policy->method('isRequired')->willReturn(true);

        self::assertTrue($this->createSubject($repository, $policy)->updateEmail(7, 'new@example.com'));
    }

    public function testUpdateEmailBackToActiveAddressCancelsPendingChange() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getEmail')->willReturn('old@example.com');
        $user->method('getPendingEmail')->willReturn('typo@example.com');
        $repository->method('findUserById')->willReturn($user);
        $repository->expects(self::once())->method('clearPendingEmail')->with(7);
        $repository->expects(self::never())->method('updatePendingEmail');
        $policy = $this->createStub(EmailVerificationPolicy::class);
        $policy->method('isRequired')->willReturn(true);

        self::assertFalse($this->createSubject($repository, $policy)->updateEmail(7, 'old@example.com'));
    }

    public function testApprovePendingEmailChangeForwardsCurrentDate() : void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects(self::once())
            ->method('approvePendingEmail')
            ->with(7, self::isInstanceOf(DateTime::class))
            ->willReturn(true);

        self::assertTrue($this->createSubject($repository)->approvePendingEmailChange(7));
    }

    private function createSubject(
        UserRepository $repository,
        ?EmailVerificationPolicy $emailVerificationPolicy = null,
    ) : UserApi
    {
        return new UserApi(
            $repository,
            $this->createStub(Validator::class),
            $this->createStub(PasswordPolicy::class),
            $emailVerificationPolicy ?? $this->createStub(EmailVerificationPolicy::class),
        );
    }
}
