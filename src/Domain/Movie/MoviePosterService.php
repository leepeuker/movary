<?php declare(strict_types=1);

namespace Movary\Domain\Movie;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Api\Tmdb\TmdbApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;

class MoviePosterService
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly MovieRepository $movieRepository,
        private readonly ImageCacheService $imageCacheService,
        private readonly JobQueueScheduler $jobScheduler,
    ) {
    }

    /** @return list<TmdbPoster> */
    public function fetchAvailablePosters(MovieEntity $movie) : array
    {
        return $this->tmdbApi->fetchEnglishMoviePosters($movie->getTmdbId());
    }

    public function select(MovieEntity $movie, string $posterPath) : void
    {
        $availablePosters = $this->fetchAvailablePosters($movie);
        $availablePaths = array_map(
            static fn(TmdbPoster $poster) : string => $poster->getPath(),
            $availablePosters,
        );

        if (in_array($posterPath, $availablePaths, true) === false) {
            throw new InvalidArgumentException('The selected poster is not available.');
        }

        if ($posterPath === $movie->getTmdbPosterPathEffective()) {
            return;
        }

        $this->movieRepository->setTmdbPosterPathOverride($movie->getId(), $posterPath);
        $this->removeCachedPosterAndScheduleRefresh($movie);
    }

    public function restoreDefault(MovieEntity $movie) : void
    {
        if ($movie->getTmdbPosterPathOverride() === null) {
            return;
        }

        $this->movieRepository->clearTmdbPosterPathOverride($movie->getId());
        $this->removeCachedPosterAndScheduleRefresh($movie);
    }

    private function removeCachedPosterAndScheduleRefresh(MovieEntity $movie) : void
    {
        $cachedPosterPath = $movie->getPosterPath();
        if ($cachedPosterPath !== null) {
            $this->imageCacheService->deleteImageByPosterPath($cachedPosterPath);
        }

        $this->jobScheduler->storeMovieIdForTmdbImageCacheJob($movie->getId());
    }
}
