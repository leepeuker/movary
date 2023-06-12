<?php declare(strict_types=1);

namespace Movary\Service\Dashboard;

use Movary\Domain\User\UserEntity;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Service\Dashboard\Dto\DashboardRowList;

class DashboardFactory
{
    public function createDashboardRowsForUser(UserEntity $user) : DashboardRowList
    {
        $visibleRows = (string)$user->getDashboardVisibleRows();
        $extendedRows = (string)$user->getDashboardExtendedRows();

        $visibleRows = $visibleRows !== '' ? explode(';', $visibleRows) : [];
        $extendedRows = $extendedRows !== '' ? explode(';', $extendedRows) : [];

        if (empty($visibleRows) === true) {
            return $this->createDefaultDashboardRows();
        }

        $dashboardRows = DashboardRowList::create();

        foreach ($visibleRows as $rowId) {
            $dashboardRows->add($this->createDashboardRowById((int)$rowId, in_array($rowId, $extendedRows, true)));
        }

        return $dashboardRows;
    }

    public function createDefaultDashboardRows() : DashboardRowList
    {
        return DashboardRowList::create(
            DashboardRow::createLastPlays(),
            DashboardRow::createMostWatchedActors(),
            DashboardRow::createMostWatchedActresses(),
            DashboardRow::createMostWatchedDirectors(),
            DashboardRow::createMostWatchedGenres(),
            DashboardRow::createMostWatchedLanguages(),
            DashboardRow::createMostWatchedProductionCompanies(),
            DashboardRow::createMostWatchedReleaseYears(),
        );
    }

    private function createDashboardRowById(int $rowId, bool $isExtended) : DashboardRow
    {
        return match (true) {
            DashboardRow::createLastPlays()->getId() === $rowId => DashboardRow::createLastPlays($isExtended),
            DashboardRow::createMostWatchedActors()->getId() === $rowId => DashboardRow::createMostWatchedActors($isExtended),
            DashboardRow::createMostWatchedActresses()->getId() === $rowId => DashboardRow::createMostWatchedActresses($isExtended),
            DashboardRow::createMostWatchedDirectors()->getId() === $rowId => DashboardRow::createMostWatchedDirectors($isExtended),
            DashboardRow::createMostWatchedGenres()->getId() === $rowId => DashboardRow::createMostWatchedGenres($isExtended),
            DashboardRow::createMostWatchedLanguages()->getId() === $rowId => DashboardRow::createMostWatchedLanguages($isExtended),
            DashboardRow::createMostWatchedProductionCompanies()->getId() === $rowId => DashboardRow::createMostWatchedProductionCompanies($isExtended),
            DashboardRow::createMostWatchedReleaseYears()->getId() === $rowId => DashboardRow::createMostWatchedReleaseYears($isExtended),

            default => throw new \RuntimeException('Not supported dashboard row id: ' . $rowId)
        };
    }
}
