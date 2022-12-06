<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Person\PersonApi;
use Movary\ValueObject\DateTime;

class SyncPerson
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly PersonApi $personApi,
    ) {
    }

    public function syncPerson(int $tmdbId) : void
    {
        $tmdbPerson = $this->tmdbApi->fetchPersonDetails($tmdbId);

        $person = $this->personApi->findByTmdbId($tmdbId);

        if ($person === null) {
            $this->personApi->create(
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

            return;
        }

        $this->personApi->update(
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
    }
}
