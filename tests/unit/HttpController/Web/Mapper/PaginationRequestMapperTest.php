<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\HttpController\Web\Mapper\PaginationRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaginationRequestMapper::class)]
class PaginationRequestMapperTest extends TestCase
{
    public static function provideParameters() : array
    {
        return [
            'defaults' => [[], 1, 20],
            'canonical parameters' => [['page' => '3', 'perPage' => '50'], 3, 50],
            'legacy parameters' => [['p' => '2', 'jpp' => '250'], 2, 250],
            'canonical parameters take precedence' => [
                ['page' => '3', 'p' => '2', 'perPage' => '50', 'jpp' => '250'],
                3,
                50,
            ],
            'invalid values use defaults' => [['page' => '-1', 'perPage' => '1000'], 1, 20],
            'non-scalar values use defaults' => [['page' => [], 'perPage' => []], 1, 20],
        ];
    }

    #[DataProvider('provideParameters')]
    public function testMapsPaginationRequest(array $parameters, int $expectedPage, int $expectedPerPage) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn($parameters);

        $paginationRequest = (new PaginationRequestMapper())->map($request, 20, [20, 50, 100, 250], ['jpp']);

        self::assertSame($expectedPage, $paginationRequest->getPage());
        self::assertSame($expectedPerPage, $paginationRequest->getPerPage());
    }
}
