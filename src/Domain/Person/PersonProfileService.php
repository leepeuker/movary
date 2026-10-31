<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Api\Tmdb\TmdbApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;

class PersonProfileService
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly PersonRepository $personRepository,
        private readonly ImageCacheService $imageCacheService,
        private readonly JobQueueScheduler $jobScheduler,
    ) {
    }

    /** @return list<TmdbProfile> */
    public function fetchAvailableProfiles(PersonEntity $person) : array
    {
        return $this->tmdbApi->fetchPersonProfiles($person->getTmdbId());
    }

    public function select(PersonEntity $person, string $profilePath) : void
    {
        $availablePaths = array_map(
            static fn(TmdbProfile $profile) : string => $profile->getPath(),
            $this->fetchAvailableProfiles($person),
        );

        if (in_array($profilePath, $availablePaths, true) === false) {
            throw new InvalidArgumentException('The selected profile is not available.');
        }

        if ($profilePath === $person->getTmdbPosterPathEffective()) {
            return;
        }

        $this->personRepository->setTmdbPosterPathOverride($person->getId(), $profilePath);
        $this->removeCachedImageAndScheduleRefresh($person);
    }

    public function restoreDefault(PersonEntity $person) : void
    {
        if ($person->getTmdbPosterPathOverride() === null) {
            return;
        }

        $this->personRepository->clearTmdbPosterPathOverride($person->getId());
        $this->removeCachedImageAndScheduleRefresh($person);
    }

    private function removeCachedImageAndScheduleRefresh(PersonEntity $person) : void
    {
        $cachedImagePath = $person->getPosterPath();
        if ($cachedImagePath !== null) {
            $this->imageCacheService->deleteImageByPosterPath($cachedImagePath);
        }

        $this->jobScheduler->storePersonIdForTmdbImageCacheJob($person->getId());
    }
}
