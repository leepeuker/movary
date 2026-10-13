<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\DateTime;
use RuntimeException;

class JobScheduleProcessor
{
    public function __construct(
        private readonly JobScheduleRepository $scheduleRepository,
        private readonly JobQueueApi $jobQueueApi,
    ) {
    }

    public function enqueueDueJobs(?DateTime $currentDateTime = null) : void
    {
        $currentDateTime ??= DateTime::create();

        foreach ($this->scheduleRepository->fetchDue($currentDateTime) as $schedule) {
            $this->enqueue($schedule);
            $nextRun = $schedule->getCronExpression()->calculateNextRun($currentDateTime);
            $this->scheduleRepository->updateNextRunIfCurrent(
                $schedule->getId(),
                $schedule->getNextRunAt(),
                $nextRun,
                $currentDateTime,
            );
        }
    }

    private function enqueue(JobScheduleEntity $schedule) : void
    {
        $jobType = $schedule->getJobType();

        match (true) {
            $jobType->isOfTypeTmdbMovieSync() => $this->enqueueTmdbMovieSync($schedule),
            $jobType->isOfTypeTmdbPersonSync() => $this->enqueueTmdbPersonSync($schedule),
            $jobType->isOfTypeImdbSync() => $this->enqueueImdbSync($schedule),
            $jobType->isOfTypeTmdbImageCache() => $this->enqueueTmdbImageCacheRefresh($schedule),
            default => throw new RuntimeException('Unsupported recurring job type: ' . $jobType),
        };
    }

    private function enqueueImdbSync(JobScheduleEntity $schedule) : void
    {
        $parameters = $schedule->getParameters();
        $this->jobQueueApi->addImdbSyncJobUnlessActive(
            movieIds: $parameters['ids'] ?? [],
            maxAgeInHours: $parameters['maxAgeInHours'] ?? null,
            threshold: $parameters['threshold'] ?? null,
            onlyNeverSynced: $parameters['onlyNeverSynced'] ?? false,
            userId: $schedule->getUserId(),
        );
    }

    private function enqueueTmdbImageCacheRefresh(JobScheduleEntity $schedule) : void
    {
        $parameters = $schedule->getParameters();
        $this->jobQueueApi->addTmdbImageCacheRefreshJobUnlessActive(
            TmdbImageCacheJobParameters::create(
                $parameters['movieIds'] ?? [],
                $parameters['personIds'] ?? [],
            ),
            $schedule->getUserId(),
        );
    }

    private function enqueueTmdbMovieSync(JobScheduleEntity $schedule) : void
    {
        $parameters = $schedule->getParameters();
        $this->jobQueueApi->addTmdbMovieSyncJobUnlessActive(
            movieIds: $parameters['ids'] ?? [],
            maxAgeInHours: $parameters['maxAgeInHours'] ?? null,
            threshold: $parameters['threshold'] ?? null,
            onlyNeverSynced: $parameters['onlyNeverSynced'] ?? false,
            userId: $schedule->getUserId(),
        );
    }

    private function enqueueTmdbPersonSync(JobScheduleEntity $schedule) : void
    {
        $parameters = $schedule->getParameters();
        $this->jobQueueApi->addTmdbPersonSyncJobUnlessActive(
            personIds: $parameters['ids'] ?? [],
            maxAgeInHours: $parameters['maxAgeInHours'] ?? null,
            threshold: $parameters['threshold'] ?? null,
            onlyNeverSynced: $parameters['onlyNeverSynced'] ?? false,
            userId: $schedule->getUserId(),
        );
    }
}
