<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\HttpController\Web\Mapper\TmdbSyncJobRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbSyncJobRequestMapper::class)]
class TmdbSyncJobRequestMapperTest extends TestCase
{
    public function testMapReturnsValidatedParameters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([
            'maxAgeInHours' => '48',
            'threshold' => '100',
            'ids' => '7, 8,7',
            'onlyNeverSynced' => '1',
        ]);

        $parameters = (new TmdbSyncJobRequestMapper())->map($request);

        self::assertSame(48, $parameters->getMaxAgeInHours());
        self::assertSame(100, $parameters->getThreshold());
        self::assertSame([7, 8], $parameters->getIds());
        self::assertTrue($parameters->isOnlyNeverSynced());
    }

    #[DataProvider('provideInvalidParameters')]
    public function testMapRejectsInvalidParameters(array $postParameters) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn($postParameters);

        $this->expectException(InvalidArgumentException::class);

        (new TmdbSyncJobRequestMapper())->map($request);
    }

    public static function provideInvalidParameters() : iterable
    {
        yield 'zero maximum age' => [['maxAgeInHours' => '0']];
        yield 'negative threshold' => [['threshold' => '-1']];
        yield 'threshold over maximum' => [['threshold' => '10001']];
        yield 'malformed IDs' => [['ids' => '7,nope']];
        yield 'empty ID' => [['ids' => '7,,8']];
        yield 'invalid checkbox' => [['onlyNeverSynced' => 'yes']];
    }
}
