<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Movary\Domain\User\Service\PasswordPolicy;
use Movary\Domain\User\Service\Validator;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
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

    public function testCreateUserValidatesPassword() : void
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
            );

        (new UserApi($repository, $validator, $passwordPolicy))->createUser(
            'user@example.com',
            'a secure password',
            'user',
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

        (new UserApi($repository, $this->createStub(Validator::class), $passwordPolicy))
            ->updatePassword(7, 'a secure password');
    }

    public function testValidPasswordAcceptsGrandfatheredShortBcryptPassword() : void
    {
        $repository = $this->createStub(UserRepository::class);
        $user = $this->createStub(UserEntity::class);
        $user->method('getPasswordHash')->willReturn(password_hash('short', PASSWORD_BCRYPT));
        $repository->method('findUserById')->willReturn($user);

        self::assertTrue($this->createSubject($repository)->isValidPassword(7, 'short'));
    }

    private function createSubject(UserRepository $repository) : UserApi
    {
        return new UserApi(
            $repository,
            $this->createStub(Validator::class),
            $this->createStub(PasswordPolicy::class),
        );
    }
}
