<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\HttpController\Web\Mapper\JobScheduleRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleRequestMapper::class)]
class JobScheduleRequestMapperTest extends TestCase
{
    public function testMapReturnsNullForOneTimeJob() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['scheduleMode' => 'once']);

        self::assertNull((new JobScheduleRequestMapper())->map($request));
    }

    public function testMapReturnsRecurringConfiguration() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([
            'scheduleMode' => 'recurring',
            'cronExpression' => '*/15 * * * *',
        ]);

        $configuration = (new JobScheduleRequestMapper())->map($request);

        self::assertNotNull($configuration);
        self::assertSame('*/15 * * * *', (string)$configuration->getCronExpression());
        self::assertSame('00', $configuration->getNextRunAt()->format('s'));
        self::assertContains($configuration->getNextRunAt()->format('i'), ['00', '15', '30', '45']);
    }

    #[DataProvider('provideInvalidScheduleData')]
    public function testMapRejectsInvalidScheduleData(array $data) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn($data);
        $this->expectException(InvalidArgumentException::class);

        (new JobScheduleRequestMapper())->map($request);
    }

    public static function provideInvalidScheduleData() : iterable
    {
        yield 'unknown schedule mode' => [['scheduleMode' => 'yearly']];
        yield 'missing cron expression' => [['scheduleMode' => 'recurring']];
        yield 'invalid cron expression' => [['scheduleMode' => 'recurring', 'cronExpression' => '60 * * * *']];
        yield 'impossible cron expression' => [['scheduleMode' => 'recurring', 'cronExpression' => '0 0 31 2 *']];
    }
}
