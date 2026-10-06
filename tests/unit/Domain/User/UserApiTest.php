<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Movary\Domain\User\Service\Validator;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserFilter;
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
        $filter = UserFilter::create(true);
        $repository->expects(self::once())->method('countUsers')->with($filter)->willReturn(3);
        $repository->expects(self::once())->method('fetchAllPaginated')->with(20, 0, $filter)->willReturn([]);
        $subject = $this->createSubject($repository);

        self::assertSame(3, $subject->countUsers($filter));
        self::assertSame([], $subject->fetchAllPaginated(20, 0, $filter));
    }

    private function createSubject(UserRepository $repository) : UserApi
    {
        return new UserApi($repository, $this->createStub(Validator::class));
    }
}
