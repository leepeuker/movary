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

    public function testCountUsersReturnsIntegerCount() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM `user`', [])
            ->willReturn('72');

        self::assertSame(72, $this->subject->countUsers());
    }

    public function testFetchAllPaginatedAppliesLimitAndOffset() : void
    {
        $users = [['id' => '7', 'name' => 'Alice', 'email' => 'alice@example.com', 'isAdmin' => '1']];
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'ORDER BY id')
                        && str_contains($query, 'LIMIT 20 OFFSET 40'),
                ),
                [],
            )
            ->willReturn($users);

        self::assertSame($users, $this->subject->fetchAllPaginated(20, 40));
    }

    public function testCountUsersAppliesRoleFilter() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM `user` WHERE is_admin = ?', [1])
            ->willReturn('3');

        self::assertSame(3, $this->subject->countUsers(true));
    }

    public function testFetchAllPaginatedAppliesRoleFilter() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'WHERE is_admin = ?')
                        && str_contains($query, 'LIMIT 20 OFFSET 40'),
                ),
                [0],
            )
            ->willReturn([]);

        self::assertSame([], $this->subject->fetchAllPaginated(20, 40, false));
    }

    public function testCreateLoginAttempt() : void
    {
        $createdAt = DateTime::createFromString('2026-10-03 12:00:00');
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('insert')
            ->with(
                'user_login_attempt',
                [
                    'subject_hash' => 'hash',
                    'created_at' => '2026-10-03 12:00:00',
                ],
            );
        $this->dbConnectionMock->expects(self::once())->method('lastInsertId')->willReturn('12');

        self::assertSame(12, $this->subject->createLoginAttempt('hash', $createdAt));
    }

    public function testFindLoginAttemptAboveLimitDate() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT `created_at` FROM `user_login_attempt` '
                . 'WHERE `subject_hash` = ? AND `created_at` >= ? '
                . 'ORDER BY `created_at` DESC LIMIT 5, 1',
                ['hash', '2026-10-03 12:00:00'],
            )
            ->willReturn('2026-10-03 12:01:00');

        self::assertEquals(
            DateTime::createFromString('2026-10-03 12:01:00'),
            $this->subject->findLoginAttemptAboveLimitDate(
                'hash',
                5,
                DateTime::createFromString('2026-10-03 12:00:00'),
            ),
        );
    }

    public function testFindLoginAttempts() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with('SELECT `id`, `subject_hash`, `created_at` FROM `user_login_attempt` ORDER BY `created_at` DESC')
            ->willReturn([
                [
                    'id' => '12',
                    'subject_hash' => 'hash',
                    'created_at' => '2026-10-03 12:00:00',
                ],
            ]);

        self::assertEquals(
            [[
                'id' => 12,
                'subjectHash' => 'hash',
                'createdAt' => DateTime::createFromString('2026-10-03 12:00:00'),
            ]],
            $this->subject->findLoginAttempts(),
        );
    }

    public function testDeleteAllLoginAttempts() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('executeStatement')
            ->with('DELETE FROM `user_login_attempt`');

        $this->subject->deleteAllLoginAttempts();
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

    public function testUpdatePasswordAndRevokeAuthTokens() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(fn(callable $callback) : mixed => $callback($this->dbConnectionMock));
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('update')
            ->with('user', ['password' => 'password-hash'], ['id' => 12]);
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('delete')
            ->with('user_auth_token', ['user_id' => 12]);

        $this->subject->updatePasswordAndRevokeAuthTokens(12, 'password-hash');
    }

    public function testResetPasswordWithTokenConsumesTokenAndRevokesAccess() : void
    {
        $currentDate = DateTime::createFromString('2026-09-28 12:00:00');
        $deleteCalls = [];
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(fn(callable $callback) : mixed => $callback($this->dbConnectionMock));
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                'SELECT reset_token.`user_id`, reset_token.`expiration_date`, u.`core_account_changes_disabled` '
                . 'FROM `user_password_reset_token` reset_token '
                . 'JOIN `user` u ON u.`id` = reset_token.`user_id` '
                . 'WHERE reset_token.`token_hash` = ?',
                ['token-hash'],
            )
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-28 12:15:00',
                'core_account_changes_disabled' => '0',
            ]);
        $this->dbConnectionMock
            ->expects(self::exactly(4))
            ->method('delete')
            ->willReturnCallback(static function (string $table, array $criteria) use (&$deleteCalls) : int {
                $deleteCalls[] = [$table, $criteria];

                return 1;
            });
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('update')
            ->with('user', ['password' => 'password-hash'], ['id' => 12]);

        self::assertTrue($this->subject->resetPasswordWithToken('token-hash', 'password-hash', $currentDate));
        self::assertSame(
            [
                ['user_password_reset_token', ['token_hash' => 'token-hash']],
                ['user_password_reset_token', ['user_id' => 12]],
                ['user_auth_token', ['user_id' => 12]],
                ['user_api_token', ['user_id' => 12]],
            ],
            $deleteCalls,
        );
    }

    public function testResetPasswordWithTokenRejectsAlreadyConsumedToken() : void
    {
        $this->dbConnectionMock
            ->method('transactional')
            ->willReturnCallback(fn(callable $callback) : mixed => $callback($this->dbConnectionMock));
        $this->dbConnectionMock
            ->method('fetchAssociative')
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-28 12:15:00',
                'core_account_changes_disabled' => '0',
            ]);
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('delete')
            ->with('user_password_reset_token', ['token_hash' => 'token-hash'])
            ->willReturn(0);
        $this->dbConnectionMock->expects(self::never())->method('update');

        self::assertFalse($this->subject->resetPasswordWithToken(
            'token-hash',
            'password-hash',
            DateTime::createFromString('2026-09-28 12:00:00'),
        ));
    }

    public function testResetPasswordWithTokenRejectsTokenThatExpiredBeforeSubmission() : void
    {
        $this->dbConnectionMock
            ->method('transactional')
            ->willReturnCallback(fn(callable $callback) : mixed => $callback($this->dbConnectionMock));
        $this->dbConnectionMock
            ->method('fetchAssociative')
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-28 11:59:59',
                'core_account_changes_disabled' => '0',
            ]);
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('delete')
            ->with('user_password_reset_token', ['token_hash' => 'token-hash']);
        $this->dbConnectionMock->expects(self::never())->method('update');

        self::assertFalse($this->subject->resetPasswordWithToken(
            'token-hash',
            'password-hash',
            DateTime::createFromString('2026-09-28 12:00:00'),
        ));
    }

    public function testResetPasswordWithTokenRejectsProtectedAccount() : void
    {
        $this->dbConnectionMock
            ->method('transactional')
            ->willReturnCallback(fn(callable $callback) : mixed => $callback($this->dbConnectionMock));
        $this->dbConnectionMock
            ->method('fetchAssociative')
            ->willReturn([
                'user_id' => '12',
                'expiration_date' => '2026-09-28 12:15:00',
                'core_account_changes_disabled' => '1',
            ]);
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('delete')
            ->with('user_password_reset_token', ['token_hash' => 'token-hash']);
        $this->dbConnectionMock->expects(self::never())->method('update');

        self::assertFalse($this->subject->resetPasswordWithToken(
            'token-hash',
            'password-hash',
            DateTime::createFromString('2026-09-28 12:00:00'),
        ));
    }

    public function testFetchPendingPasswordResetTokensReturnsSafeMetadata() : void
    {
        $currentDate = DateTime::createFromString('2026-09-28 12:00:00');
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                'SELECT reset_token.`user_id`, u.`name`, u.`email`, reset_token.`expiration_date`, reset_token.`created_at` '
                . 'FROM `user_password_reset_token` reset_token '
                . 'JOIN `user` u ON u.`id` = reset_token.`user_id` '
                . 'WHERE reset_token.`expiration_date` > ? '
                . 'ORDER BY reset_token.`created_at` DESC',
                ['2026-09-28 12:00:00'],
            )
            ->willReturn([[
                'user_id' => '12',
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'expiration_date' => '2026-09-28 12:15:00',
                'created_at' => '2026-09-28 12:00:00',
            ]]);

        self::assertEquals(
            [[
                'userId' => 12,
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'expirationDate' => DateTime::createFromString('2026-09-28 12:15:00'),
                'createdAt' => DateTime::createFromString('2026-09-28 12:00:00'),
            ]],
            $this->subject->fetchPendingPasswordResetTokens($currentDate),
        );
    }

    public function testCountPendingPasswordResetTokens() : void
    {
        $currentDate = DateTime::createFromString('2026-09-28 12:00:00');
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM `user_password_reset_token` WHERE `expiration_date` > ?',
                ['2026-09-28 12:00:00'],
            )
            ->willReturn('42');

        self::assertSame(42, $this->subject->countPendingPasswordResetTokens($currentDate));
    }

    public function testFetchPendingPasswordResetTokensPaginated() : void
    {
        $currentDate = DateTime::createFromString('2026-09-28 12:00:00');
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'ORDER BY reset_token.`created_at` DESC')
                        && str_contains($query, 'LIMIT 20 OFFSET 40'),
                ),
                ['2026-09-28 12:00:00'],
            )
            ->willReturn([[
                'user_id' => '12',
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'expiration_date' => '2026-09-28 12:15:00',
                'created_at' => '2026-09-28 12:00:00',
            ]]);

        $tokens = $this->subject->fetchPendingPasswordResetTokens($currentDate, 20, 40);

        self::assertSame(12, $tokens[0]['userId']);
        self::assertEquals(DateTime::createFromString('2026-09-28 12:15:00'), $tokens[0]['expirationDate']);
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

    public function testDeleteAllPasswordResetTokens() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('executeStatement')
            ->with('DELETE FROM `user_password_reset_token`');

        $this->subject->deleteAllPasswordResetTokens();
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
