<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\ServerSettings;
use Movary\Service\SlugifyService;
use RuntimeException;

class MastodonPostWatchlistService
{
    public function __construct(
        private readonly MastodonPostService $mastodonPostService,
        private readonly MovieApi $movieApi,
        private readonly ServerSettings $serverSettings,
        private readonly UserApi $userApi,
        private readonly SlugifyService $slugify,
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
        
        $user = $this->userApi->findUserById($userId);
        if ($user === null) {
            throw new RuntimeException('User does not exist with id: ' . $movieId);
        }

        $applicationUrl = $this->serverSettings->requireApplicationUrl();
        $movieUrl = (
            $applicationUrl . "/users/" . $user->getName() . "/movies/"
            . $movieId . '-' . $this->slugify->slugify($movie->getTitle())
        );
        // link only renders on mastodon if https, not for http
        $message = (
            'Added movie to watchlist: '
            . $movie->getTitle() . ' (' . $movie->getReleaseDate()?->format('Y') . ')'
            . "\n\n" . $movieUrl
        );

        $this->mastodonPostService->postMessageForUser($userId, $message);
    }
}
