<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** @covers \Movary\ValueObject\JobStatus */
class JobStatusTest extends TestCase
{
    public function testCreateDone() : void
    {
        $subject = JobStatus::createDone();

        self::assertSame('done', (string)$subject);
    }

    public function testCreateFailed() : void
    {
        $subject = JobStatus::createFailed();

        self::assertSame('failed', (string)$subject);
    }

    public function testCreateInProgress() : void
    {
        $subject = JobStatus::createInProgress();

        self::assertSame('in progress', (string)$subject);
    }

    public function testCreateThrowsExceptionIfJobTypeIsNotSupported() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not supported job status: foobar');

        JobStatus::createFromString('foobar');
    }

    public function testCreateWaiting() : void
    {
        $subject = JobStatus::createWaiting();

        self::assertSame('waiting', (string)$subject);
    }
}
