<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Dashboard;

use Movary\Domain\User\UserEntity;
use Movary\Service\Dashboard\DashboardFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\Dashboard\DashboardFactory::class)]
class DashboardFactoryTest extends TestCase
{
    private DashboardFactory $subject;

    public function setUp() : void
    {
        $this->subject = new DashboardFactory();
    }

    public function testCreateDashboardRowsForUserUsesDefaultsWithoutSavedSettings() : void
    {
        $user = $this->createUser(null);

        $dashboardRows = $this->subject->createDashboardRowsForUser($user);

        foreach ($dashboardRows as $dashboardRow) {
            self::assertTrue($dashboardRow->isVisible());
        }

        $keys = array_map(static fn($row) => $row->getKey(), iterator_to_array($dashboardRows));
        self::assertCount(count(array_unique($keys)), $keys);
        self::assertSame([
            'last-plays', 'most-watched-actors', 'most-watched-actresses', 'most-watched-directors',
            'most-watched-genres', 'most-watched-languages', 'most-watched-production-companies',
            'most-watched-release-years', 'latest-watchlist', 'top-locations', 'last-plays-cinema',
            'most-watched-production-countries', 'personal-rating-distribution',
        ], $keys);
    }

    public function testCreateDashboardRowsForUserCanHideAllRows() : void
    {
        $user = $this->createUser('');

        $dashboardRows = $this->subject->createDashboardRowsForUser($user);

        foreach ($dashboardRows as $dashboardRow) {
            self::assertFalse($dashboardRow->isVisible());
        }
    }

    public function testCreateDashboardRowsForUserUsesSavedVisibility() : void
    {
        $user = $this->createUser('0;9');

        $dashboardRows = $this->subject->createDashboardRowsForUser($user);

        foreach ($dashboardRows as $dashboardRow) {
            self::assertSame(
                in_array($dashboardRow->getId(), [0, 9], true),
                $dashboardRow->isVisible(),
            );
        }
    }

    private function createUser(?string $visibleRows) : UserEntity
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getDashboardVisibleRows')->willReturn($visibleRows);
        $user->method('getDashboardExtendedRows')->willReturn(null);
        $user->method('getDashboardOrderRows')->willReturn(null);

        return $user;
    }
}
