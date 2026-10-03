<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History;

use Closure;
use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\History\MovieHistoryEditor;
use Movary\Domain\Movie\MovieApi;
use Movary\ValueObject\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryEditor::class)]
class MovieHistoryEditorTest extends TestCase
{
    private Connection&MockObject $dbConnectionMock;

    private MovieApi&MockObject $movieApiMock;

    private MovieHistoryEditor $subject;

    protected function setUp() : void
    {
        $this->dbConnectionMock = $this->createMock(Connection::class);
        $this->movieApiMock = $this->createMock(MovieApi::class);
        $this->subject = new MovieHistoryEditor($this->dbConnectionMock, $this->movieApiMock);
    }

    public static function provideHistoryMetadata() : array
    {
        return [
            'set metadata' => ['A comment', 7],
            'clear metadata' => [null, null],
        ];
    }

    #[DataProvider('provideHistoryMetadata')]
    public function testChangingWatchDateMovesCompleteEntryInTransaction(?string $comment, ?int $locationId) : void
    {
        $calls = [];
        $originalDate = Date::createFromString('2026-09-01');
        $newDate = Date::createFromString('2026-09-02');

        $this->expectTransaction($calls);
        $this->movieApiMock
            ->expects(self::once())
            ->method('addPlaysForMovieOnDate')
            ->with(34, 12, $newDate, 2, 3, $comment, $locationId, true)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'add';
            });
        $this->movieApiMock
            ->expects(self::once())
            ->method('deleteHistoryByIdAndDate')
            ->with(34, 12, $originalDate)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'delete';
            });
        $this->movieApiMock
            ->expects(self::once())
            ->method('updateHistoryComment')
            ->with(34, 12, $newDate, $comment)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'comment';
            });
        $this->movieApiMock
            ->expects(self::once())
            ->method('updateHistoryLocation')
            ->with(34, 12, $newDate, $locationId)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'location';
            });
        $this->movieApiMock->expects(self::never())->method('replaceHistoryForMovieByDate');

        $this->subject->update(34, 12, $originalDate, $newDate, 2, 3, $comment, $locationId, true);

        self::assertSame(['transaction-start', 'add', 'delete', 'comment', 'location', 'transaction-end'], $calls);
    }

    public function testEditingUnchangedWatchDateUpdatesCompleteEntryInTransaction() : void
    {
        $calls = [];
        $watchDate = Date::createFromString('2026-09-01');

        $this->expectTransaction($calls);
        $this->movieApiMock
            ->expects(self::once())
            ->method('updateHistoryComment')
            ->with(34, 12, $watchDate, null)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'comment';
            });
        $this->movieApiMock
            ->expects(self::once())
            ->method('updateHistoryLocation')
            ->with(34, 12, $watchDate, null)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'location';
            });
        $this->movieApiMock
            ->expects(self::once())
            ->method('replaceHistoryForMovieByDate')
            ->with(34, 12, $watchDate, 2, 3, null, null, false)
            ->willReturnCallback(static function () use (&$calls) : void {
                $calls[] = 'replace';
            });
        $this->movieApiMock->expects(self::never())->method('addPlaysForMovieOnDate');
        $this->movieApiMock->expects(self::never())->method('deleteHistoryByIdAndDate');

        $this->subject->update(34, 12, $watchDate, $watchDate, 2, 3, null, null, false);

        self::assertSame(['transaction-start', 'comment', 'location', 'replace', 'transaction-end'], $calls);
    }

    private function expectTransaction(array &$calls) : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static function (Closure $callback) use (&$calls) : void {
                $calls[] = 'transaction-start';
                $callback();
                $calls[] = 'transaction-end';
            });
    }
}
