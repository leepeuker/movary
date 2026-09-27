<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Doctrine\DBAL\Connection;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Domain\User\UserRepository::class)]
class UserRepositoryTest extends TestCase
{
    private MockObject|Connection $dbConnectionMock;

    private UserRepository $subject;

    protected function setUp() : void
    {
        $this->dbConnectionMock = $this->createMock(Connection::class);
        $this->subject = new UserRepository($this->dbConnectionMock);
    }

    public function testFindAuthTokenData() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                'SELECT `user_id`, `expiration_date` FROM `user_auth_token` WHERE `token` = ?',
                ['authentication-token'],
            )
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-27 12:00:00',
            ]);

        self::assertEquals(
            [
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('2026-09-27 12:00:00'),
            ],
            $this->subject->findAuthTokenData('authentication-token'),
        );
    }

    public function testFindAuthTokenDataReturnsNull() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->subject->findAuthTokenData('unknown-token'));
    }
}
