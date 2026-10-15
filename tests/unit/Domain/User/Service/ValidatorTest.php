<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Service\Validator;
use Movary\Domain\User\UserEntity;
use Movary\Domain\User\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    private UserRepository&MockObject $repository;

    private Validator $subject;

    protected function setUp() : void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->subject = new Validator($this->repository);
    }

    public function testAcceptsEmailThatIsNeitherActiveNorPending() : void
    {
        $this->repository->expects(self::once())->method('findUserByEmail')->with('new@example.com')->willReturn(null);
        $this->repository
            ->expects(self::once())
            ->method('findUserByPendingEmail')
            ->with('new@example.com')
            ->willReturn(null);

        $this->subject->ensureEmailIsUnique('new@example.com');
    }

    public function testRejectsAnotherUsersActiveEmail() : void
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(7);
        $this->repository
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with('used@example.com')
            ->willReturn($user);
        $this->repository->expects(self::never())->method('findUserByPendingEmail');

        $this->expectException(EmailNotUnique::class);

        $this->subject->ensureEmailIsUnique('used@example.com', 12);
    }

    public function testRejectsAnotherUsersPendingEmail() : void
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(7);
        $this->repository
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with('pending@example.com')
            ->willReturn(null);
        $this->repository
            ->expects(self::once())
            ->method('findUserByPendingEmail')
            ->with('pending@example.com')
            ->willReturn($user);

        $this->expectException(EmailNotUnique::class);

        $this->subject->ensureEmailIsUnique('pending@example.com', 12);
    }

    public function testAcceptsCurrentUsersActiveOrPendingEmail() : void
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $this->repository
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with('alice@example.com')
            ->willReturn($user);
        $this->repository
            ->expects(self::once())
            ->method('findUserByPendingEmail')
            ->with('alice@example.com')
            ->willReturn($user);

        $this->subject->ensureEmailIsUnique('alice@example.com', 12);
    }
}
