<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\HttpController\Web\Mapper\UserFilterRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserFilterRequestMapper::class)]
class UserFilterRequestMapperTest extends TestCase
{
    public static function provideRoles() : array
    {
        return [
            'admin' => ['admin', true],
            'user' => ['user', false],
            'missing' => [null, null],
            'invalid' => ['invalid', null],
            'non-scalar' => [[], null],
        ];
    }

    #[DataProvider('provideRoles')]
    public function testMapsRoleFilter(mixed $role, ?bool $expectedIsAdmin) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn($role === null ? [] : ['role' => $role]);

        $filter = (new UserFilterRequestMapper())->map($request);

        self::assertSame($expectedIsAdmin, $filter->getIsAdmin());
    }
}
