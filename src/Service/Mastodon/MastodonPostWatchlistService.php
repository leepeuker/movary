<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use RuntimeException;

class MastodonPostWatchlistService
{
    public function __construct(
        private readonly MastodonPostService $mastodonPostService,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing parameter: userId');
        }

        $movieId = $job->getParameters()['movieId'] ?? null;
        if ($movieId === null) {
            throw new RuntimeException('Missing parameter: movieId');
        }

        $this->postWatchlist($userId, $movieId);
    }

    public function postWatchlist(int $userId, int $movieId) : void
    {
        $movie = $this->movieApi->findById($movieId);
        if ($movie === null) {
            throw new RuntimeException('Movie does not exist with id: ' . $movieId);
        }

        // TODO Improve message
        $message = 'Added movie to watchlist: ' . $movie->getTitle();

        $this->mastodonPostService->postMessageForUser($userId, $message);
    }
}
