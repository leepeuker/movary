<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\DateTime;

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
            $nextRun = $schedule->getFrequency()->calculateNextRun(
                $schedule->getFirstRunAt(),
                $currentDateTime,
            );
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
        $parameters = $schedule->getParameters();
        if ($schedule->getJobType()->isOfTypeTmdbMovieSync()) {
            $this->jobQueueApi->addTmdbMovieSyncJobUnlessActive(
                movieIds: $parameters['ids'] ?? [],
                maxAgeInHours: $parameters['maxAgeInHours'] ?? null,
                threshold: $parameters['threshold'] ?? null,
                onlyNeverSynced: $parameters['onlyNeverSynced'] ?? false,
                userId: $schedule->getUserId(),
            );

            return;
        }

        $this->jobQueueApi->addTmdbPersonSyncJobUnlessActive(
            personIds: $parameters['ids'] ?? [],
            maxAgeInHours: $parameters['maxAgeInHours'] ?? null,
            threshold: $parameters['threshold'] ?? null,
            onlyNeverSynced: $parameters['onlyNeverSynced'] ?? false,
            userId: $schedule->getUserId(),
        );
    }
}
