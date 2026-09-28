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

    public function testFindPasswordResetTokenCreationDate() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT `created_at` FROM `user_password_reset_token` WHERE `user_id` = ?',
                [12],
            )
            ->willReturn('2026-09-27 12:00:00');

        self::assertEquals(
            DateTime::createFromString('2026-09-27 12:00:00'),
            $this->subject->findPasswordResetTokenCreationDate(12),
        );
    }

    public function testFindPasswordResetTokenCreationDateReturnsNull() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->willReturn(false);

        self::assertNull($this->subject->findPasswordResetTokenCreationDate(12));
    }

    public function testFindPasswordResetTokenData() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                'SELECT `user_id`, `expiration_date`, `created_at` FROM `user_password_reset_token` WHERE `token_hash` = ?',
                ['token-hash'],
            )
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-27 12:15:00',
                'created_at' => '2026-09-27 12:00:00',
            ]);

        self::assertEquals(
            [
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('2026-09-27 12:15:00'),
                'createdAt' => DateTime::createFromString('2026-09-27 12:00:00'),
            ],
            $this->subject->findPasswordResetTokenData('token-hash'),
        );
    }

    public function testFindPasswordResetTokenDataReturnsNull() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        self::assertNull($this->subject->findPasswordResetTokenData('unknown-token-hash'));
    }

    public function testReplacePasswordResetToken() : void
    {
        $expirationDate = DateTime::createFromString('2026-09-27 12:15:00');
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(function (callable $callback) : void {
                $callback($this->dbConnectionMock);
            });
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('delete')
            ->with('user_password_reset_token', ['user_id' => 12]);
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('insert')
            ->with(
                'user_password_reset_token',
                self::callback(
                    static fn(array $data) => $data['user_id'] === 12
                        && $data['token_hash'] === 'token-hash'
                        && $data['expiration_date'] === (string)$expirationDate
                        && is_string($data['created_at']),
                ),
            );

        $this->subject->replacePasswordResetToken(12, 'token-hash', $expirationDate);
    }
}
