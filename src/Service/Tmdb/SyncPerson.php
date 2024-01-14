<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Person\PersonApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;

class SyncPerson
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly PersonApi $personApi,
        private readonly JobQueueScheduler $jobScheduler,
        private readonly LoggerInterface $logger,
        private readonly PersonChangesCalculator $changesCalculator,
    ) {
    }

    public function syncPerson(int $tmdbId) : void
    {
        try {
            $tmdbPerson = $this->tmdbApi->fetchPersonDetails($tmdbId);
        } catch (TmdbResourceNotFound) {
            $person = $this->personApi->findByTmdbId($tmdbId);

            if ($person !== null) {
                $this->personApi->deleteById($person->getId());
            }

            $this->logger->debug('TMDB: Could not sync person data, id does not exist', ['tmdbId' => $tmdbId]);

            return;
        }

        $person = $this->personApi->findByTmdbId($tmdbId);

        if ($person === null) {
            $person = $this->personApi->create(
                $tmdbPerson->getTmdbId(),
                $tmdbPerson->getName(),
                $tmdbPerson->getGender(),
                $tmdbPerson->getKnownForDepartment(),
                $tmdbPerson->getProfilePath(),
                $tmdbPerson->getBiography(),
                $tmdbPerson->getBirthDate(),
                $tmdbPerson->getDeathDate(),
                $tmdbPerson->getPlaceOfBirth(),
                updatedAtTmdb: DateTime::create(),
                imdbId: $tmdbPerson->getImdbId(),
            );

            $this->logger->debug('TMDB: Created person', ['personId' => $person->getId(), 'tmdbId' => $person->getTmdbId()]);

            $this->jobScheduler->storePersonIdForTmdbImageCacheJob($person->getId());

            return;
        }

        $originalTmdbPosterPath = $person->getTmdbPosterPath();

        $changes = $this->changesCalculator->calculatePersonChanges($person, $tmdbPerson);

        $person = $this->personApi->update(
            $person->getId(),
            $tmdbPerson->getTmdbId(),
            $tmdbPerson->getName(),
            $tmdbPerson->getGender(),
            $tmdbPerson->getKnownForDepartment(),
            $tmdbPerson->getProfilePath(),
            $tmdbPerson->getBiography(),
            $tmdbPerson->getBirthDate(),
            $tmdbPerson->getDeathDate(),
            $tmdbPerson->getPlaceOfBirth(),
            DateTime::create(),
            $tmdbPerson->getImdbId(),
        );

        $this->logger->debug(
            'TMDB: Synced person data',
            array_merge(
                ['personId' => $person->getId(), 'tmdbId' => $person->getTmdbId()],
                ['changes' => $changes],
            ),
        );

        if ($originalTmdbPosterPath !== $person->getTmdbPosterPath()) {
            $this->jobScheduler->storePersonIdForTmdbImageCacheJob($person->getId());
        }
    }
}
