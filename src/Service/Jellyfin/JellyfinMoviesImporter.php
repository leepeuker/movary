<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\JobQueue\JobEntity;
use RuntimeException;

class JellyfinMoviesImporter
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly JellyfinApi $jellyfinApi,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->importMoviesToJellyfin($userId);
    }

    public function importMoviesToJellyfin(int $userId)
    {
        // TODO
    }
}
