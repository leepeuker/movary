<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PersonListRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonListRequestMapper::class)]
class PersonListRequestMapperTest extends TestCase
{
    public function testMapsCatalogFiltersAndSortOptions() : void
    {
        $request = $this->request(['gender' => '0', 'role' => 'director']);
        $baseMapper = $this->createMock(RequestMapper::class);
        $baseMapper->expects(self::once())->method('mapPage')->with($request)->willReturn(2);
        $baseMapper->expects(self::once())->method('mapLimit')->with($request)->willReturn(10);
        $baseMapper->expects(self::once())->method('mapSearchTerm')->with($request)->willReturn('Lee');
        $baseMapper->expects(self::once())->method('mapSortBy')->with($request, ['id', 'name', 'birthDate'], 'name')->willReturn('birthDate');
        $baseMapper->expects(self::once())->method('mapSortOrder')->with($request, 'asc')->willReturn(SortOrder::createDesc());

        $result = (new PersonListRequestMapper($baseMapper))->mapRequest($request);

        self::assertSame(2, $result['page']);
        self::assertSame(10, $result['limit']);
        self::assertSame('Lee', $result['search']);
        self::assertSame(0, $result['gender']?->asInt());
        self::assertSame('director', $result['role']);
        self::assertSame('birthDate', $result['sortBy']);
        self::assertSame('desc', (string)$result['sortOrder']);
    }

    public function testRejectsUnsupportedGender() : void
    {
        $this->expectException(InvalidApiRequest::class);
        (new PersonListRequestMapper($this->createStub(RequestMapper::class)))->mapRequest($this->request(['gender' => '4']));
    }

    public function testRejectsUnsupportedRole() : void
    {
        $this->expectException(InvalidApiRequest::class);
        (new PersonListRequestMapper($this->createStub(RequestMapper::class)))->mapRequest($this->request(['role' => 'producer']));
    }

    private function request(array $parameters) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn($parameters);

        return $request;
    }
}
