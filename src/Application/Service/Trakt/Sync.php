<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Application\SyncLog\Repository;

class Sync
{
    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly SyncWatchedMovies $syncWatchedMovies,
        private readonly Repository $scanLogRepository
    ) {
    }

    public function syncAll(bool $forceSync = false) : void
    {
        $this->syncRatings->execute();
        $this->syncWatchedMovies->execute($forceSync);

        $this->scanLogRepository->insertLogForTraktSync();
    }
}
