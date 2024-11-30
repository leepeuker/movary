<?php declare(strict_types=1);

namespace Movary\Service\Dashboard;

use Movary\Domain\User\UserEntity;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use RuntimeException;

class DashboardFactory
{
    public function createDashboardRowsForUser(UserEntity $user) : DashboardRowList
    {
        $visibleRows = (string)$user->getDashboardVisibleRows();
        $extendedRows = (string)$user->getDashboardExtendedRows();
        $orderRows = (string)$user->getDashboardOrderRows();

        $visibleRows = $visibleRows !== '' ? explode(';', $visibleRows) : [];
        $extendedRows = $extendedRows !== '' ? explode(';', $extendedRows) : [];
        $orderRows = $orderRows !== '' ? explode(';', $orderRows) : [];

        if (empty($visibleRows) === true) {
            return $this->createDefaultDashboardRows();
        }

        $dashboardRows = DashboardRowList::create();

        foreach (self::createDefaultDashboardRows() as $defaultDashboardRow) {
            $isVisible = in_array($defaultDashboardRow->getId(), $visibleRows);
            $isExtended = in_array($defaultDashboardRow->getId(), $extendedRows);
            $position = array_search((string)$defaultDashboardRow->getId(), $orderRows);

            if ($position === false) {
                $dashboardRows->add($this->createDashboardRowById($defaultDashboardRow->getId(), $isVisible, $isExtended));

                continue;
            }

            $dashboardRows->addAtOffset($position, $this->createDashboardRowById($defaultDashboardRow->getId(), $isVisible, $isExtended));
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
            DashboardRow::createWatchlist(),
            DashboardRow::createTopLocations(),
            DashboardRow::createLastPlaysCinema(),
        );
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity
    private function createDashboardRowById(int $rowId, bool $isVisible, bool $isExtended) : DashboardRow
    {
        return match (true) {
            DashboardRow::createLastPlays()->getId() === $rowId => DashboardRow::createLastPlays($isVisible, $isExtended),
            DashboardRow::createMostWatchedActors()->getId() === $rowId => DashboardRow::createMostWatchedActors($isVisible, $isExtended),
            DashboardRow::createMostWatchedActresses()->getId() === $rowId => DashboardRow::createMostWatchedActresses($isVisible, $isExtended),
            DashboardRow::createMostWatchedDirectors()->getId() === $rowId => DashboardRow::createMostWatchedDirectors($isVisible, $isExtended),
            DashboardRow::createMostWatchedGenres()->getId() === $rowId => DashboardRow::createMostWatchedGenres($isVisible, $isExtended),
            DashboardRow::createMostWatchedLanguages()->getId() === $rowId => DashboardRow::createMostWatchedLanguages($isVisible, $isExtended),
            DashboardRow::createMostWatchedProductionCompanies()->getId() === $rowId => DashboardRow::createMostWatchedProductionCompanies($isVisible, $isExtended),
            DashboardRow::createMostWatchedReleaseYears()->getId() === $rowId => DashboardRow::createMostWatchedReleaseYears($isVisible, $isExtended),
            DashboardRow::createWatchlist()->getId() === $rowId => DashboardRow::createWatchlist($isVisible, $isExtended),
            DashboardRow::createTopLocations()->getId() === $rowId => DashboardRow::createTopLocations($isVisible, $isExtended),
            DashboardRow::createLastPlaysCinema()->getId() === $rowId => DashboardRow::createLastPlaysCinema($isVisible, $isExtended),

            default => throw new RuntimeException('Not supported dashboard row id: ' . $rowId)
        };
    }
}
