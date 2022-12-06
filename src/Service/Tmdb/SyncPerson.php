<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Person\PersonApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\ValueObject\DateTime;

class SyncPerson
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly PersonApi $personApi,
        private readonly JobQueueScheduler $jobScheduler,
    ) {
    }

    public function syncPerson(int $tmdbId) : void
    {
        $tmdbPerson = $this->tmdbApi->fetchPersonDetails($tmdbId);

        $person = $this->personApi->findByTmdbId($tmdbId);

        if ($person === null) {
            $person = $this->personApi->create(
                $tmdbPerson->getTmdbId(),
                $tmdbPerson->getName(),
                $tmdbPerson->getGender(),
                $tmdbPerson->getKnownForDepartment(),
                $tmdbPerson->getProfilePath(),
                $tmdbPerson->getBirthDate(),
                $tmdbPerson->getDeathDate(),
                $tmdbPerson->getPlaceOfBirth(),
                updatedAtTmdb: DateTime::create(),
            );

            $this->jobScheduler->storePersonIdForTmdbImageCacheJob($person->getId());

            return;
        }

        $originalTmdbPosterPath = $person->getTmdbPosterPath();

        $person = $this->personApi->update(
            $person->getId(),
            $tmdbPerson->getTmdbId(),
            $tmdbPerson->getName(),
            $tmdbPerson->getGender(),
            $tmdbPerson->getKnownForDepartment(),
            $tmdbPerson->getProfilePath(),
            $tmdbPerson->getBirthDate(),
            $tmdbPerson->getDeathDate(),
            $tmdbPerson->getPlaceOfBirth(),
            DateTime::create(),
        );

        if ($originalTmdbPosterPath !== $person->getTmdbPosterPath()) {
            $this->jobScheduler->storePersonIdForTmdbImageCacheJob($person->getId());
        }
    }
}
