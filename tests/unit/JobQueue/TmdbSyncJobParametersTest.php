<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use InvalidArgumentException;
use Movary\JobQueue\TmdbSyncJobParameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbSyncJobParameters::class)]
class TmdbSyncJobParametersTest extends TestCase
{
    public function testCreateDeduplicatesIds() : void
    {
        self::assertSame([7, 8], TmdbSyncJobParameters::create(48, 100, [7, 8, 7], true)->getIds());
    }

    public function testUnboundedSyncRequiresConfirmation() : void
    {
        self::assertTrue(TmdbSyncJobParameters::create(null, null, [], false)->requiresUnboundedSyncConfirmation());
        self::assertFalse(TmdbSyncJobParameters::create(null, 100, [], false)->requiresUnboundedSyncConfirmation());
        self::assertFalse(TmdbSyncJobParameters::create(null, null, [7], false)->requiresUnboundedSyncConfirmation());
    }

    public function testCreateRejectsThresholdOverMaximum() : void
    {
        $this->expectException(InvalidArgumentException::class);

        TmdbSyncJobParameters::create(null, TmdbSyncJobParameters::MAX_THRESHOLD + 1, [], false);
    }
}
