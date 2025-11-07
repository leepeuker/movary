<?php declare(strict_types=1);

namespace Movary\Service\Mastodon;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\Service\SlugifyService;
use Movary\ValueObject\Date;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;

class MastodonPostPlayService
{
    public function __construct(
        private readonly MastodonPostService $mastodonPostService,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SlugifyService $slugify,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly MovieHistoryApi $movieHistoryApi,
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

        $watchDate = $job->getParameters()['watchDate'] ?? null;
        if ($watchDate === null) {
            throw new RuntimeException('Missing parameter: watchDate');
        }

        $this->postPlay($userId, $movieId, $watchDate);
    }

    public function postPlay(int $userId, int $movieId, string $watchDate) : void
    {
        $movie = $this->movieApi->findById($movieId);
        if ($movie === null) {
            throw new RuntimeException('Movie does not exist with id: ' . $movieId);
        }

        $user = $this->userApi->findUserById($userId);
        if ($user === null) {
            throw new RuntimeException('User does not exist with id: ' . $movieId);
        }

        $watch = $this->movieHistoryApi->findHistoryEntryForMovieByUserOnDate(
            $movieId, $userId, Date::createFromString($watchDate)
        );
        if ($watch === null) {
            throw new RuntimeException(
                'Movie id ' . $movieId . ' and user id ' . $userId . ' does not have a watch on ' . $watchDate
            );
        }

        $personalRating = $this->movieApi->findUserRating($movieId, $userId)?->asInt();

        $comment = $watch->getComment();

        $movieUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create(
                '/users/' . $user->getName() . '/movies/' . $movieId . '-' . $this->slugify->slugify($movie->getTitle())
            )
        );

        // link only renders on mastodon if https, not for http
        $message_start = (
            'Watched movie: '
            . $movie->getTitle() . ' (' . $movie->getReleaseDate()?->format('Y') . ')'
            . (
                $personalRating != null
                  ? (
                    "\nRated: "
                    . str_repeat("★", $personalRating)
                    . str_repeat("☆", 10 - $personalRating)
                  ) : ""
            )
        );
        $message_comment = $comment != null ? "\nComment: " . $comment : "";
        $message_url = "\n\n" . $movieUrl;

        // for mastodon, cannot send message over 500 characters
        $length = strlen($message_start . $message_comment . $message_url);
        if ($length >= 500) {
            $max_message_length = 500 - strlen($message_start . $message_url) - 2;
            $message_comment = substr($message_comment, 0, $max_message_length) . "…\n";
        }
        $message = $message_start . $message_comment . $message_url;

        $this->mastodonPostService->postMessageForUser($userId, $message);
    }
}
