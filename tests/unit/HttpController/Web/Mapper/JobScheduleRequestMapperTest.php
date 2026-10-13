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
        $request->method('getPostParameters')->willReturn(['frequency' => 'once']);

        self::assertNull((new JobScheduleRequestMapper())->map($request));
    }

    public function testMapReturnsRecurringConfiguration() : void
    {
        $firstRunAt = time() + 3600;
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([
            'frequency' => 'minutely',
            'firstRunAt' => (string)$firstRunAt,
        ]);

        $configuration = (new JobScheduleRequestMapper())->map($request);

        self::assertNotNull($configuration);
        self::assertSame('minutely', (string)$configuration->getFrequency());
        self::assertSame($firstRunAt, (int)$configuration->getFirstRunAt()->format('U'));
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
        yield 'unknown frequency' => [['frequency' => 'yearly', 'firstRunAt' => (string)(time() + 3600)]];
        yield 'missing first run' => [['frequency' => 'daily']];
        yield 'past first run' => [['frequency' => 'daily', 'firstRunAt' => (string)(time() - 60)]];
    }
}
