<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User;

use Movary\Domain\User\UserFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserFilter::class)]
class UserFilterTest extends TestCase
{
    public static function provideFilters() : array
    {
        return [
            'all roles' => [null, false],
            'admins' => [true, true],
            'users' => [false, true],
        ];
    }

    #[DataProvider('provideFilters')]
    public function testCreatesRoleFilter(?bool $isAdmin, bool $hasFilters) : void
    {
        $filter = UserFilter::create($isAdmin);

        self::assertSame($isAdmin, $filter->getIsAdmin());
        self::assertSame($hasFilters, $filter->hasFilters());
    }
}
