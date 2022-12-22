<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\TraktApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Trakt\Exception\TraktClientIdNotSet;
use Movary\Service\Trakt\Exception\TraktUserNameNotSet;
use Movary\ValueObject\PersonalRating;
use RuntimeException;

class ImportRatings
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly TraktApi $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Rating\Service $traktApiCacheUserMovieRatingService,
        private readonly UserApi $userApi,
    ) {
    }

    public function execute(int $userId, bool $overwriteExistingData = false) : void
    {
        $traktClientId = $this->userApi->findTraktClientId($userId);
        if ($traktClientId === null) {
            throw new TraktClientIdNotSet();
        }

        $traktUserName = $this->userApi->findTraktUserName($userId);
        if ($traktUserName === null) {
            throw new TraktUserNameNotSet();
        }

        $this->traktApiCacheUserMovieRatingService->set($userId, $this->traktApi->fetchUserMoviesRatings($traktClientId, $traktUserName));

        foreach ($this->movieApi->fetchAll() as $movie) {
            $traktId = $movie->getTraktId();

            if ($traktId === null) {
                continue;
            }

            $traktUserRating = $this->traktApiCacheUserMovieRatingService->findRatingByTraktId($userId, $traktId);

            if ($traktUserRating === null) {
                continue;
            }

            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);

            if ($overwriteExistingData === true || $userRating === null) {
                $this->movieApi->updateUserRating($movie->getId(), $userId, PersonalRating::create($traktUserRating));
            }
        }
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId);
    }
}
