<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb\Dto\TmdbPerson;
use Movary\Domain\Person\PersonEntity;

class PersonChangesCalculator
{
    // phpcs:ignore Generic.Metrics.CyclomaticComplexity
    public function calculatePersonChanges(PersonEntity $person, TmdbPerson $tmdbPerson) : array
    {
        $changes = [];

        if ($person->getName() !== $tmdbPerson->getName()) {
            $changes['name'] = [
                'old' => $person->getName(),
                'new' => $tmdbPerson->getName(),
            ];
        }

        if ($person->getBiography() !== $tmdbPerson->getBiography()) {
            $changes['biography'] = [
                'old' => $person->getBiography() === null ? null : mb_strimwidth((string)$person->getBiography(), 0, 25, '...'),
                'new' => $tmdbPerson->getBiography() === null ? null : mb_strimwidth((string)$tmdbPerson->getBiography(), 0, 25, '...'),
            ];
        }

        if ((string)$person->getBirthDate() !== (string)$tmdbPerson->getBirthDate()) {
            $changes['birthDate'] = [
                'old' => (string)$person->getBirthDate(),
                'new' => (string)$tmdbPerson->getBirthDate(),
            ];
        }

        if ((string)$person->getDeathDate() !== (string)$tmdbPerson->getDeathDate()) {
            $changes['deathDate'] = [
                'old' => (string)$person->getDeathDate(),
                'new' => (string)$tmdbPerson->getDeathDate(),
            ];
        }

        if ($person->getPlaceOfBirth() !== $tmdbPerson->getPlaceOfBirth()) {
            $changes['placeOfBirth'] = [
                'old' => $person->getPlaceOfBirth(),
                'new' => $tmdbPerson->getPlaceOfBirth(),
            ];
        }

        if ($person->getImdbId() !== $tmdbPerson->getImdbId()) {
            $changes['imdbId'] = [
                'old' => $person->getImdbId(),
                'new' => $tmdbPerson->getImdbId(),
            ];
        }

        if ($person->getPlaceOfBirth() !== $tmdbPerson->getPlaceOfBirth()) {
            $changes['placeOfBirth'] = [
                'old' => $person->getPlaceOfBirth(),
                'new' => $tmdbPerson->getPlaceOfBirth(),
            ];
        }

        if ($person->getGender()->isEqual($tmdbPerson->getGender()) === false) {
            $changes['gender'] = [
                'old' => $person->getGender()->getText(),
                'new' => $tmdbPerson->getGender()->getText(),
            ];
        }

        if ($person->getKnownForDepartment() !== $tmdbPerson->getKnownForDepartment()) {
            $changes['knownForDepartment'] = [
                'old' => $person->getKnownForDepartment(),
                'new' => $tmdbPerson->getKnownForDepartment(),
            ];
        }

        if ($person->getTmdbPosterPath() !== $tmdbPerson->getProfilePath()) {
            $changes['tmdbPosterPath'] = [
                'old' => $person->getTmdbPosterPath(),
                'new' => $tmdbPerson->getProfilePath(),
            ];
        }

        return $changes;
    }
}
