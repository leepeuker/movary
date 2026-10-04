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
