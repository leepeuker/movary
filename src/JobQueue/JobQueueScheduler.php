<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\Service\ServerSettings;

class JobQueueScheduler
{
    private const IMAGE_CACHE_BATCH_LIMIT = 250;

    public function __construct(
        private readonly JobQueueApi $jobQueueApi,
        private readonly ServerSettings $serverSettings,
        private array $movieIdsForImageCacheJob = [],
        private array $personIdsForImageCacheJob = [],
    ) {
    }

    public function __destruct()
    {
        if ($this->getCountOfIdsForImageCacheJob() === 0) {
            return;
        }

        $this->addTmdbImageCacheJob();
    }

    public function storeMovieIdForTmdbImageCacheJob(int $movieId) : void
    {
        if ($this->getCountOfIdsForImageCacheJob() >= self::IMAGE_CACHE_BATCH_LIMIT) {
            $this->addTmdbImageCacheJob();
        }

        $this->movieIdsForImageCacheJob[$movieId] = true;
    }

    public function storePersonIdForTmdbImageCacheJob(int $personId) : void
    {
        if ($this->getCountOfIdsForImageCacheJob() >= self::IMAGE_CACHE_BATCH_LIMIT) {
            $this->addTmdbImageCacheJob();
        }

        $this->personIdsForImageCacheJob[$personId] = true;
    }

    private function addTmdbImageCacheJob() : void
    {
        if ($this->serverSettings->isTmdbCachingEnabled() === false) {
            return;
        }

        $this->jobQueueApi->addTmdbImageCacheJob(array_keys($this->movieIdsForImageCacheJob), array_keys($this->personIdsForImageCacheJob));

        $this->personIdsForImageCacheJob = [];
        $this->movieIdsForImageCacheJob = [];
    }

    private function getCountOfIdsForImageCacheJob() : int
    {
        return count($this->movieIdsForImageCacheJob) + count($this->personIdsForImageCacheJob);
    }
}
